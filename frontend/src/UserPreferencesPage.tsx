import React, { useState } from 'react';
import { Shield, QrCode, CheckCircle2, X, Loader2 } from 'lucide-react';
import { apiRequest } from './api';
import type { User } from './api';

interface Props {
  token: string;
  user: User;
  onUserUpdate: (user: User) => void;
}

export const UserPreferencesPage: React.FC<Props> = ({ token, user, onUserUpdate }) => {
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [qrCodeUrl, setQrCodeUrl] = useState<string | null>(null);
  const [confirmCode, setConfirmCode] = useState('');
  const [disableCode, setDisableCode] = useState('');
  const [showConfirm, setShowConfirm] = useState(false);
  const [showDisable, setShowDisable] = useState(false);

  const handleEnableMfa = async () => {
    setLoading(true);
    setError(null);
    setMessage(null);
    try {
      const res = await apiRequest<{ message: string; qr_code_url: string; secret: string; user: User }>(
        '/auth/mfa/setup',
        { method: 'POST' },
        token,
      );
      setQrCodeUrl(res.qr_code_url);
      setShowConfirm(true);
      setMessage(res.message);
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Failed to enable MFA');
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };

  const handleConfirmMfa = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setMessage(null);
    try {
      const res = await apiRequest<{ message: string; user: User }>(
        '/auth/mfa/confirm',
        {
          method: 'POST',
          body: JSON.stringify({ code: confirmCode }),
        },
        token,
      );
      onUserUpdate(res.user);
      setMessage(res.message);
      setShowConfirm(false);
      setQrCodeUrl(null);
      setConfirmCode('');
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Failed to confirm MFA');
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };

  const handleDisableMfa = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setMessage(null);
    try {
      const res = await apiRequest<{ message: string; user: User }>(
        '/auth/mfa/disable',
        {
          method: 'POST',
          body: JSON.stringify({ code: disableCode }),
        },
        token,
      );
      onUserUpdate(res.user);
      setMessage(res.message);
      setShowDisable(false);
      setDisableCode('');
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Failed to disable MFA');
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      <h2 className="text-3xl font-bold text-slate-900 flex items-center gap-2">
        <Shield className="w-8 h-8 text-purple-600" />
        User Preferences
      </h2>

      <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h3 className="text-xl font-semibold text-slate-900 mb-4 flex items-center gap-2">
          <Shield className="w-5 h-5 text-purple-600" />
          Two-factor authentication
        </h3>
        <p className="text-slate-600 mb-4">
          Protect your account with an additional one-time code from an authenticator app like Google
          Authenticator, Authy, or Microsoft Authenticator.
        </p>
        <div className="mb-4">
          <span className="text-sm font-medium text-slate-700">Status: </span>
          <span
            className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-sm font-medium ${
              user.mfa_enabled
                ? 'bg-green-100 text-green-700'
                : 'bg-slate-100 text-slate-700'
            }`}
          >
            {user.mfa_enabled ? (
              <>
                <CheckCircle2 className="w-4 h-4" />
                Enabled
              </>
            ) : (
              'Disabled'
            )}
          </span>
        </div>

        {message && (
          <div className="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg text-sm mb-4">
            {message}
          </div>
        )}
        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-4">
            {error}
          </div>
        )}

        {showConfirm && qrCodeUrl && (
          <div className="mt-6 p-6 border border-slate-300 rounded-lg bg-slate-50">
            <h4 className="text-lg font-semibold text-slate-900 mb-2 flex items-center gap-2">
              <QrCode className="w-5 h-5" />
              Scan QR Code
            </h4>
            <p className="text-sm text-slate-600 mb-4">
              Scan this QR code with your authenticator app:
            </p>
            <div className="flex justify-center mb-4">
              <div className="bg-white p-4 rounded-lg border-2 border-slate-300">
                <img src={qrCodeUrl} alt="QR Code" className="w-64 h-64" />
              </div>
            </div>
            <form onSubmit={handleConfirmMfa} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">
                  Enter 6-digit code from your app:
                </label>
                <input
                  type="text"
                  value={confirmCode}
                  onChange={e => setConfirmCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                  placeholder="000000"
                  maxLength={6}
                  required
                  className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-colors text-center text-2xl tracking-widest font-mono"
                />
              </div>
              <div className="flex gap-2">
                <button
                  type="submit"
                  disabled={loading || confirmCode.length !== 6}
                  className="flex-1 bg-purple-600 text-white py-2.5 px-4 rounded-lg font-medium hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
                >
                  {loading ? (
                    <>
                      <Loader2 className="w-4 h-4 animate-spin" />
                      Verifying…
                    </>
                  ) : (
                    <>
                      <CheckCircle2 className="w-4 h-4" />
                      Confirm & Enable
                    </>
                  )}
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setShowConfirm(false);
                    setQrCodeUrl(null);
                    setConfirmCode('');
                  }}
                  disabled={loading}
                  className="px-4 py-2.5 bg-slate-200 text-slate-700 rounded-lg font-medium hover:bg-slate-300 transition-colors flex items-center justify-center gap-2"
                >
                  <X className="w-4 h-4" />
                  Cancel
                </button>
              </div>
            </form>
          </div>
        )}

        {showDisable && (
          <div className="mt-6 p-6 border border-slate-300 rounded-lg bg-slate-50">
            <h4 className="text-lg font-semibold text-slate-900 mb-2">Disable Two-Factor Authentication</h4>
            <p className="text-sm text-slate-600 mb-4">Enter your 6-digit code to disable 2FA:</p>
            <form onSubmit={handleDisableMfa} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">Code:</label>
                <input
                  type="text"
                  value={disableCode}
                  onChange={e => setDisableCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                  placeholder="000000"
                  maxLength={6}
                  required
                  className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-colors text-center text-2xl tracking-widest font-mono"
                />
              </div>
              <div className="flex gap-2">
                <button
                  type="submit"
                  disabled={loading || disableCode.length !== 6}
                  className="flex-1 bg-red-600 text-white py-2.5 px-4 rounded-lg font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
                >
                  {loading ? (
                    <>
                      <Loader2 className="w-4 h-4 animate-spin" />
                      Disabling…
                    </>
                  ) : (
                    <>
                      <X className="w-4 h-4" />
                      Disable 2FA
                    </>
                  )}
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setShowDisable(false);
                    setDisableCode('');
                  }}
                  disabled={loading}
                  className="px-4 py-2.5 bg-slate-200 text-slate-700 rounded-lg font-medium hover:bg-slate-300 transition-colors"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        )}

        {!showConfirm && !showDisable && (
          <div className="flex gap-3">
            <button
              type="button"
              onClick={handleEnableMfa}
              disabled={loading || user.mfa_enabled}
              className="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <Shield className="w-4 h-4" />
              Enable 2FA
            </button>
            <button
              type="button"
              onClick={() => setShowDisable(true)}
              disabled={loading || !user.mfa_enabled}
              className="inline-flex items-center gap-2 px-4 py-2.5 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <X className="w-4 h-4" />
              Disable 2FA
            </button>
          </div>
        )}
      </div>
    </div>
  );
};
