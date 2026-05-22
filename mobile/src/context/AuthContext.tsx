import * as SecureStore from 'expo-secure-store';
import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react';
import { login as apiLogin, register as apiRegister } from '../api/auth';
import { apiRequest } from '../api/client';
import { userIri } from '../api/catalog';

const TOKEN_KEY = 'ralphs_jwt';
const USER_KEY = 'ralphs_user';

export type StoredUser = {
  id: number;
  email: string;
  name: string;
  roles: string[];
};

type AuthContextValue = {
  token: string | null;
  user: StoredUser | null;
  loading: boolean;
  signIn: (email: string, password: string) => Promise<void>;
  signUp: (email: string, name: string, password: string) => Promise<string>;
  signOut: () => Promise<void>;
  refreshProfile: () => Promise<void>;
  customerIri: string | null;
};

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

async function fetchMe(token: string): Promise<StoredUser> {
  const me = await apiRequest<{
    id: number;
    email: string;
    name: string;
    roles: string[];
  }>('/api/me', { token });
  return {
    id: me.id,
    email: me.email,
    name: me.name,
    roles: me.roles,
  };
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<StoredUser | null>(null);
  const [loading, setLoading] = useState(true);

  const persist = useCallback(async (nextToken: string, nextUser: StoredUser) => {
    await SecureStore.setItemAsync(TOKEN_KEY, nextToken);
    await SecureStore.setItemAsync(USER_KEY, JSON.stringify(nextUser));
    setToken(nextToken);
    setUser(nextUser);
  }, []);

  const refreshProfile = useCallback(async () => {
    if (!token) return;
    const me = await fetchMe(token);
    await SecureStore.setItemAsync(USER_KEY, JSON.stringify(me));
    setUser(me);
  }, [token]);

  useEffect(() => {
    (async () => {
      try {
        const storedToken = await SecureStore.getItemAsync(TOKEN_KEY);
        const storedUser = await SecureStore.getItemAsync(USER_KEY);
        if (!storedToken) return;
        setToken(storedToken);
        if (storedUser) {
          setUser(JSON.parse(storedUser) as StoredUser);
        }
        const me = await fetchMe(storedToken);
        setUser(me);
        await SecureStore.setItemAsync(USER_KEY, JSON.stringify(me));
      } catch {
        await SecureStore.deleteItemAsync(TOKEN_KEY);
        await SecureStore.deleteItemAsync(USER_KEY);
        setToken(null);
        setUser(null);
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  const signIn = useCallback(
    async (email: string, password: string) => {
      const { token: jwt } = await apiLogin(email.trim(), password);
      const me = await fetchMe(jwt);
      await persist(jwt, me);
    },
    [persist],
  );

  const signUp = useCallback(async (email: string, name: string, password: string) => {
    const res = await apiRegister({ email: email.trim(), name: name.trim(), password });
    return res.message;
  }, []);

  const signOut = useCallback(async () => {
    await SecureStore.deleteItemAsync(TOKEN_KEY);
    await SecureStore.deleteItemAsync(USER_KEY);
    setToken(null);
    setUser(null);
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({
      token,
      user,
      loading,
      signIn,
      signUp,
      signOut,
      refreshProfile,
      customerIri: user ? userIri(user.id) : null,
    }),
    [token, user, loading, signIn, signUp, signOut, refreshProfile],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return ctx;
}
