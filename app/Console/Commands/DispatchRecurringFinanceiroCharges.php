<?php

namespace App\Console\Commands;

use App\Services\AsaasService;
use App\Services\FinanceiroChargeService;
use Illuminate\Console\Command;

class DispatchRecurringFinanceiroCharges extends Command
{
    protected $signature = 'financeiro:dispatch-recurring-charges';

    protected $description = 'Gera cobrancas PIX automaticas para empresas com agendamento habilitado';

    public function handle(FinanceiroChargeService $financeiroChargeService, AsaasService $asaas): int
    {
        $processed = $financeiroChargeService->dispatchScheduledPixCharges($asaas);

        $this->info($processed > 0
            ? $processed.' cobranca(s) automatica(s) gerada(s).'
            : 'Nenhuma cobranca automatica elegivel para geracao.');

        return self::SUCCESS;
    }
}