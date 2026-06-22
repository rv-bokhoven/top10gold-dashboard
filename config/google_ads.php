<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Ads API
    |--------------------------------------------------------------------------
    | Credentials voor de Google Ads API. Zie DEPLOY/README voor hoe je deze
    | aanmaakt (developer token via 301 Ads → API Center, OAuth-client in Google
    | Cloud, refresh token via consent-flow).
    */

    'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),

    'client_id' => env('GOOGLE_ADS_CLIENT_ID'),
    'client_secret' => env('GOOGLE_ADS_CLIENT_SECRET'),
    'refresh_token' => env('GOOGLE_ADS_REFRESH_TOKEN'),

    // Manager-account (MCC) "301 Ads".
    'login_customer_id' => env('GOOGLE_ADS_LOGIN_CUSTOMER_ID', '6625858813'),

    // Campagne-account "Top10 Gold".
    'customer_id' => env('GOOGLE_ADS_CUSTOMER_ID', '4149739998'),

    // API-versie in de REST-endpoint-URL. Bump dit als Google een versie uitfaseert.
    'api_version' => env('GOOGLE_ADS_API_VERSION', 'v24'),

];
