import { apiRequest } from './client';
import type { LoginResponse, RegisterResponse } from './types';

export async function login(email: string, password: string): Promise<LoginResponse> {
  return apiRequest<LoginResponse>('/api/login', {
    method: 'POST',
    body: { email, password },
  });
}

export async function register(payload: {
  email: string;
  name: string;
  password: string;
}): Promise<RegisterResponse> {
  return apiRequest<RegisterResponse>('/api/register', {
    method: 'POST',
    body: payload,
  });
}

export async function verifyEmail(token: string) {
  return apiRequest<{ success: boolean; message: string }>('/api/verify-email', {
    method: 'POST',
    body: { token },
  });
}
