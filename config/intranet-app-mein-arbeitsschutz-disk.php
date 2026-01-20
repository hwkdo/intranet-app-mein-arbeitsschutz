<?php

return [
    'driver' => 'local',
    'root' => storage_path('app/public/apps/mein-arbeitsschutz'),
    'url' => env('APP_URL').'/storage/apps/mein-arbeitsschutz',
    'visibility' => 'public',
    'permissions' => [
        'file' => [
            'public' => 0664,
            'private' => 0600,
        ],
        'dir' => [
            'public' => 0775,
            'private' => 0700,
        ],
    ],
    'throw' => true,
    'report' => true,
];
