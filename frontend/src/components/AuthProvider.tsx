'use client';

import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react';
import { ApiError } from '@/lib/api';
import { getAuthenticatedUser, logout as logoutRequest } from '@/lib/auth';
import type { PublicUser } from '@/lib/types';

const AuthContext = createContext<{
  user: PublicUser | null;
  loading: boolean;
  refreshUser: () => Promise<PublicUser | null>;
  logout: () => Promise<void>;
} | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<PublicUser | null>(null);
  const [loading, setLoading] = useState(true);

  const refreshUser = useCallback(async () => {
    try {
      const response = await getAuthenticatedUser();
      setUser(response.data.user);
      return response.data.user;
    } catch (error) {
      if (error instanceof ApiError && error.status === 401) setUser(null);
      else setUser(null);
      return null;
    }
  }, []);

  useEffect(() => {
    const timer = window.setTimeout(() => {
      refreshUser().finally(() => setLoading(false));
    }, 0);
    return () => window.clearTimeout(timer);
  }, [refreshUser]);

  const logout = async () => {
    await logoutRequest();
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, loading, refreshUser, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuth must be used within AuthProvider');
  return context;
}
