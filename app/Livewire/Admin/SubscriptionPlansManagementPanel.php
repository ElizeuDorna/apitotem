<?php

namespace App\Livewire\Admin;

use App\Models\EmpresaFinanceiroConfig;
use App\Models\EmpresaSubscriptionPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SubscriptionPlansManagementPanel extends Component
{
    public ?int $editingPlanId = null;

    public bool $formEnabled = false;

    public string $code = '';

    public string $name = '';

    public string $description = '';

    public string $intervaloCobrancaDias = '30';

    public string $valorUnitario = '0.00';

    public string $trialDays = '7';

    public bool $isActive = true;

    public bool $isSelfService = true;

    public string $sortOrder = '0';

    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->resetForm();
    }

    public function startCreate(): void
    {
        $this->authorizeAccess();
        $this->resetForm();
        $this->formEnabled = true;
        $this->statusMessage = null;
    }

    public function editPlan(int $planId): void
    {
        $this->authorizeAccess();

        $plan = EmpresaSubscriptionPlan::query()->findOrFail($planId);

        $this->editingPlanId = $plan->id;
        $this->formEnabled = true;
        $this->code = (string) $plan->code;
        $this->name = (string) $plan->name;
        $this->description = (string) ($plan->description ?? '');
        $this->intervaloCobrancaDias = (string) $plan->intervalo_cobranca_dias;
        $this->valorUnitario = number_format((float) $plan->valor_unitario, 2, '.', '');
        $this->trialDays = (string) $plan->trial_days;
        $this->isActive = (bool) $plan->is_active;
        $this->isSelfService = (bool) $plan->is_self_service;
        $this->sortOrder = (string) $plan->sort_order;
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->statusMessage = null;
    }

    public function save(): void
    {
        $this->authorizeAccess();

        $validated = $this->validate($this->rules());

        $attributes = [
            'code' => strtolower((string) $validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] !== '' ? $validated['description'] : null,
            'intervalo_cobranca_dias' => (int) $validated['intervaloCobrancaDias'],
            'valor_unitario' => round((float) $validated['valorUnitario'], 2),
            'trial_days' => (int) $validated['trialDays'],
            'is_active' => (bool) $validated['isActive'],
            'is_self_service' => (bool) $validated['isSelfService'],
            'sort_order' => (int) $validated['sortOrder'],
        ];

        if ($this->editingPlanId) {
            $plan = EmpresaSubscriptionPlan::query()->findOrFail($this->editingPlanId);
            $plan->update($attributes);
            $message = "Plano {$plan->name} atualizado com sucesso.";
        } else {
            $plan = EmpresaSubscriptionPlan::query()->create($attributes);
            $message = "Plano {$plan->name} criado com sucesso.";
        }

        $this->resetForm();
        $this->statusMessage = $message;
    }

    public function toggleActive(int $planId): void
    {
        $this->authorizeAccess();

        $plan = EmpresaSubscriptionPlan::query()->findOrFail($planId);
        $plan->is_active = ! $plan->is_active;
        $plan->save();

        $this->statusMessage = $plan->is_active
            ? "Plano {$plan->name} ativado com sucesso."
            : "Plano {$plan->name} desativado com sucesso.";
    }

    public function render()
    {
        $this->authorizeAccess();

        return view('livewire.admin.subscription-plans-management-panel', [
            'plans' => EmpresaSubscriptionPlan::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'intervalOptions' => EmpresaFinanceiroConfig::billingIntervalOptions(),
        ]);
    }

    private function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('empresa_subscription_plans', 'code')->ignore($this->editingPlanId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'intervaloCobrancaDias' => ['required', Rule::in(array_keys(EmpresaFinanceiroConfig::billingIntervalOptions()))],
            'valorUnitario' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'trialDays' => ['required', 'integer', 'min:0', 'max:365'],
            'isActive' => ['required', 'boolean'],
            'isSelfService' => ['required', 'boolean'],
            'sortOrder' => ['required', 'integer', 'min:0', 'max:999999'],
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingPlanId',
            'formEnabled',
            'code',
            'name',
            'description',
            'statusMessage',
        ]);

        $this->intervaloCobrancaDias = (string) EmpresaFinanceiroConfig::INTERVALO_30_DIAS;
        $this->valorUnitario = '0.00';
        $this->trialDays = (string) config('subscriptions.self_service.trial_days', 7);
        $this->isActive = true;
        $this->isSelfService = true;
        $this->sortOrder = '0';
        $this->resetValidation();
    }

    private function authorizeAccess(): void
    {
        $user = Auth::user();

        abort_unless($user?->isDefaultAdmin(), 403);
    }
}