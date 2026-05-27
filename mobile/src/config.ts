import { API_BASE_URL } from './config/api';

export const API_URL = API_BASE_URL;

export function assetUrl(path: string | null | undefined): string | null {
  if (!path) return null;
  if (path.startsWith('http')) return path;
  return `${API_BASE_URL}/uploads/products/${path}`;
}
