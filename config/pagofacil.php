<?php

return [
    'token_service' => env('PAGO_FACIL_TCTOKEN_SERVICE'),
    'token_secret' => env('PAGO_FACIL_TCTOKEN_SECRET'),
    'client_code' => env('PAGO_FACIL_CLIENT_CODE', env('PAGO_FACIL_COMERCE_ID')),
    'callback_url' => env('PAGO_FACIL_CALLBACK_URL'),
    'payment_method_id' => env('PAGO_FACIL_PAYMENT_METHOD_ID'),

    'urls' => [
        'login' => 'https://masterqr.pagofacil.com.bo/api/services/v2/login',
        'list_methods' => 'https://masterqr.pagofacil.com.bo/api/services/v2/list-enabled-services',
        'generate_qr' => 'https://masterqr.pagofacil.com.bo/api/services/v2/generate-qr',
        'query' => 'https://masterqr.pagofacil.com.bo/api/services/v2/query-transaction',
    ],
];
