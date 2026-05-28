<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class DispatchScheduledWhatsAppCampaigns extends Command
{
    protected $signature = 'whatsapp:dispatch-scheduled';

    protected $description = 'Dispara campanhas agendadas do modulo WhatsApp';

    public function handle(WhatsAppService $whatsAppService): int
    {
        $processed = $whatsAppService->dispatchScheduledCampaigns();

        $this->info($processed > 0
            ? $processed.' campanha(s) de WhatsApp processada(s).'
            : 'Nenhuma campanha de WhatsApp elegivel para envio.');

        return self::SUCCESS;
    }
}
