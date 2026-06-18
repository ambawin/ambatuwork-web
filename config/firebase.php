<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Project Configuration
    |--------------------------------------------------------------------------
    |
    | Set FIREBASE_CREDENTIALS to the path of your Firebase service account
    | JSON file (relative to the project root). The credentials file is stored
    | in storage/app/private and should NOT be committed to version control.
    |
    */

    'default' => env('FIREBASE_PROJECT', 'app'),

    'projects' => [
        'app' => [
            'credentials' => env(
                'FIREBASE_CREDENTIALS',
                storage_path('app/private/firebase_credentials.json')
            ),
        ],
    ],

];
