<?php

return [
    'key' => env('MAILJET_APIKEY'),
    'secret' => env('MAILJET_APISECRET'),
    
    // Configuration optimisée pour l'API v3.1 (transactional)
    'transactional' => [
        'call' => true,
        'options' => [
            'url' => 'api.mailjet.com',
            'version' => 'v3.1',
            'call' => true,
            'secured' => true,
            'timeout' => 30,
            'connect_timeout' => 10
        ]
    ],
    
    // Configuration pour l'API v3 (common)
    'common' => [
        'call' => true,
        'options' => [
            'url' => 'api.mailjet.com',
            'version' => 'v3',
            'call' => true,
            'secured' => true,
            'timeout' => 30,
            'connect_timeout' => 10
        ]
    ],
    
    // Options de performance
    'options' => [
        'sandbox' => env('MAILJET_SANDBOX', false),
        'track_opens' => true,
        'track_clicks' => true,
        'deduplicate_campaign' => true
    ]
];