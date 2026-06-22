# Google Ads API koppelen (fase 2)

De dashboard-app heeft eigen Google Ads API-credentials nodig. Account-gegevens staan al
ingevuld in `config/google_ads.php`:
- Manager (login-customer-id): `6625858813` (301 Ads)
- Campagne-account (customer-id): `4149739998` (Top10 Gold)

Je moet nog 3 dingen aanmaken: een **developer token**, een **OAuth-client** en een **refresh token**.

## 1. Developer token
1. Log in op het **manager-account 301 Ads** in Google Ads.
2. **Tools → API Center** (alleen zichtbaar op een manager-account).
3. Vraag een **developer token** aan. Voor échte data heb je **Basic access** nodig — dat
   vereist goedkeuring van Google (kan een dag of langer duren). Een test-token werkt alleen
   op test-accounts.

## 2. OAuth-client (Google Cloud)
1. Ga naar https://console.cloud.google.com → maak/kies een project.
2. **APIs & Services → Library →** zoek **Google Ads API → Enable**.
3. **OAuth consent screen**: type **External**, vul de basics in, en voeg jouw Google-account
   toe als **Test user**.
4. **Credentials → Create credentials → OAuth client ID → type "Desktop app"**.
   Noteer de **Client ID** en **Client secret**.

## 3. Refresh token (eenmalig)
Makkelijkste weg via de **OAuth 2.0 Playground**:
1. Open https://developers.google.com/oauthplayground
2. Rechtsboven ⚙️ → vink **"Use your own OAuth credentials"** → vul Client ID + secret in.
3. Scope (links zelf invoeren): `https://www.googleapis.com/auth/adwords` → **Authorize APIs**.
4. Log in met het account dat toegang heeft tot 301 Ads → **Exchange authorization code for tokens**.
5. Kopieer de **refresh token**.

> Zorg dat in de Playground-instellingen dezelfde Client ID/secret staan als in je `.env`,
> anders werkt de refresh token niet.

## 4. Invullen + testen (lokaal)
Zet in `.env`:
```
GOOGLE_ADS_DEVELOPER_TOKEN=...
GOOGLE_ADS_CLIENT_ID=...
GOOGLE_ADS_CLIENT_SECRET=...
GOOGLE_ADS_REFRESH_TOKEN=...
```
Dan:
```bash
php artisan google-ads:sync --all      # haalt de ad-stats binnen
```
Open het dashboard → onderaan vult de **Google Ads**-sectie zich met echte data.

## 5. Live zetten (Vercel + Neon)
1. Migratie op Neon draaien (nieuwe tabel `google_ad_stats`):
   ```bash
   DB_CONNECTION=pgsql DB_URL='postgres://...pooler...?sslmode=require' php artisan migrate --force
   ```
2. De 4 `GOOGLE_ADS_*` secrets toevoegen in **Vercel → Settings → Environment Variables**
   (login/customer-id en api-version staan al in de code als default).
3. `feature/google-ads` mergen naar `main` en pushen → Vercel deployt.
   De dagelijkse cron (`/cron/sync`) synct dan automatisch ook Google Ads mee.
