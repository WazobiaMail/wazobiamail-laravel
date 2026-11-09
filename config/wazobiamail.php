<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WazobiaMail API Key
    |--------------------------------------------------------------------------
    |
    | Your WazobiaMail API key (`wzbm_...`). You can create this in your
    | WazobiaMail dashboard under SMTP & API Keys.
    |
    */
    'api_key' => env('WAZOBIA_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | API Gateway Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the WazobiaMail REST API. Typically unchanged unless
    | connecting to a staging or proxy gateway.
    |
    */
    'base_url' => env('WAZOBIA_BASE_URL', 'https://app.wazobiamail.com/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The request timeout in seconds for API calls.
    |
    */
    'timeout' => (float) env('WAZOBIA_TIMEOUT', 30.0),
];
