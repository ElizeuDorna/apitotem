<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateWithForeignCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
        protected $signature = 'migrate:fix {--seed}';

    /**
     * The console command description.
     *
     * @var string
     */
     protected $description = 'Roda migrate:refresh desativando/ativando FOREIGN_KEY_CHECKS no MySQL.';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Garante que o comando só rode em ambientes de desenvolvimento/local
        if (app()->environment(['local', 'testing'])) {
            
            $this->info('Desativando FOREIGN_KEY_CHECKS...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            $this->info('Iniciando migrate:refresh...');
            
            // Roda o migrate:refresh, passando a flag --seed se ela foi usada
            Artisan::call('migrate:refresh', [
                '--seed' => $this->option('seed')
            ]);
            
            $this->info(Artisan::output()); // Exibe a saída do refresh
            
            $this->info('Reativando FOREIGN_KEY_CHECKS...');
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->info('Processo de migração concluído com sucesso!');

            return 0;
    }
    $this->error('Este comando só pode ser executado em ambiente local.');
        return 1;
}
}
