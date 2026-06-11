<?php

namespace App\Livewire\Admin;

use App\Models\Empresa;
use App\Models\EmpresaFinanceiroConfig;
use App\Support\EmpresaContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class FinanceiroShowPanel extends Component
{
    public Empresa $empresa;

    public EmpresaFinanceiroConfig $config;

    public $quantidadeDispositivos;

    public $totalPagar;

    public $totalReceber;

    public $isAdmin;

    public $isRevenda;

    public $isClienteFinal;

    public $isEmpresaRevenda;

    public $cobrancas;

    public $cobrancaAberta;

    public $canCreatePixCharge;

    public $asaasConfigured;

    public $billingIntervalOptions;

    public $billingIntervalLabel;

    public $suggestedChargeDueDate;

    public $valorReceberUnitario;

    public $dataVencimento;

    public $dataAviso;

    public $dataBloqueio;

    public $intervaloCobrancaDias;

    public int $mesesCobranca = 1;

    public bool $cobrancaAutomaticaAtiva = false;

    public bool $asaasIntegrationAtiva = false;

    public bool $bloquearTvInadimplencia = false;

    public bool $exibirQrCodeTvBloqueada = false;

    public ?string $successMessage = null;

    public int $successToastKey = 0;

    public function mount(
        Empresa $empresa,
        EmpresaFinanceiroConfig $config,
        $quantidadeDispositivos,
        $totalPagar,
        $totalReceber,
        $isAdmin,
        $isRevenda,
        $isClienteFinal,
        $isEmpresaRevenda,
        $cobrancas,
        $cobrancaAberta,
        $canCreatePixCharge,
        $asaasConfigured,
        $billingIntervalOptions,
        $billingIntervalLabel,
        $suggestedChargeDueDate
    ): void {
        $this->empresa = $empresa;
        $this->config = $config;
        $this->quantidadeDispositivos = $quantidadeDispositivos;
        $this->totalPagar = $totalPagar;
        $this->totalReceber = $totalReceber;
        $this->isAdmin = $isAdmin;
        $this->isRevenda = $isRevenda;
        $this->isClienteFinal = $isClienteFinal;
        $this->isEmpresaRevenda = $isEmpresaRevenda;
        $this->cobrancas = $cobrancas;
        $this->cobrancaAberta = $cobrancaAberta;
        $this->canCreatePixCharge = $canCreatePixCharge;
        $this->asaasConfigured = $asaasConfigured;
        $this->billingIntervalOptions = $billingIntervalOptions;
        $this->billingIntervalLabel = $billingIntervalLabel;
        $this->suggestedChargeDueDate = $suggestedChargeDueDate;
        $this->valorReceberUnitario = $config->valor_receber_unitario;
        $this->dataVencimento = optional($config->data_vencimento)->format('Y-m-d');
        $this->dataAviso = optional($config->data_aviso)->format('Y-m-d');
        $this->dataBloqueio = optional($config->data_bloqueio)->format('Y-m-d');
        $this->intervaloCobrancaDias = $config->billingIntervalDays();
        $this->mesesCobranca = $config->billingCycleMonths();
        $this->cobrancaAutomaticaAtiva = (bool) $config->cobranca_automatica_ativa;
        $this->asaasIntegrationAtiva = (bool) $config->asaas_integration_ativa;
        $this->bloquearTvInadimplencia = (bool) $config->bloquear_tv_inadimplencia;
        $this->exibirQrCodeTvBloqueada = (bool) $config->exibir_qr_code_tv_bloqueada;
        $this->refreshTotals();
    }

    protected function rules(): array
    {
        return [
            'valorReceberUnitario' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'dataVencimento' => ['required', 'date'],
            'dataAviso' => ['required', 'date'],
            'dataBloqueio' => ['required', 'date', 'after_or_equal:dataAviso'],
            'intervaloCobrancaDias' => ['required', 'integer', Rule::in(array_keys(EmpresaFinanceiroConfig::billingIntervalOptions()))],
            'cobrancaAutomaticaAtiva' => ['boolean'],
            'asaasIntegrationAtiva' => ['boolean'],
            'bloquearTvInadimplencia' => ['boolean'],
            'exibirQrCodeTvBloqueada' => ['boolean'],
        ];
    }

    public function saveFinanceiroConfig(): void
    {
        [$isAdmin, $isRevenda, $isClienteFinal] = $this->resolveProfileFlags();

        $this->authorizeEmpresaFinanceiroAccess($this->empresa, $isAdmin, $isRevenda, $isClienteFinal);

        abort_unless($isAdmin || $isRevenda, 403, 'Sem permissão para alterar valores financeiros.');

        if ($isAdmin && (int) $this->empresa->nivel_acesso === Empresa::NIVEL_CLIENTE_FINAL && $this->empresa->revenda_id !== null) {
            abort(403, 'Admin pode visualizar, mas a configuracao deste cliente e gerida pela revenda vinculada.');
        }

        $validated = $this->validate();

        $this->config->valor_pagar_unitario = (float) $validated['valorReceberUnitario'];
        $this->config->valor_receber_unitario = (float) $validated['valorReceberUnitario'];
        $this->config->data_vencimento = $validated['dataVencimento'];
        $this->config->data_aviso = $validated['dataAviso'];
        $this->config->data_bloqueio = $validated['dataBloqueio'];
        $this->config->intervalo_cobranca_dias = (int) $validated['intervaloCobrancaDias'];
        $this->config->cobranca_automatica_ativa = (bool) $validated['cobrancaAutomaticaAtiva'];
        $this->config->asaas_integration_ativa = (bool) $validated['asaasIntegrationAtiva'];
        $this->config->bloquear_tv_inadimplencia = (bool) $validated['bloquearTvInadimplencia'];
        $this->config->exibir_qr_code_tv_bloqueada = (bool) $validated['exibirQrCodeTvBloqueada'];
        $this->config->save();
        $this->config->refresh();

        $this->mesesCobranca = $this->config->billingCycleMonths();
        $this->refreshTotals();
        $this->billingIntervalLabel = $this->config->billingIntervalLabel();

        $this->successMessage = 'Valores financeiros atualizados com sucesso.';
        $this->successToastKey++;
        session()->flash('success', $this->successMessage);
    }

    public function updatedValorReceberUnitario(): void
    {
        $this->refreshTotals();
    }

    public function updatedIntervaloCobrancaDias($value): void
    {
        $intervaloDias = (int) $value;

        if (! array_key_exists($intervaloDias, EmpresaFinanceiroConfig::billingIntervalOptions())) {
            return;
        }

        $this->config->intervalo_cobranca_dias = $intervaloDias;
        $this->mesesCobranca = $this->config->billingCycleMonths();
        $this->billingIntervalLabel = $this->config->billingIntervalLabel();
        $this->refreshTotals();
    }

    private function refreshTotals(): void
    {
        $valorUnitario = round((float) $this->valorReceberUnitario, 2);
        $valorCicloPorDispositivo = round($valorUnitario * $this->mesesCobranca, 2);

        $this->totalPagar = round($this->quantidadeDispositivos * $valorCicloPorDispositivo, 2);
        $this->totalReceber = round($this->quantidadeDispositivos * $valorCicloPorDispositivo, 2);
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

    public function render()
    {
        return view('livewire.admin.financeiro-show-panel');
    }
}