<?php

namespace App\Services;

use App\Models\Configuracao;
use App\Models\Empresa;
use App\Models\EmpresaFinanceiroCobranca;
use App\Models\EmpresaFinanceiroConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AsaasService
{
    public function isConfigured(?Empresa $empresa = null): bool
    {
        return $this->resolveCredentials($empresa)['api_key'] !== '';
    }

    public function webhookToken(?Empresa $empresa = null): ?string
    {
        $token = $this->resolveCredentials($empresa)['webhook_token'];

        return $token !== '' ? $token : null;
    }

    public function createPixCharge(Empresa $empresa, EmpresaFinanceiroConfig $config, EmpresaFinanceiroCobranca $cobranca): EmpresaFinanceiroCobranca
    {
        $credentials = $this->resolveCredentials($empresa);
        $customerId = $this->ensureCustomer($empresa, $config);

        $payment = $this->send('post', '/payments', [
            'customer' => $customerId,
            'billingType' => 'PIX',
            'value' => (float) $cobranca->valor_total,
            'dueDate' => optional($cobranca->vencimento)->format('Y-m-d'),
            'description' => $cobranca->descricao,
            'externalReference' => $cobranca->external_reference,
        ], [], $credentials);

        // The payment can be created before Asaas makes the PIX QR payload available.
        // In that case we still persist the charge and let sync/webhook fill the QR fields later.
        $qrCode = $this->fetchPixQrCode((string) Arr::get($payment, 'id'), false, $credentials);

        return $this->fillChargeFromGateway($cobranca, $payment, $qrCode);
    }

    public function syncCharge(EmpresaFinanceiroCobranca $cobranca): EmpresaFinanceiroCobranca
    {
        $empresa = $cobranca->relationLoaded('empresa') ? $cobranca->empresa : $cobranca->empresa()->first();
        $credentials = $this->resolveCredentials($empresa);

        if (! $cobranca->gateway_payment_id) {
            throw new RuntimeException('A cobranca ainda nao possui identificador no Asaas.');
        }

        try {
            $payment = $this->send('get', '/payments/'.$cobranca->gateway_payment_id, [], [], $credentials);
        } catch (RuntimeException $exception) {
            if ($this->shouldMarkChargeAsDeleted($exception->getMessage())) {
                return $this->markChargeAsDeleted($cobranca, ['error' => $exception->getMessage()]);
            }

            throw $exception;
        }

        if ((bool) Arr::get($payment, 'deleted')) {
            return $this->markChargeAsDeleted($cobranca, $payment);
        }

        $qrCode = null;

        if (in_array((string) Arr::get($payment, 'billingType'), ['PIX', 'UNDEFINED', 'BOLETO'], true)) {
            $qrCode = $this->fetchPixQrCode((string) $cobranca->gateway_payment_id, false, $credentials);
        }

        return $this->fillChargeFromGateway($cobranca, $payment, $qrCode);
    }

    public function handleWebhook(array $payload, ?string $receivedToken = null): ?EmpresaFinanceiroCobranca
    {
        $payment = Arr::get($payload, 'payment', []);
        $paymentId = (string) Arr::get($payment, 'id', '');
        $externalReference = (string) Arr::get($payment, 'externalReference', '');

        if ($paymentId === '' && $externalReference === '') {
            return null;
        }

        $cobranca = EmpresaFinanceiroCobranca::query()
            ->when($paymentId !== '', fn ($query) => $query->where('gateway_payment_id', $paymentId))
            ->when($paymentId === '' && $externalReference !== '', fn ($query) => $query->where('external_reference', $externalReference))
            ->latest('id')
            ->first();

        if (! $cobranca) {
            return null;
        }

        $empresa = $cobranca->relationLoaded('empresa') ? $cobranca->empresa : $cobranca->empresa()->first();
        $configuredToken = $this->webhookToken($empresa);

        if ($configuredToken === null) {
            throw new RuntimeException('Webhook do Asaas nao configurado para esta conta.');
        }

        if ($receivedToken !== null && ! hash_equals($configuredToken, $receivedToken)) {
            throw new RuntimeException('Token de webhook invalido.');
        }

        $qrCode = null;
        if (in_array((string) Arr::get($payment, 'billingType'), ['PIX', 'UNDEFINED', 'BOLETO'], true) && $paymentId !== '') {
            $qrCode = $this->fetchPixQrCode($paymentId, false, $this->resolveCredentials($empresa));
        }

        return $this->fillChargeFromGateway($cobranca, $payment, $qrCode);
    }

    public function ensureCustomer(Empresa $empresa, EmpresaFinanceiroConfig $config): string
    {
        $credentials = $this->resolveCredentials($empresa);
        $this->ensureConfigured($credentials);

        $payload = $this->buildCustomerPayload($empresa);

        if ($config->asaas_customer_id) {
            try {
                $this->send('put', '/customers/'.$config->asaas_customer_id, $payload, [], $credentials);

                return (string) $config->asaas_customer_id;
            } catch (RuntimeException $exception) {
                $config->asaas_customer_id = null;
                $config->save();
            }
        }

        $existing = $this->send('get', '/customers', [], [
            'externalReference' => 'empresa:'.$empresa->id,
            'limit' => 1,
        ], $credentials);

        $existingCustomer = Arr::first((array) Arr::get($existing, 'data', []));
        if (is_array($existingCustomer) && Arr::get($existingCustomer, 'id')) {
            $config->asaas_customer_id = (string) Arr::get($existingCustomer, 'id');
            $config->save();

            return (string) $config->asaas_customer_id;
        }

        $created = $this->send('post', '/customers', $payload, [], $credentials);
        $config->asaas_customer_id = (string) Arr::get($created, 'id');
        $config->save();

        return (string) $config->asaas_customer_id;
    }

    private function buildCustomerPayload(Empresa $empresa): array
    {
        $name = trim((string) ($empresa->nome ?: $empresa->fantasia ?: $empresa->razaosocial));
        $cpfCnpj = preg_replace('/\D/', '', (string) $empresa->cnpj_cpf);

        if ($name === '' || $cpfCnpj === '') {
            throw new RuntimeException('A empresa precisa ter nome e CPF/CNPJ para gerar cobranca PIX.');
        }

        return array_filter([
            'name' => $name,
            'cpfCnpj' => $cpfCnpj,
            'email' => $empresa->email,
            'mobilePhone' => preg_replace('/\D/', '', (string) $empresa->fone),
            'address' => $empresa->endereco,
            'addressNumber' => $empresa->numero,
            'province' => $empresa->bairro,
            'postalCode' => preg_replace('/\D/', '', (string) $empresa->cep),
            'externalReference' => 'empresa:'.$empresa->id,
            'notificationDisabled' => true,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function fetchPixQrCode(string $paymentId, bool $raiseOnError = true, array $credentials = []): ?array
    {
        if ($paymentId === '') {
            return null;
        }

        try {
            return $this->send('get', '/payments/'.$paymentId.'/pixQrCode', [], [], $credentials);
        } catch (RuntimeException $exception) {
            if ($raiseOnError) {
                throw $exception;
            }

            return null;
        }
    }

    private function fillChargeFromGateway(EmpresaFinanceiroCobranca $cobranca, array $payment, ?array $qrCode = null): EmpresaFinanceiroCobranca
    {
        $status = strtoupper((string) Arr::get($payment, 'status', $cobranca->status ?: 'PENDING'));
        $paidAt = $this->resolvePaidAt($status, $payment);

        $cobranca->fill([
            'gateway' => 'asaas',
            'gateway_customer_id' => (string) Arr::get($payment, 'customer', $cobranca->gateway_customer_id),
            'gateway_payment_id' => (string) Arr::get($payment, 'id', $cobranca->gateway_payment_id),
            'status' => $status,
            'invoice_url' => Arr::get($payment, 'invoiceUrl', $cobranca->invoice_url),
            'external_reference' => (string) Arr::get($payment, 'externalReference', $cobranca->external_reference),
            'valor_total' => (float) Arr::get($payment, 'value', $cobranca->valor_total),
            'vencimento' => $this->parseDate(Arr::get($payment, 'dueDate'), $cobranca->vencimento),
            'paid_at' => $paidAt,
            'last_gateway_sync_at' => now(),
            'gateway_payload' => $payment,
        ]);

        if (is_array($qrCode)) {
            $cobranca->pix_qr_code = Arr::get($qrCode, 'encodedImage', $cobranca->pix_qr_code);
            $cobranca->pix_copy_paste = Arr::get($qrCode, 'payload', $cobranca->pix_copy_paste);
            $cobranca->pix_expires_at = $this->parseDateTime(Arr::get($qrCode, 'expirationDate'), $cobranca->pix_expires_at);
        }

        $cobranca->save();

        return $cobranca->fresh();
    }

    private function markChargeAsDeleted(EmpresaFinanceiroCobranca $cobranca, array $payload = []): EmpresaFinanceiroCobranca
    {
        $cobranca->fill([
            'status' => 'DELETED',
            'paid_at' => null,
            'last_gateway_sync_at' => now(),
            'gateway_payload' => $payload !== [] ? $payload : $cobranca->gateway_payload,
        ]);

        $cobranca->save();

        return $cobranca->fresh();
    }

    private function shouldMarkChargeAsDeleted(string $message): bool
    {
        $normalized = mb_strtolower(trim($message));

        foreach ([
            'cobranca nao encontrada',
            'cobrança não encontrada',
            'payment not found',
            'not found',
            'nao existe',
            'não existe',
            'deleted',
            'remov',
        ] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function resolvePaidAt(string $status, array $payment): ?Carbon
    {
        if (! in_array($status, ['RECEIVED', 'RECEIVED_IN_CASH', 'CONFIRMED'], true)) {
            return null;
        }

        return $this->parseDateTime(Arr::get($payment, 'clientPaymentDate') ?: Arr::get($payment, 'paymentDate'));
    }

    private function parseDate(mixed $value, mixed $fallback = null): mixed
    {
        if (! $value) {
            return $fallback;
        }

        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function parseDateTime(mixed $value, mixed $fallback = null): mixed
    {
        if (! $value) {
            return $fallback;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function send(string $method, string $uri, array $data = [], array $query = [], array $credentials = []): array
    {
        $this->ensureConfigured($credentials);

        $request = $this->client($credentials);

        $response = match (strtolower($method)) {
            'get' => $request->get($uri, $query),
            'put' => $request->put($uri, $data),
            'post' => $request->post($uri, $data),
            default => throw new RuntimeException('Metodo HTTP nao suportado para integracao Asaas.'),
        };

        return $this->decodeResponse($response);
    }

    private function decodeResponse(Response $response): array
    {
        if ($response->successful()) {
            return $response->json() ?: [];
        }

        $errors = Arr::get($response->json(), 'errors', []);
        $message = Arr::get(Arr::first((array) $errors), 'description')
            ?: Arr::get($response->json(), 'message')
            ?: 'Falha ao comunicar com o Asaas.';

        throw new RuntimeException($message);
    }

    private function client(array $credentials = []): PendingRequest
    {
        return Http::baseUrl((string) ($credentials['base_url'] ?? config('services.asaas.base_url')))
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'access_token' => (string) ($credentials['api_key'] ?? config('services.asaas.api_key')),
                'User-Agent' => 'ApiFinanceiro/1.0',
            ])
            ->timeout(20);
    }

    private function ensureConfigured(array $credentials = []): void
    {
        if (($credentials['api_key'] ?? '') === '') {
            throw new RuntimeException('Asaas nao configurado. Defina ASAAS_API_KEY no ambiente.');
        }
    }

    private function resolveCredentials(?Empresa $empresa = null): array
    {
        $scopeEmpresaId = $this->resolveConfigScopeEmpresaId($empresa);
        $config = Configuracao::query()
            ->when($scopeEmpresaId === null, fn ($query) => $query->whereNull('empresa_id'))
            ->when($scopeEmpresaId !== null, fn ($query) => $query->where('empresa_id', $scopeEmpresaId))
            ->first();

        return [
            'base_url' => trim((string) ($config?->asaasBaseUrl ?: config('services.asaas.base_url', ''))),
            'api_key' => trim((string) ($config?->asaasApiKey ?: config('services.asaas.api_key', ''))),
            'webhook_token' => trim((string) ($config?->asaasWebhookToken ?: config('services.asaas.webhook_token', ''))),
        ];
    }

    private function resolveConfigScopeEmpresaId(?Empresa $empresa = null): ?int
    {
        if (! $empresa) {
            return null;
        }

        if ((int) $empresa->nivel_acesso === Empresa::NIVEL_CLIENTE_FINAL && $empresa->revenda_id) {
            return (int) $empresa->revenda_id;
        }

        if ((int) $empresa->nivel_acesso === Empresa::NIVEL_REVENDA) {
            return (int) $empresa->id;
        }

        return null;
    }
}