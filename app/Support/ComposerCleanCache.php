<?php

namespace App\Support;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;

class ComposerCleanCache
{
    public static function cleanAllCacheDirectories()
    {
        // Load the Composer Autoloader
        require_once __DIR__.'/../../vendor/autoload.php';

        // Bootstrap the Laravel Application Container
        $app = require_once __DIR__.'/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        $targetPaths = [
            base_path('bootstrap/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];

        foreach ($targetPaths as $targetPath) {
            if (File::isDirectory($targetPath)) {
                File::cleanDirectory($targetPath);
                echo "Successfully cleaned: {$targetPath}\n";
            }
        }
    }
}
