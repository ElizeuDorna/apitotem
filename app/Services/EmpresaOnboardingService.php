<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\EmpresaFinanceiroConfig;
use App\Models\EmpresaFinanceiroCobranca;
use App\Models\EmpresaSubscription;
use App\Models\EmpresaSubscriptionPlan;
use App\Models\Configuracao;
use App\Models\User;
use App\Models\WebScreenModel;
use App\Rules\CpfCnpjValido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use RuntimeException;

class EmpresaOnboardingService
{
    public function __construct(
        private readonly FinanceiroChargeService $financeiroChargeService,
        private readonly AsaasService $asaasService,
    ) {}

    public function availableSelfServicePlans(): array
    {
        $databasePlans = EmpresaSubscriptionPlan::query()
            ->where('is_self_service', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($databasePlans->isNotEmpty()) {
            $options = EmpresaFinanceiroConfig::billingIntervalOptions();

            return $databasePlans->map(function (EmpresaSubscriptionPlan $plan) use ($options) {
                $intervaloDias = $this->resolveBillingIntervalDays($plan->intervalo_cobranca_dias);

                return [
                    'id' => $plan->id,
                    'code' => $plan->code,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'intervalo_cobranca_dias' => $intervaloDias,
                    'intervalo_label' => $options[$intervaloDias],
                    'valor_unitario' => round((float) $plan->valor_unitario, 2),
                    'trial_days' => (int) $plan->trial_days,
                ];
            })->values()->all();
        }

        $plans = config('subscriptions.self_service.plans', []);

        if (! is_array($plans)) {
            return [];
        }

        $options = EmpresaFinanceiroConfig::billingIntervalOptions();

        return collect($plans)
            ->filter(fn ($plan) => is_array($plan) && isset($plan['name'], $plan['intervalo_cobranca_dias'], $plan['valor_unitario']))
            ->map(function (array $plan, string $code) use ($options) {
                $intervaloDias = $this->resolveBillingIntervalDays($plan['intervalo_cobranca_dias'] ?? null);

                return [
                    'id' => null,
                    'code' => $code,
                    'name' => (string) $plan['name'],
                    'description' => (string) ($plan['description'] ?? ''),
                    'intervalo_cobranca_dias' => $intervaloDias,
                    'intervalo_label' => $options[$intervaloDias],
                    'valor_unitario' => round((float) $plan['valor_unitario'], 2),
                    'trial_days' => max(0, (int) ($plan['trial_days'] ?? config('subscriptions.self_service.trial_days', 0))),
                ];
            })
            ->values()
            ->all();
    }

    public function registerSelfServiceCompany(array $payload): array
    {
        $payload['company_document'] = preg_replace('/\D/', '', (string) ($payload['company_document'] ?? ''));
        $payload['owner_document'] = preg_replace('/\D/', '', (string) ($payload['owner_document'] ?? ''));

        $plans = collect($this->availableSelfServicePlans())->keyBy('code');
        $planCodes = $plans->keys()->all();

        $validated = validator($payload, [
            'company_name' => ['required', 'string', 'max:255'],
            'company_social_name' => ['required', 'string', 'max:255'],
            'company_document' => ['required', 'string', 'max:18', Rule::unique('empresa', 'cnpj_cpf'), new CpfCnpjValido()],
            'company_email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('empresa', 'email')],
            'company_phone' => ['required', 'string', 'max:20'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'owner_document' => ['required', 'string', 'regex:/^(\d{11}|\d{14})$/', Rule::unique('users', 'cpf')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'plan_code' => ['required', Rule::in($planCodes)],
        ])->validate();

        /** @var array{name:string,intervalo_cobranca_dias:int,valor_unitario:float,code:string} $plan */
        $plan = $plans->get($validated['plan_code']);
        $trialDays = max(0, (int) ($plan['trial_days'] ?? config('subscriptions.self_service.trial_days', 0)));

        $result = DB::transaction(function () use ($validated, $plan, $trialDays) {
            $empresa = Empresa::query()->create([
                'nome' => $validated['company_name'],
                'fantasia' => $validated['company_name'],
                'razaosocial' => $validated['company_social_name'],
                'cnpj_cpf' => $validated['company_document'],
                'email' => $validated['company_email'],
                'fone' => $validated['company_phone'],
                'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
                'revenda_id' => null,
                'cadastro_origem' => Empresa::CADASTRO_ORIGEM_SELF_SERVICE,
                'urlimagem' => '',
            ]);

            $user = User::query()->create([
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'cpf' => $validated['owner_document'],
                'empresa_id' => $empresa->id,
                'menu_permissions' => $this->resolveSelfServiceDefaultMenuPermissions(),
                'password' => Hash::make($validated['password']),
            ]);

            $this->provisionSelfServiceClient($empresa, [
                'id' => $plan['id'] ?? null,
                'code' => $plan['code'],
                'plan_name' => $plan['name'],
                'intervalo_cobranca_dias' => $plan['intervalo_cobranca_dias'],
                'valor_unitario' => $plan['valor_unitario'],
            ], [
                'trial_ends_at' => $trialDays > 0 ? now()->startOfDay()->addDays($trialDays) : null,
            ]);

            $this->applyDefaultSelfServiceWebScreenModel($empresa);

            return [
                'empresa' => $empresa->refresh(),
                'user' => $user->refresh(),
                'subscription' => $empresa->subscription()->first(),
                'financeiro_config' => $empresa->financeiroConfig()->first(),
            ];
        });

        $result['initial_charge'] = $this->createInitialSelfServiceCharge(
            $result['empresa'],
            $result['subscription'],
            $plan
        );

        return $result;
    }

    public function provisionManagedClient(Empresa $empresa): void
    {
        if (! $empresa->isClienteFinal()) {
            return;
        }

        EmpresaFinanceiroConfig::query()->firstOrCreate(
            ['empresa_id' => $empresa->id],
            [
                'valor_pagar_unitario' => 0,
                'valor_receber_unitario' => 0,
                'data_vencimento' => now()->startOfDay()->toDateString(),
                'data_aviso' => now()->startOfDay()->toDateString(),
                'data_bloqueio' => now()->startOfDay()->toDateString(),
                'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
                'cobranca_automatica_ativa' => false,
                'asaas_integration_ativa' => false,
                'bloquear_tv_inadimplencia' => false,
                'exibir_qr_code_tv_bloqueada' => false,
            ]
        );

        EmpresaSubscription::query()->firstOrCreate(
            ['empresa_id' => $empresa->id],
            [
                'status' => EmpresaSubscription::STATUS_ACTIVE,
                'starts_at' => now()->startOfDay()->toDateString(),
                'plan_name' => 'Cadastro gerenciado',
                'metadata' => ['cadastro_origem' => $empresa->cadastro_origem],
            ]
        );
    }

    public function provisionSelfServiceClient(Empresa $empresa, array $plan, array $overrides = []): void
    {
        if (! $empresa->isClienteFinal()) {
            return;
        }

        $startsAt = $this->resolveDate($overrides['starts_at'] ?? now()->startOfDay());
        $intervaloDias = $this->resolveBillingIntervalDays($plan['intervalo_cobranca_dias'] ?? null);
        $trialEndsAt = $this->resolveOptionalDate($overrides['trial_ends_at'] ?? null);
        $graceEndsAt = $this->resolveOptionalDate($overrides['grace_ends_at'] ?? null);
        $accessExpiresAt = $this->resolveOptionalDate($overrides['access_expires_at'] ?? null)
            ?? ($trialEndsAt ?: $startsAt->copy()->addDays($intervaloDias));

        EmpresaFinanceiroConfig::query()->updateOrCreate(
            ['empresa_id' => $empresa->id],
            [
                'valor_pagar_unitario' => round((float) ($plan['valor_unitario'] ?? 0), 2),
                'valor_receber_unitario' => round((float) ($plan['valor_unitario'] ?? 0), 2),
                'data_vencimento' => $this->resolveOptionalDate($overrides['data_vencimento'] ?? null)?->toDateString() ?? $accessExpiresAt->toDateString(),
                'data_aviso' => $this->resolveOptionalDate($overrides['data_aviso'] ?? null)?->toDateString() ?? $accessExpiresAt->toDateString(),
                'data_bloqueio' => $this->resolveOptionalDate($overrides['data_bloqueio'] ?? null)?->toDateString() ?? ($graceEndsAt?->toDateString() ?: $accessExpiresAt->toDateString()),
                'intervalo_cobranca_dias' => $intervaloDias,
                'cobranca_automatica_ativa' => (bool) ($overrides['cobranca_automatica_ativa'] ?? true),
                'asaas_integration_ativa' => (bool) ($overrides['asaas_integration_ativa'] ?? true),
                'bloquear_tv_inadimplencia' => (bool) ($overrides['bloquear_tv_inadimplencia'] ?? true),
                'exibir_qr_code_tv_bloqueada' => (bool) ($overrides['exibir_qr_code_tv_bloqueada'] ?? true),
            ]
        );

        EmpresaSubscription::query()->updateOrCreate(
            ['empresa_id' => $empresa->id],
            [
                'subscription_plan_id' => $plan['id'] ?? null,
                'status' => $this->resolveSubscriptionStatus($overrides['status'] ?? null, $trialEndsAt),
                'starts_at' => $startsAt->toDateString(),
                'trial_ends_at' => $trialEndsAt?->toDateString(),
                'access_expires_at' => $accessExpiresAt->toDateString(),
                'grace_ends_at' => $graceEndsAt?->toDateString(),
                'plan_name' => (string) ($plan['plan_name'] ?? 'Plano self-service'),
                'metadata' => [
                    'cadastro_origem' => $empresa->cadastro_origem,
                    'plan_code' => $plan['code'] ?? null,
                    'intervalo_cobranca_dias' => $intervaloDias,
                ],
            ]
        );
    }

    private function resolveBillingIntervalDays(mixed $intervaloDias): int
    {
        $intervalo = (int) ($intervaloDias ?: EmpresaFinanceiroConfig::INTERVALO_30_DIAS);

        return array_key_exists($intervalo, EmpresaFinanceiroConfig::billingIntervalOptions())
            ? $intervalo
            : EmpresaFinanceiroConfig::INTERVALO_30_DIAS;
    }

    private function resolveSubscriptionStatus(mixed $status, ?Carbon $trialEndsAt): string
    {
        $status = is_string($status) ? $status : null;

        if ($status && in_array($status, EmpresaSubscription::statuses(), true)) {
            return $status;
        }

        return $trialEndsAt ? EmpresaSubscription::STATUS_TRIAL : EmpresaSubscription::STATUS_ACTIVE;
    }

    private function resolveDate(mixed $value): Carbon
    {
        return $value instanceof Carbon
            ? $value->copy()->startOfDay()
            : Carbon::parse((string) $value)->startOfDay();
    }

    private function resolveOptionalDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->resolveDate($value);
    }

    private function createInitialSelfServiceCharge(Empresa $empresa, ?EmpresaSubscription $subscription, array $plan): ?EmpresaFinanceiroCobranca
    {
        $config = $empresa->financeiroConfig()->first();

        if (! $config || ! $config->asaas_integration_ativa || ! $this->asaasService->isConfigured($empresa)) {
            return null;
        }

        $dueDate = $subscription?->trial_ends_at
            ?? $subscription?->access_expires_at
            ?? now()->startOfDay()->addDays(max(0, (int) ($plan['trial_days'] ?? config('subscriptions.self_service.trial_days', 7))));

        try {
            $result = $this->financeiroChargeService->createPixChargeForEmpresa(
                $empresa,
                $this->asaasService,
                'Assinatura inicial '.$empresa->nome.' - '.(string) ($plan['name'] ?? 'Plano self-service'),
                $dueDate,
                1,
            );
        } catch (RuntimeException $exception) {
            Log::warning('Falha ao gerar cobranca inicial do self-service.', [
                'empresa_id' => $empresa->id,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        return $result['charge'] ?? null;
    }

    private function resolveSelfServiceDefaultMenuPermissions(): array
    {
        if (! Schema::hasColumn('configuracoes', 'selfServiceDefaultMenuPermissions')) {
            return User::defaultSelfServiceMenuPermissions();
        }

        $globalConfig = Configuracao::query()
            ->whereNull('empresa_id')
            ->first();

        if (! $globalConfig || $globalConfig->selfServiceDefaultMenuPermissions === null) {
            return User::defaultSelfServiceMenuPermissions();
        }

        return User::sanitizeMenuPermissions($globalConfig->selfServiceDefaultMenuPermissions);
    }

    private function applyDefaultSelfServiceWebScreenModel(Empresa $empresa): void
    {
        if (! Schema::hasColumn('configuracoes', 'selfServiceDefaultWebScreenModelId') || ! Schema::hasTable('web_screen_models')) {
            return;
        }

        $globalConfig = Configuracao::query()
            ->whereNull('empresa_id')
            ->first();

        $modelId = (int) ($globalConfig?->selfServiceDefaultWebScreenModelId ?? 0);
        if ($modelId <= 0) {
            return;
        }

        $model = WebScreenModel::query()
            ->where('id', $modelId)
            ->where('is_admin_default', true)
            ->first();

        if (! $model || ! is_array($model->config_payload) || $model->config_payload === []) {
            return;
        }

        $config = Configuracao::query()->firstOrCreate([
            'empresa_id' => $empresa->id,
        ], []);

        $fillable = array_flip((new Configuracao())->getFillable());

        foreach ($model->config_payload as $key => $value) {
            if (! is_string($key) || ! isset($fillable[$key]) || $key === 'empresa_id') {
                continue;
            }

            $config->setAttribute($key, $value);
        }

        $config->save();
    }
}