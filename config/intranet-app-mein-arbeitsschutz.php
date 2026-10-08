<?php

// config for Hwkdo/IntranetAppMeinArbeitsschutz
return [
    'lightrag' => [
        'url' => env('LIGHTRAG_ARBEITSSCHUTZ_URL', 'https://lightrag-arbeitsschutz.swarm.hwkdo.com'),
        'api_key' => env('LIGHTRAG_API_KEY'),
        'execute_in_tests' => false,
    ],

    'roles' => [
        'admin' => [
            'name' => 'App-MeinArbeitsschutz-Admin',
            'permissions' => [
                'see-app-mein-arbeitsschutz',
                'manage-app-mein-arbeitsschutz',
            ],
        ],
        'user' => [
            'name' => 'App-MeinArbeitsschutz-Benutzer',
            'permissions' => [
                'see-app-mein-arbeitsschutz',
            ],
            'all_users' => true,  // Alle aktiven User bekommen automatisch diese Rolle
        ],
    ],
];
