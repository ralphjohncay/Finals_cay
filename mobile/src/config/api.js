/**
 * Central Symfony API base URL (Railway production).
 * Override locally with EXPO_PUBLIC_API_URL in mobile/.env
 */
export const API_BASE_URL = (
  process.env.EXPO_PUBLIC_API_URL ?? 'https://finalscay-production.up.railway.app'
).replace(/\/$/, '');
