<?php

// config for Hwkdo/IntranetAppMeinArbeitsschutz
return [
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
