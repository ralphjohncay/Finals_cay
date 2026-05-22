# Deploying RALPHS Footwear on Railway

## Required service variables (Finals_cay app service)

Set these in Railway → **Finals_cay** → **Variables** (not only MySQL):

| Variable | Value |
|----------|--------|
| `APP_ENV` | `prod` |
| `APP_DEBUG` | `0` |
| `APP_SECRET` | long random string (32+ chars) |
| `DATABASE_URL` | `${{MySQL.DATABASE_URL}}` then append `?serverVersion=8.0.32&charset=utf8mb4` if not already present |
| `DEFAULT_URI` | `https://finalscay-production.up.railway.app` (your public URL) |
| `JWT_PASSPHRASE` | same passphrase used when keys were generated locally |
| `JWT_SECRET_KEY` | `%kernel.project_dir%/config/jwt/private.pem` |
| `JWT_PUBLIC_KEY` | `%kernel.project_dir%/config/jwt/public.pem` |
| `CORS_ALLOW_ORIGIN` | `'^https?://(localhost\|127\.0\.0\.1\|.*\.up\.railway\.app)(:[0-9]+)?$'` |
| `TRUSTED_PROXIES` | `REMOTE_ADDR` |
| `MAILER_DSN` | your SMTP DSN (optional) |
| `OAUTH_GOOGLE_CALLBACK_URL` | `https://<your-domain>/connect/google/check` |

JWT keys are generated on first container start if missing (`lexik:jwt:generate-keypair`). Keep `JWT_PASSPHRASE` stable across deploys.

## MySQL service

Link MySQL to the app service. `DATABASE_URL` must reference `${{MySQL.DATABASE_URL}}`.

## Build

Railway uses `railway.toml` → **Dockerfile** (not Nixpacks root serving).

- Document root: `public/`
- All routes go through `public/router.php` → `index.php` (fixes 404 on `/homepage`, `/api`, etc.)
- Assets built with `npm run build` during image build
- Migrations run on container start

## After deploy

1. Open `https://<your-app>.up.railway.app/homepage` — public landing page
2. API docs: `https://<your-app>.up.railway.app/api/docs`
3. Login: `https://<your-app>.up.railway.app/login`

## Optional: seed data

In Railway → Finals_cay → **Shell** (one-off):

```bash
php bin/console doctrine:fixtures:load --no-interaction
```

## Mobile app API URL

```
EXPO_PUBLIC_API_URL=https://finalscay-production.up.railway.app
```
