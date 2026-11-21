import { apiRequest } from './api';

export type PublicUser = {
  id: number;
  name: string;
  email: string;
};

export async function initializeCsrf(): Promise<void> {
  await apiRequest('/sanctum/csrf-cookie');
}

export async function login(email: string, password: string) {
  await initializeCsrf();

  return apiRequest<{ message: string; data: { user: PublicUser } }>('/api/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });
}

export async function getAuthenticatedUser() {
  return apiRequest<{ data: { user: PublicUser } }>('/api/auth/me');
}

export async function logout() {
  return apiRequest<{ message: string }>('/api/auth/logout', {
    method: 'POST',
  });
}
