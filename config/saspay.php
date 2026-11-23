<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration SasPay (saspay.me)
    |--------------------------------------------------------------------------
    */
    'base_url' => env('SASPAY_BASE_URL', 'https://api.saspay.me/api/v1'),
    'api_key' => env('SASPAY_API_KEY', ''),
    'webhook_secret' => env('SASPAY_WEBHOOK_SECRET', ''),
];
