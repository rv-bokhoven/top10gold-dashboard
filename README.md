# RedTrack Dashboard

Intern dashboard dat RedTrack-stats van de Google Ads-campagnes ophaalt, lokaal opslaat en
overzichtelijk toont — zodat het team de cijfers kan bekijken zonder in RedTrack in te loggen.
Alle data wordt gefilterd op `rt_source=Google` (haalt bot-clicks eruit).

Stack: Laravel 13 · Livewire 4 · Flux · Tailwind 4 · SQLite · ApexCharts.

## Installeren

```bash
composer install
npm install && npm run build
cp .env.example .env        # vul REDTRACK_API_KEY en DASHBOARD_PASSWORD in
php artisan key:generate
php artisan migrate
php artisan redtrack:sync --all   # eerste backfill vanaf REDTRACK_BACKFILL_FROM
```

## Draaien

- **Herd**: `herd link redtrack` → bereikbaar op `https://redtrack.test`.
- **Of**: `php artisan serve` → `http://127.0.0.1:8000`.

Inloggen met het `DASHBOARD_PASSWORD` uit `.env` (één gedeeld wachtwoord, geen losse accounts).

## Data verversen

```bash
php artisan redtrack:sync               # laatste 3 dagen (incl. late conversie-attributie)
php artisan redtrack:sync --from=2026-05-01 --to=2026-06-19
php artisan redtrack:sync --all         # volledige backfill
```

Automatisch elk uur via de scheduler. Zorg dat de scheduler draait:

- Lokaal: `php artisan schedule:work`
- Productie: cron-entry `* * * * * cd /pad && php artisan schedule:run >> /dev/null 2>&1`

In het dashboard zit ook een **"Ververs nu"**-knop die direct synct.

## Belangrijke configuratie (`config/redtrack.php`)

- `rt_source` — vaste traffic-source filter (default `Google`).
- `conv_types` — mapping van RedTrack's `convtypeN`-kolommen naar betekenis. Geverifieerd:
  `lead=convtype1`, `qlead=convtype2`, `sale=convtype3`, `lpclick=convtype4`.
  Pas dit aan als je in RedTrack de volgorde van je conversion types wijzigt.

> LP Views en kosten worden door RedTrack op campagne-/landing-niveau geteld; LP Clicks en
> conversies attribueren naar de offers. De per-offer-tabel start daarom bij LP Click.

## Volgende fase

Google Ads ad-performance koppelen (aparte Google Ads API OAuth) naast de RedTrack-data.
