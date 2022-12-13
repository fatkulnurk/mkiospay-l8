<?php

return [
    'credentials' => [
        'partner_id' => env('TELE_PARTNER_ID', '40014002'),
        'user_id' => env('TELE_USER_ID', 'tester40014002'),
        'passid' => env('TELE_PASSID', '417760'),
        'x_api_key' => env('TELE_X_API_KEY', 'ec8d3e7a826443b3483ce908f2e74aa9b9300d7b'),
    ],
    'url' => [
        'inquiry' => env('TELE_URL_INQUIRY', 'https://119.82.226.231:8443/tele-api-v3/h2h/trxdev/inquiry'),
        'payment' => env('TELE_URL_PAYMENT', 'https://119.82.226.231:8443/tele-api-v3/h2h/trxdev/payment'),
        'purchase' => env('TELE_URL_PURCHASE', 'https://119.82.226.231:8443/tele-api-v3/h2h/trxdev/purchase'),
        'status' => env('TELE_URL_STATUS', 'https://119.82.226.231:8443/tele-api-v3/h2h/trxdev/cek_status'),
        'pbb_inquiry' => env('TELE_URL_PBB_INQUIRY', 'https://119.82.226.232/tele-api-v3/h2h/trxdev/inquiry'),
        'pbb_payment' => env('TELE_URL_PBB_PAYMENT', 'https://119.82.226.232/tele-api-v3/h2h/trxdev/payment'),
    ]
];
