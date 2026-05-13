<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $storagePath = sys_get_temp_dir().'/api-tests-storage-'.getmypid();

        foreach ([
            $storagePath,
            $storagePath.'/app',
            $storagePath.'/framework',
            $storagePath.'/framework/cache',
            $storagePath.'/framework/sessions',
            $storagePath.'/framework/testing',
            $storagePath.'/framework/views',
            $storagePath.'/logs',
        ] as $directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }

        $app->useStoragePath($storagePath);
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
