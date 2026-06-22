<?php

return [

    /*
    |--------------------------------------------------------------------------
    | RedTrack API
    |--------------------------------------------------------------------------
    */

    'api_key' => env('REDTRACK_API_KEY'),

    'base_url' => env('REDTRACK_BASE_URL', 'https://api.redtrack.io'),

    // Geheim token waarmee het /cron/sync endpoint wordt beveiligd.
    'cron_secret' => env('CRON_SECRET'),

    /*
    | Vaste traffic-source filter. Alle rapporten worden hierop gefilterd zodat
    | we alleen "echte" Google-traffic zien (zonder deze filter zitten er veel
    | bot-clicks in de data).
    */
    'rt_source' => env('REDTRACK_RT_SOURCE', 'Google'),

    /*
    | Eerste dag waarvandaan een volledige backfill begint (accountstart).
    */
    'backfill_from' => env('REDTRACK_BACKFILL_FROM', '2026-05-01'),

    /*
    | Aantal dagen dat de standaard (incrementele) sync terugkijkt. Iets ruimer
    | dan 1 dag zodat late conversie-attributie alsnog wordt meegenomen.
    */
    'sync_days' => env('REDTRACK_SYNC_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | Conversion-type mapping
    |--------------------------------------------------------------------------
    | RedTrack levert conversies als genummerde kolommen convtype1..convtype40.
    | Hieronder de mapping naar betekenis. Per type kun je één of meer kolommen
    | opgeven; die worden bij elkaar opgeteld. Lead en qlead zijn aparte types
    | (qlead = quality lead, levert revenue op). Pas dit aan als je in RedTrack
    | de volgorde van je conversion types wijzigt.
    */
    'conv_types' => [
        'lead' => ['convtype1'],
        'qlead' => ['convtype2'],
        'sale' => ['convtype3'],
        'lpclick' => ['convtype4'],
    ],

    /*
    | Veld in de API-respons met de werkelijke omzet (som over alle conversion
    | types). Het losse `revenue`-veld is hier 0; `total_revenue` is correct.
    */
    'revenue_field' => 'total_revenue',

];
