<?php

namespace App\Console\Commands;

use App\Services\AsaasService;
use App\Services\FinanceiroChargeService;
use Illuminate\Console\Command;

class SyncPendingFinanceiroCharges extends Command
{
    protected $signature = 'financeiro:sync-pending-charges';

    protected $description = 'Sincroniza no Asaas as cobrancas financeiras pendentes para atualizar status automaticamente';

    public function handle(FinanceiroChargeService $financeiroChargeService, AsaasService $asaas): int
    {
        $result = $financeiroChargeService->syncAwaitingPixCharges($asaas);

        $this->info($result['processed'].' cobranca(s) consultada(s); '.$result['updated'].' atualizada(s).');

        return self::SUCCESS;
    }
}