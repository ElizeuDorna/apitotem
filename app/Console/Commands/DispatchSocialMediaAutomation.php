<?php

namespace App\Console\Commands;

use App\Services\SocialMediaAutomationService;
use Illuminate\Console\Command;
use Throwable;

class DispatchSocialMediaAutomation extends Command
{
    protected $signature = 'social-media:dispatch-automation';

    protected $description = 'Dispara publicacoes automaticas de ofertas para Facebook e Instagram';

    public function handle(SocialMediaAutomationService $automationService): int
    {
        try {
            $result = $automationService->dispatchDueAutomations();
            $this->info('Automacoes processadas: '.$result['processed'].' | publicacoes geradas: '.$result['published']);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
