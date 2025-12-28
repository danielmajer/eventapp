import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { LogIn, Shield, Mail, Lock, Loader2 } from 'lucide-react';
import { apiRequest } from './api';
import type { User } from './api';

interface Props {
  onLogin: (token: string, user: User) => void;
}

export const LoginPage: React.FC<Props> = ({ onLogin }) => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [mfaCode, setMfaCode] = useState('');
  const [requiresMfa, setRequiresMfa] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      const res = await apiRequest<{ token?: string; user?: User; requires_mfa?: boolean }>(
        '/auth/login',
        {
          method: 'POST',
          body: JSON.stringify({ email, password }),
        },
      );

      if (res.requires_mfa) {
        setRequiresMfa(true);
        return;
      }

      if (!res.token || !res.user) {
        throw new Error('Unexpected login response');
      }

      onLogin(res.token, res.user);
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Login failed');
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyMfa = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      const res = await apiRequest<{ token: string; user: User }>('/auth/mfa/verify', {
        method: 'POST',
        body: JSON.stringify({ email, code: mfaCode }),
      });
      onLogin(res.token, res.user);
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('MFA verification failed');
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center px-4">
      <div className="w-full max-w-md">
        {!requiresMfa ? (
          <form
            onSubmit={handleSubmit}
            className="bg-white rounded-xl shadow-lg p-8 space-y-6 border border-slate-200"
          >
            <div className="text-center">
              <div className="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                <LogIn className="w-8 h-8 text-blue-600" />
              </div>
              <h2 className="text-2xl font-bold text-slate-900">Login</h2>
              <p className="text-sm text-slate-600 mt-2">Enter your credentials to continue</p>
            </div>

            {error && (
              <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                {error}
              </div>
            )}

            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">
                  <Mail className="w-4 h-4 inline mr-2" />
                  Email
                </label>
                <input
                  value={email}
                  onChange={e => setEmail(e.target.value)}
                  type="email"
                  required
                  className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                  placeholder="you@example.com"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">
                  <Lock className="w-4 h-4 inline mr-2" />
                  Password
                </label>
                <input
                  value={password}
                  onChange={e => setPassword(e.target.value)}
                  type="password"
                  required
                  className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                  placeholder="••••••••"
                />
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-blue-600 text-white py-2.5 px-4 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
            >
              {loading ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin" />
                  Logging in…
                </>
              ) : (
                <>
                  <LogIn className="w-4 h-4" />
                  Login
                </>
              )}
            </button>

            <div className="text-center">
              <Link
                to="/reset-password"
                className="text-sm text-blue-600 hover:text-blue-700 font-medium"
              >
                Forgot your password?
              </Link>
            </div>
          </form>
        ) : (
          <form
            onSubmit={handleVerifyMfa}
            className="bg-white rounded-xl shadow-lg p-8 space-y-6 border border-slate-200"
          >
            <div className="text-center">
              <div className="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-4">
                <Shield className="w-8 h-8 text-purple-600" />
              </div>
              <h2 className="text-2xl font-bold text-slate-900">Two-factor authentication</h2>
              <p className="text-sm text-slate-600 mt-2">
                Enter the 6-digit code from your authenticator app
              </p>
            </div>

            {error && (
              <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                {error}
              </div>
            )}

            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">
                One-time code
              </label>
              <input
                value={mfaCode}
                onChange={e => setMfaCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                maxLength={6}
                required
                className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-colors text-center text-2xl tracking-widest font-mono"
                placeholder="000000"
              />
            </div>

            <button
              type="submit"
              disabled={loading || mfaCode.length !== 6}
              className="w-full bg-purple-600 text-white py-2.5 px-4 rounded-lg font-medium hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
            >
              {loading ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin" />
                  Verifying…
                </>
              ) : (
                <>
                  <Shield className="w-4 h-4" />
                  Verify & Sign in
                </>
              )}
            </button>
          </form>
        )}
      </div>
    </div>
  );
};


