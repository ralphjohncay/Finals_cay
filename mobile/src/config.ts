import Constants from 'expo-constants';
import { Platform } from 'react-native';

/**
 * Symfony API base URL.
 * - iOS simulator / web: 127.0.0.1
 * - Android emulator: 10.0.2.2 (host machine)
 * - Physical device: set EXPO_PUBLIC_API_URL to http://<your-pc-ip>:8000
 */
function resolveApiUrl(): string {
  const fromEnv = process.env.EXPO_PUBLIC_API_URL;
  if (fromEnv) {
    return fromEnv.replace(/\/$/, '');
  }

  const fromExpo = Constants.expoConfig?.extra?.apiUrl as string | undefined;
  if (fromExpo && Platform.OS !== 'android') {
    return fromExpo.replace(/\/$/, '');
  }

  if (Platform.OS === 'android') {
    return 'http://10.0.2.2:8000';
  }

  return 'http://127.0.0.1:8000';
}

export const API_URL = resolveApiUrl();

export function assetUrl(path: string | null | undefined): string | null {
  if (!path) return null;
  if (path.startsWith('http')) return path;
  return `${API_URL}/uploads/products/${path}`;
}
