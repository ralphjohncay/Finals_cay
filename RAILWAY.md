# Railway deployment (Docker only)

Railway must use **Dockerfile** via `railway.toml` — not Nixpacks / FrankenPHP.

## Railway service variables (set in dashboard)

- `APP_ENV=prod`
- `APP_DEBUG=0`
- `APP_SECRET` — random string (not in repo)
- `DATABASE_URL=${{MySQL.DATABASE_URL}}` (add `?serverVersion=8.0.32&charset=utf8mb4` if missing)
- `DEFAULT_URI=https://finalscay-production.up.railway.app`
- `JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem`
- `JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem`
- `JWT_PASSPHRASE` — your passphrase
- `MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0`

Health check: `GET /health` → `OK`

Public site: `/homepage` (landing page)
