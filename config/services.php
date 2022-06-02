<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'here' => [
        'api_key' => env('API_KEY_HERE'),
        'routing' => [
            'base_url' => env('BASE_URL_HERE_ROUTING')
        ],
        'search'  => [
            'base_url' => env('BASE_URL_HERE_SEARCH')
        ],
        'geocode'  => [
            'base_url' => env('BASE_URL_HERE_GEOCODE')
        ]
    ],

    'inegi' => [
        'api_key' => env('API_KEY_INEGI'),
        'base_url' => env('BASE_URL_INEGI'),
    ],

];
