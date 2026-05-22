# RALPHS Footwear — React Native (Expo)

Mobile client for the Symfony + API Platform backend in the parent folder (`Ralph-master`).

## Prerequisites

- Node 18+
- Symfony API running at `http://127.0.0.1:8000` (`symfony serve:start` in project root)
- Database loaded with fixtures: `php bin/console doctrine:fixtures:load`

## Install & run

```bash
cd mobile
npm install
npx expo start
```

Press `a` for Android emulator or scan QR with Expo Go on a device.

### API URL

| Environment | URL |
|-------------|-----|
| iOS simulator | `http://127.0.0.1:8000` (default) |
| Android emulator | `http://10.0.2.2:8000` (automatic) |
| Physical device | Set `EXPO_PUBLIC_API_URL=http://<your-pc-ip>:8000` in `.env` |

## Dev login (fixtures)

- Email: `customer@shoes.com`
- Password: `customer123`

## Features

- JWT auth (`POST /api/login`) + profile (`GET /api/me`)
- Register (`POST /api/register`)
- Products, services, orders via API Platform (`/api/products`, etc.)
- Cart → `POST /api/orders` with `pending_approval` status
- UI colors aligned with the public website (brown/cream palette)

## Cursor prompt

See `CURSOR_PROMPT.md` for a copy-paste prompt to extend or wire an existing React Native project.
