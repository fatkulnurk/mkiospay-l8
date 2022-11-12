<?php

return [
    'credentials' => [
        'user_id' => env('TELE_USER_ID'),
        'partner_id' => env('TELE_PARTNER_ID'),
        'passid' => env('TELE_PASSID'),
        'x_api_key' => env('TELE_X_API_KEY'),
    ],
    'url' => [
        'inquiry' => env('TELE_URL_INQUIRY'),
        'payment' => env('TELE_URL_PAYMENT'),
        'purchase' => env('TELE_URL_PURCHASE'),
        'status' => env('TELE_URL_STATUS'),
    ]
];
