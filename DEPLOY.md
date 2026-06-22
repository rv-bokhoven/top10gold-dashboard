# Deploy naar Vercel

Deze app draait op Vercel via de community PHP-runtime (`vercel-php`). Omdat Vercel
serverless is, gebruiken we:

- een **externe Postgres-database** (Neon) i.p.v. de lokale SQLite;
- **cookie-sessies** (geen server-side opslag nodig);
- een **cron-endpoint** (`/cron/sync`) dat de RedTrack-sync draait.

## Benodigde environment variables (Vercel → Project → Settings → Environment Variables)

Zet deze als **encrypted** env vars in het Vercel-dashboard (NIET in `vercel.json`):

| Naam | Waarde |
|------|--------|
| `APP_KEY` | `base64:...` (gegenereerd; zie het bericht waarin de waarden zijn gedeeld) |
| `APP_URL` | de Vercel-URL van het project (na 1e deploy bekend), bijv. `https://top10gold-dashboard.vercel.app` |
| `DASHBOARD_PASSWORD` | een sterk gedeeld wachtwoord naar keuze |
| `REDTRACK_API_KEY` | je RedTrack API-key |
| `CRON_SECRET` | gegenereerd geheim (zie gedeelde waarden) |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | de **pooled** connection string van Neon (incl. `?sslmode=require`) |

> ⚠️ Vul de echte waarden alleen in het Vercel-dashboard in. Zet ze **nooit** in dit bestand
> of elders in git.

> `APP_ENV`, `APP_DEBUG`, sessie-/cache-/cron-instellingen staan al in `vercel.json`.
> Zet `APP_DEBUG=true` tijdelijk in het dashboard als een deploy faalt en je de fout wilt zien.

## Stappen

1. **Neon-database aanmaken** (https://neon.tech, gratis): nieuw project → kopieer de
   *pooled* connection string (de variant met `-pooler` in de host).

2. **Database migreren + eerste backfill** (eenmalig, vanaf je Mac):
   ```bash
   cd /Users/ricky/redtrack
   DB_CONNECTION=pgsql DB_URL='postgres://...pooler...?sslmode=require' php artisan migrate --force
   DB_CONNECTION=pgsql DB_URL='postgres://...pooler...?sslmode=require' php artisan redtrack:sync --all
   ```

3. **GitHub**: repo `rv-bokhoven/top10gold-dashboard` (private) — push (zie hieronder).

4. **Vercel**: New Project → Import Git Repository → kies de repo. Framework Preset = **Other**.
   Voeg de env vars uit de tabel toe. Deploy.

5. **APP_URL** zetten op de toegekende Vercel-URL en opnieuw deployen (of redeploy).

6. **Cron**: `vercel.json` draait `/cron/sync` dagelijks (Hobby-plan staat maximaal dagelijks toe).
   Wil je **elk uur** verversen, maak dan op https://cron-job.org (gratis) een job die elk uur
   `https://<jouw-url>/cron/sync?token=<CRON_SECRET>` aanroept.

## Frontend assets

De gecompileerde Vite-assets (`public/build`) worden **meegecommit** zodat Vercel ze direct serveert
(geen Node-buildstap nodig). Na een frontend-wijziging lokaal opnieuw bouwen en committen:
```bash
npm run build && git add public/build && git commit -m "Rebuild assets"
```
