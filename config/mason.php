<?php

declare(strict_types=1);

return [
    'generator' => [
        'namespace' => 'Lara\\App\Filament\\Components\\Mason',
        'views_path' => 'lara-app',
    ],
    'preview' => [
        'layout' => 'lara-app::components.mason.iframe-preview', // Set to your layout view path, e.g., 'layouts.preview'
    ],
    'entry' => [
        'layout' => 'lara-app::components.mason.iframe-entry', // Set to your layout view path, e.g., 'layouts.entry'
    ],
    'routes' => [
        'middleware' => ['web', 'auth'],
    ],
];
