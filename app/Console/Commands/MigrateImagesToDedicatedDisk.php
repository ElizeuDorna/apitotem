<?php

namespace App\Console\Commands;

use App\Support\ImageStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateImagesToDedicatedDisk extends Command
{
    protected $signature = 'images:migrate-to-dedicated-disk';

    protected $description = 'Move arquivos de imagem existentes do disk public para o disk dedicado images';

    public function handle(): int
    {
        $directories = ['galeria-nova', 'galeria-geral', 'empresas'];
        $moved = 0;

        foreach ($directories as $directory) {
            if (! Storage::disk('public')->exists($directory)) {
                continue;
            }

            foreach (Storage::disk('public')->allFiles($directory) as $path) {
                if (! Storage::disk(ImageStorage::disk())->exists($path)) {
                    $stream = Storage::disk('public')->readStream($path);

                    if ($stream !== false) {
                        Storage::disk(ImageStorage::disk())->writeStream($path, $stream);
                        if (is_resource($stream)) {
                            fclose($stream);
                        }
                    }
                }

                Storage::disk('public')->delete($path);
                $moved++;
            }
        }

        $this->info("Arquivos movidos: {$moved}");

        return self::SUCCESS;
    }
}