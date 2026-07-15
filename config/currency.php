<?php

return [

    // Terugval-wisselkoers (USD per 1 EUR) als de live ECB-koers nog niet is
    // opgehaald. De actuele koers staat in de settings-tabel (key fx.eur_usd),
    // dagelijks ververst via het `fx:update` command.
    'eur_usd_fallback' => (float) env('EUR_USD_RATE', 1.08),

    // Standaard weergavevaluta van het dashboard: 'USD' of 'EUR'.
    'default' => env('DASHBOARD_CURRENCY', 'USD'),

];
