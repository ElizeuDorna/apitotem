<?php

namespace App\Console\Commands;

use App\Models\DeviceActivation;
use Illuminate\Console\Command;

class CleanupExpiredDeviceActivations extends Command
{
    protected $signature = 'devices:cleanup-activations';

    protected $description = 'Remove ativações de dispositivo expiradas e não utilizadas';

    public function handle(): int
    {
        $deleted = DeviceActivation::query()
            ->where('activated', false)
            ->where('expires_at', '<', now())
            ->delete();

        $this->info("Ativações expiradas removidas: {$deleted}");

        return self::SUCCESS;
    }
}
