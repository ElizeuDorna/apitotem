<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Empresa;
use App\Models\EmpresaFinanceiroCobranca;
use App\Models\EmpresaFinanceiroConfig;
use App\Services\AsaasService;
use App\Services\FinanceiroChargeService;
use App\Support\EmpresaContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class FinanceiroController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        [$isAdmin, $isRevenda, $isClienteFinal] = $this->resolveProfileFlags();

        $nivelSelecionado = 'cliente_final';
        $revendaSelecionadaId = 0;
        $revendaSelecionadaNome = null;

        if ($isAdmin) {
            $nivel = (string) $request->query('nivel', 'cliente_final');
            $nivelSelecionado = in_array($nivel, ['cliente_final', 'revenda'], true) ? $nivel : 'cliente_final';
            $revendaSelecionadaId = max(0, (int) $request->query('revenda_id', 0));
        } elseif ($isRevenda) {
            $nivelSelecionado = 'cliente_final';
            $revendaSelecionadaId = (int) ($user?->empresa?->id ?? 0);
        }

        if ($isAdmin && $nivelSelecionado === 'revenda' && $revendaSelecionadaId <= 0) {
            $linhas = $this->buildRevendaSummaryRows();
        } elseif ($isAdmin && $nivelSelecionado === 'revenda' && $revendaSelecionadaId > 0) {
            $linhas = $this->buildClientesByRevendaRows($revendaSelecionadaId);
            $revendaSelecionadaNome = Empresa::query()->where('id', $revendaSelecionadaId)->value('nome');
        } elseif ($isAdmin) {
            $linhas = $this->buildClienteFinalRows();
        } elseif ($isRevenda) {
            $linhas = $this->buildClientesByRevendaRows($revendaSelecionadaId);
        } else {
            $linhas = $this->buildClienteRows();
        }

        $resumoRevenda = null;
        if ($isRevenda) {
            $resumoRevenda = $this->buildResumoRevenda((int) ($user?->empresa?->id ?? 0));
        }

        $linhas = $linhas->map(function (array $linha) use ($isAdmin, $nivelSelecionado, $revendaSelecionadaId, $request) {
            $empresa = $linha['empresa'];

            if ($isAdmin && $nivelSelecionado === 'revenda' && $revendaSelecionadaId <= 0) {
                $linha['click_url'] = route('admin.financeiro.index', array_merge($request->query(), [
                    'nivel' => 'revenda',
                    'revenda_id' => $empresa->id,
                ]));
            } else {
                $linha['click_url'] = route('admin.financeiro.show', $empresa->id);
            }

            return $linha;
        });

        return view('admin.financeiro.index', [
            'linhas' => $linhas,
            'isAdmin' => $isAdmin,
            'isRevenda' => $isRevenda,
            'isClienteFinal' => $isClienteFinal,
            'nivelSelecionado' => $nivelSelecionado,
            'revendaSelecionadaId' => $revendaSelecionadaId,
            'revendaSelecionadaNome' => $revendaSelecionadaNome,
            'resumoRevenda' => $resumoRevenda,
        ]);
    }

    public function show(Empresa $empresa, AsaasService $asaas, FinanceiroChargeService $financeiroChargeService): View
    {
        [$isAdmin, $isRevenda, $isClienteFinal] = $this->resolveProfileFlags();

        $this->authorizeEmpresaFinanceiroAccess($empresa, $isAdmin, $isRevenda, $isClienteFinal);

        $config = EmpresaFinanceiroConfig::query()->firstOrCreate(
            ['empresa_id' => $empresa->id],
            [
                'valor_pagar_unitario' => 0,
                'valor_receber_unitario' => 0,
                'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
                'cobranca_automatica_ativa' => false,
                'asaas_integration_ativa' => true,
                'bloquear_tv_inadimplencia' => false,
                'exibir_qr_code_tv_bloqueada' => false,
                'asaas_customer_id' => null,
            ]
        );

        $isEmpresaRevenda = (int) $empresa->nivel_acesso === Empresa::NIVEL_REVENDA;
        $quantidadeDispositivos = $isEmpresaRevenda
            ? $this->countDevicesByRevenda((int) $empresa->id)
            : $this->countActiveDevicesForEmpresa((int) $empresa->id);
        $valorUnitario = (float) ($config->valor_receber_unitario ?? 0);
        $cobrancas = EmpresaFinanceiroCobranca::query()
            ->where('empresa_id', $empresa->id)
            ->latest('vencimento')
            ->latest('id')
            ->get();

        $cobrancaAberta = $cobrancas->first(fn (EmpresaFinanceiroCobranca $cobranca) => $cobranca->isAwaitingPayment());

        if ($cobrancaAberta && $cobrancaAberta->gateway_payment_id && $config->asaas_integration_ativa) {
            try {
                $cobrancaSincronizada = $asaas->syncCharge($cobrancaAberta);
                $cobrancas = $cobrancas->map(fn (EmpresaFinanceiroCobranca $cobranca) => $cobranca->id === $cobrancaSincronizada->id ? $cobrancaSincronizada : $cobranca);
            } catch (RuntimeException) {
                // Keep the page render resilient even if the gateway sync fails.
            }

            $cobrancaAberta = $cobrancas->first(fn (EmpresaFinanceiroCobranca $cobranca) => $cobranca->isAwaitingPayment());
        }

        return view('admin.financeiro.show', [
            'empresa' => $empresa,
            'config' => $config,
            'quantidadeDispositivos' => $quantidadeDispositivos,
            'totalPagar' => $quantidadeDispositivos * $valorUnitario,
            'totalReceber' => $quantidadeDispositivos * $valorUnitario,
            'isAdmin' => $isAdmin,
            'isRevenda' => $isRevenda,
            'isClienteFinal' => $isClienteFinal,
            'isEmpresaRevenda' => $isEmpresaRevenda,
            'cobrancas' => $cobrancas,
            'cobrancaAberta' => $cobrancaAberta,
            'canCreatePixCharge' => $this->canManageEmpresaCobranca($empresa, $isAdmin, $isRevenda),
            'asaasConfigured' => $asaas->isConfigured($empresa),
            'billingIntervalOptions' => EmpresaFinanceiroConfig::billingIntervalOptions(),
            'billingIntervalLabel' => $config->billingIntervalLabel(),
            'suggestedChargeDueDate' => $financeiroChargeService->resolveSuggestedChargeDueDate($empresa, $config)->format('Y-m-d'),
        ]);
    }

    public function update(Request $request, Empresa $empresa): RedirectResponse
    {
        [$isAdmin, $isRevenda, $isClienteFinal] = $this->resolveProfileFlags();

        $this->authorizeEmpresaFinanceiroAccess($empresa, $isAdmin, $isRevenda, $isClienteFinal);

        abort_unless($isAdmin || $isRevenda, 403, 'Sem permissão para alterar valores financeiros.');
        if ($isAdmin && (int) $empresa->nivel_acesso === Empresa::NIVEL_CLIENTE_FINAL && $empresa->revenda_id !== null) {
            abort(403, 'Admin pode visualizar, mas a configuracao deste cliente e gerida pela revenda vinculada.');
        }

        $validated = $request->validate([
            'valor_receber_unitario' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'data_vencimento' => ['required', 'date'],
            'data_aviso' => ['required', 'date'],
            'data_bloqueio' => ['required', 'date', 'after_or_equal:data_aviso'],
            'intervalo_cobranca_dias' => ['required', 'integer', Rule::in(array_keys(EmpresaFinanceiroConfig::billingIntervalOptions()))],
            'cobranca_automatica_ativa' => ['nullable', 'boolean'],
            'asaas_integration_ativa' => ['nullable', 'boolean'],
            'bloquear_tv_inadimplencia' => ['nullable', 'boolean'],
            'exibir_qr_code_tv_bloqueada' => ['nullable', 'boolean'],
        ]);

        $config = EmpresaFinanceiroConfig::query()->firstOrCreate(
            ['empresa_id' => $empresa->id],
            [
                'valor_receber_unitario' => 0,
                'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
                'cobranca_automatica_ativa' => false,
                'asaas_integration_ativa' => true,
                'bloquear_tv_inadimplencia' => false,
                'exibir_qr_code_tv_bloqueada' => false,
            ]
        );

        $config->valor_pagar_unitario = (float) $validated['valor_receber_unitario'];
        $config->valor_receber_unitario = (float) $validated['valor_receber_unitario'];
        $config->data_vencimento = $validated['data_vencimento'];
        $config->data_aviso = $validated['data_aviso'];
        $config->data_bloqueio = $validated['data_bloqueio'];
        $config->intervalo_cobranca_dias = (int) $validated['intervalo_cobranca_dias'];
        $config->cobranca_automatica_ativa = $request->boolean('cobranca_automatica_ativa');
        $config->asaas_integration_ativa = $request->boolean('asaas_integration_ativa');
        $config->bloquear_tv_inadimplencia = $request->boolean('bloquear_tv_inadimplencia');
        $config->exibir_qr_code_tv_bloqueada = $request->boolean('exibir_qr_code_tv_bloqueada');
        $config->save();

        return redirect()->route('admin.financeiro.show', $empresa)->with('success', 'Valores financeiros atualizados com sucesso.');
    }

    public function storePixCharge(Request $request, Empresa $empresa, AsaasService $asaas, FinanceiroChargeService $financeiroChargeService): RedirectResponse
    {
        [$isAdmin, $isRevenda, $isClienteFinal] = $this->resolveProfileFlags();
        $this->authorizeEmpresaFinanceiroAccess($empresa, $isAdmin, $isRevenda, $isClienteFinal);

        abort_unless($this->canManageEmpresaCobranca($empresa, $isAdmin, $isRevenda), 403, 'Sem permissao para gerar cobranca PIX para esta empresa.');
        abort_unless((int) $empresa->nivel_acesso === Empresa::NIVEL_CLIENTE_FINAL, 422, 'A cobranca PIX inicial esta disponivel apenas para cliente final.');

        $validated = $request->validate([
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $financeiroChargeService->createPixChargeForEmpresa(
                $empresa,
                $asaas,
                $validated['description'] ?? null,
                $validated['due_date'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('admin.financeiro.show', $empresa)
                ->withErrors(['asaas' => $exception->getMessage()]);
        }

        if ($result['status'] === 'processing') {
            return redirect()
                ->route('admin.financeiro.show', $empresa)
                ->with('success', 'Ja existe uma cobranca PIX em processamento. Aguarde alguns instantes antes de tentar novamente.');
        }

        if ($result['status'] === 'existing') {
            $existingCharge = $result['charge'];

            if (! $existingCharge->gateway_payment_id) {
                return redirect()
                    ->route('admin.financeiro.show', $empresa)
                    ->with('success', 'Ja existe uma cobranca PIX em processamento. Aguarde alguns instantes antes de tentar novamente.');
            }

            try {
                $asaas->syncCharge($existingCharge);
            } catch (RuntimeException $exception) {
                return redirect()
                    ->route('admin.financeiro.show', $empresa)
                    ->withErrors(['asaas' => $exception->getMessage()]);
            }

            return redirect()
                ->route('admin.financeiro.show', $empresa)
                ->with('success', 'Ja existe uma cobranca PIX em aberto. O status foi atualizado.');
        }

        return redirect()->route('admin.financeiro.show', $empresa)->with('success', 'Cobranca PIX gerada com sucesso.');
    }

    public function syncCharge(EmpresaFinanceiroCobranca $cobranca, AsaasService $asaas): RedirectResponse
    {
        [$isAdmin, $isRevenda, $isClienteFinal] = $this->resolveProfileFlags();
        $this->authorizeEmpresaFinanceiroAccess($cobranca->empresa, $isAdmin, $isRevenda, $isClienteFinal);

        if (! optional($cobranca->empresa->financeiroConfig)->asaas_integration_ativa) {
            return redirect()
                ->route('admin.financeiro.show', $cobranca->empresa)
                ->withErrors(['asaas' => 'A integracao com o Asaas nao esta ativada para esta empresa.']);
        }

        try {
            $asaas->syncCharge($cobranca);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('admin.financeiro.show', $cobranca->empresa)
                ->withErrors(['asaas' => $exception->getMessage()]);
        }

        return redirect()->route('admin.financeiro.show', $cobranca->empresa)->with('success', 'Status da cobranca atualizado.');
    }

    private function resolveProfileFlags(): array
    {
        $user = Auth::user();
        $empresaUsuario = $user?->empresa;

        $isAdmin = (bool) ($user && $user->isDefaultAdmin());
        $isRevenda = (bool) (! $isAdmin && $empresaUsuario && $empresaUsuario->isRevenda());
        $isClienteFinal = ! $isAdmin && ! $isRevenda;

        return [$isAdmin, $isRevenda, $isClienteFinal];
    }

    private function authorizeEmpresaFinanceiroAccess(Empresa $empresa, bool $isAdmin, bool $isRevenda, bool $isClienteFinal): void
    {
        $isEmpresaClienteFinal = (int) $empresa->nivel_acesso === Empresa::NIVEL_CLIENTE_FINAL;
        $isEmpresaRevenda = (int) $empresa->nivel_acesso === Empresa::NIVEL_REVENDA;

        abort_unless($isEmpresaClienteFinal || $isEmpresaRevenda, 422, 'Somente cliente final ou revenda pode ter conta financeira.');

        $user = Auth::user();
        $empresaUsuario = $user?->empresa;

        if ($isAdmin) {
            return;
        }

        if ($isRevenda) {
            abort_unless($isEmpresaClienteFinal, 403, 'Revenda só pode acessar financeiro dos clientes finais da sua carteira.');
            abort_unless((int) $empresa->revenda_id === (int) $empresaUsuario->id, 403, 'Você só pode acessar clientes da sua carteira.');
            return;
        }

        if ($isClienteFinal) {
            $empresaIdCliente = EmpresaContext::requireEmpresaId($user);
            abort_unless((int) $empresa->id === (int) $empresaIdCliente, 403, 'Você só pode visualizar os dados da sua empresa.');
        }
    }

    private function buildClienteFinalRows()
    {
        $clientes = Empresa::query()
            ->where('nivel_acesso', Empresa::NIVEL_CLIENTE_FINAL)
            ->whereNull('revenda_id')
            ->orderBy('nome')
            ->get(['id', 'nome', 'cnpj_cpf', 'nivel_acesso']);

        $clienteIds = $clientes->pluck('id')->all();
        $configs = EmpresaFinanceiroConfig::query()->whereIn('empresa_id', $clienteIds)->get()->keyBy('empresa_id');
        $devices = $this->countActiveDevicesForEmpresas($clienteIds);

        return $clientes->map(function (Empresa $cliente) use ($configs, $devices) {
            $qtd = (int) ($devices[$cliente->id] ?? 0);
            $unit = (float) ($configs[$cliente->id]->valor_receber_unitario ?? 0);

            return [
                'empresa' => $cliente,
                'quantidade_dispositivos' => $qtd,
                'valor_total' => $qtd * $unit,
            ];
        })->values();
    }

    private function buildRevendaSummaryRows()
    {
        $revendas = Empresa::query()
            ->where('nivel_acesso', Empresa::NIVEL_REVENDA)
            ->orderBy('nome')
            ->get(['id', 'nome', 'cnpj_cpf', 'nivel_acesso']);

        $revendaIds = $revendas->pluck('id')->all();
        $configs = EmpresaFinanceiroConfig::query()->whereIn('empresa_id', $revendaIds)->get()->keyBy('empresa_id');

        $devicesPorRevenda = Device::query()
            ->join('empresa as clientes', 'clientes.id', '=', 'devices.empresa_id')
            ->where('devices.ativo', true)
            ->where('clientes.nivel_acesso', Empresa::NIVEL_CLIENTE_FINAL)
            ->whereNotNull('clientes.revenda_id')
            ->groupBy('clientes.revenda_id')
            ->selectRaw('clientes.revenda_id as revenda_id, COUNT(devices.id) as total')
            ->pluck('total', 'revenda_id');

        return $revendas->map(function (Empresa $revenda) use ($configs, $devicesPorRevenda) {
            $qtd = (int) ($devicesPorRevenda[$revenda->id] ?? 0);
            $unit = (float) ($configs[$revenda->id]->valor_receber_unitario ?? 0);

            return [
                'empresa' => $revenda,
                'quantidade_dispositivos' => $qtd,
                'valor_total' => $qtd * $unit,
            ];
        })->values();
    }

    private function buildClientesByRevendaRows(int $revendaId)
    {
        $clientes = Empresa::query()
            ->where('nivel_acesso', Empresa::NIVEL_CLIENTE_FINAL)
            ->where('revenda_id', $revendaId)
            ->orderBy('nome')
            ->get(['id', 'nome', 'cnpj_cpf', 'nivel_acesso']);

        $clienteIds = $clientes->pluck('id')->all();
        $configs = EmpresaFinanceiroConfig::query()->whereIn('empresa_id', $clienteIds)->get()->keyBy('empresa_id');
        $devices = $this->countActiveDevicesForEmpresas($clienteIds);

        return $clientes->map(function (Empresa $cliente) use ($configs, $devices) {
            $qtd = (int) ($devices[$cliente->id] ?? 0);
            $unit = (float) ($configs[$cliente->id]->valor_receber_unitario ?? 0);

            return [
                'empresa' => $cliente,
                'quantidade_dispositivos' => $qtd,
                'valor_total' => $qtd * $unit,
            ];
        })->values();
    }

    private function buildClienteRows()
    {
        $user = Auth::user();
        $empresaId = EmpresaContext::requireEmpresaId($user);

        $empresa = Empresa::query()->findOrFail($empresaId, ['id', 'nome', 'cnpj_cpf', 'nivel_acesso']);
        $config = EmpresaFinanceiroConfig::query()->where('empresa_id', $empresa->id)->first();
        $qtd = $this->countActiveDevicesForEmpresa((int) $empresa->id);
        $unit = (float) ($config?->valor_receber_unitario ?? 0);

        return collect([
            [
                'empresa' => $empresa,
                'quantidade_dispositivos' => $qtd,
                'valor_total' => $qtd * $unit,
            ],
        ]);
    }

    private function buildResumoRevenda(int $revendaId): array
    {
        $qtdCarteira = $this->countDevicesByRevenda($revendaId);

        $configRevenda = EmpresaFinanceiroConfig::query()->where('empresa_id', $revendaId)->first();
        $valorUnitarioPagarAdmin = (float) ($configRevenda?->valor_receber_unitario ?? 0);
        $totalPagarAdmin = $qtdCarteira * $valorUnitarioPagarAdmin;

        $clientes = Empresa::query()
            ->where('nivel_acesso', Empresa::NIVEL_CLIENTE_FINAL)
            ->where('revenda_id', $revendaId)
            ->get(['id']);

        $clienteIds = $clientes->pluck('id')->all();
        $configsClientes = EmpresaFinanceiroConfig::query()
            ->whereIn('empresa_id', $clienteIds)
            ->get()
            ->keyBy('empresa_id');

        $devicesClientes = $this->countActiveDevicesForEmpresas($clienteIds);

        $totalReceberClientes = 0.0;
        foreach ($clienteIds as $clienteId) {
            $qtd = (int) ($devicesClientes[$clienteId] ?? 0);
            $unit = (float) ($configsClientes[$clienteId]->valor_receber_unitario ?? 0);
            $totalReceberClientes += $qtd * $unit;
        }

        return [
            'quantidade_dispositivos' => $qtdCarteira,
            'valor_unitario_pagar_admin' => $valorUnitarioPagarAdmin,
            'total_pagar_admin' => $totalPagarAdmin,
            'total_receber_clientes' => $totalReceberClientes,
        ];
    }

    private function countDevicesByRevenda(int $revendaId): int
    {
        return (int) Device::query()
            ->join('empresa as clientes', 'clientes.id', '=', 'devices.empresa_id')
            ->where('devices.ativo', true)
            ->where('clientes.nivel_acesso', Empresa::NIVEL_CLIENTE_FINAL)
            ->where('clientes.revenda_id', $revendaId)
            ->count('devices.id');
    }

    private function countActiveDevicesForEmpresa(int $empresaId): int
    {
        return (int) Device::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->count();
    }

    private function countActiveDevicesForEmpresas(array $empresaIds)
    {
        if ($empresaIds === []) {
            return collect();
        }

        return Device::query()
            ->selectRaw('empresa_id, COUNT(*) as total')
            ->whereIn('empresa_id', $empresaIds)
            ->where('ativo', true)
            ->groupBy('empresa_id')
            ->pluck('total', 'empresa_id');
    }

    private function canManageEmpresaCobranca(Empresa $empresa, bool $isAdmin, bool $isRevenda): bool
    {
        if ((int) $empresa->nivel_acesso !== Empresa::NIVEL_CLIENTE_FINAL) {
            return false;
        }

        if ($isRevenda) {
            return (int) $empresa->revenda_id === (int) (Auth::user()?->empresa?->id ?? 0);
        }

        if ($isAdmin) {
            return $empresa->revenda_id === null;
        }

        return false;
    }
}
