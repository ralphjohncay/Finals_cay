import { API_URL } from '../config';
import type { ApiError } from './types';

export class ApiClientError extends Error {
  status: number;
  body: ApiError | string;

  constructor(status: number, body: ApiError | string) {
    const msg =
      typeof body === 'object'
        ? body.message ?? body.detail ?? `Request failed (${status})`
        : body;
    super(msg);
    this.status = status;
    this.body = body;
  }
}

type RequestOptions = {
  method?: string;
  token?: string | null;
  body?: unknown;
  headers?: Record<string, string>;
};

export async function apiRequest<T>(
  path: string,
  options: RequestOptions = {},
): Promise<T> {
  const { method = 'GET', token, body, headers = {} } = options;
  const url = path.startsWith('http') ? path : `${API_URL}${path}`;

  const res = await fetch(url, {
    method,
    headers: {
      Accept: 'application/ld+json',
      ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...headers,
    },
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  const text = await res.text();
  let parsed: ApiError | T | null = null;
  if (text) {
    try {
      parsed = JSON.parse(text) as ApiError | T;
    } catch {
      parsed = text;
    }
  }

  if (!res.ok) {
    throw new ApiClientError(res.status, (parsed ?? text) as ApiError | string);
  }

  return (parsed ?? ({} as T)) as T;
}
