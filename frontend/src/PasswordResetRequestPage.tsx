import React, { useState } from 'react';
import { Mail, Loader2, ArrowLeft } from 'lucide-react';
import { apiRequest } from './api';
import { Link } from 'react-router-dom';

export const PasswordResetRequestPage: React.FC = () => {
  const [email, setEmail] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const [loading, setLoading] = useState(false);
  const [resetLink, setResetLink] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setSuccess(false);
    setResetLink(null);

    try {
      const res = await apiRequest<{ message: string; reset_link: string | null }>('/auth/password/email', {
        method: 'POST',
        body: JSON.stringify({ email }),
      });

      setSuccess(true);
      setResetLink(res.reset_link || null);
    } catch {
      // Even on error, show success message (security best practice - don't reveal if email exists)
      setSuccess(true);
    } finally {
      setLoading(false);
    }
  };

  if (success) {
    return (
      <div className="min-h-screen flex items-center justify-center px-4">
        <div className="w-full max-w-md">
          <div className="bg-white rounded-xl shadow-lg p-8 space-y-6 border border-slate-200">
            <div className="text-center">
              <div className="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                <Mail className="w-8 h-8 text-green-600" />
              </div>
              <h2 className="text-2xl font-bold text-slate-900">
                Password Reset Link
                <div className='text-sm italic'>(Will be emailed)</div>
              </h2>
              {resetLink ? (
                <>
                  <p className="text-sm text-slate-600 mt-2 mb-4">
                    Click the link below to reset your password:
                  </p>
                  <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <a
                      href={resetLink}
                      className="text-blue-600 hover:text-blue-700 break-all text-sm font-medium underline"
                    >
                      {resetLink}
                    </a>
                  </div>
                  <p className="text-xs text-slate-500">
                    This link will expire in 60 minutes and can only be used once.
                  </p>
                </>
              ) : (
                <>
                  <p className="text-sm text-slate-600 mt-2">
                    If that email address exists in our system, a password reset link would have been generated.
                  </p>
                  <p className="text-xs text-slate-500 mt-4">
                    The link expires in 60 minutes and can only be used once.
                  </p>
                </>
              )}
            </div>

            <Link
              to="/"
              className="block w-full text-center text-blue-600 hover:text-blue-700 text-sm font-medium"
            >
              <ArrowLeft className="w-4 h-4 inline mr-2" />
              Back to login
            </Link>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center px-4">
      <div className="w-full max-w-md">
        <form
          onSubmit={handleSubmit}
          className="bg-white rounded-xl shadow-lg p-8 space-y-6 border border-slate-200"
        >
          <div className="text-center">
            <div className="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
              <Mail className="w-8 h-8 text-blue-600" />
            </div>
            <h2 className="text-2xl font-bold text-slate-900">Reset Password</h2>
            <p className="text-sm text-slate-600 mt-2">
              Enter your email address and we'll send you a password reset link
            </p>
          </div>

          {error && (
            <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
              {error}
            </div>
          )}

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

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-blue-600 text-white py-2.5 px-4 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
          >
            {loading ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin" />
                Sending...
              </>
            ) : (
              <>
                <Mail className="w-4 h-4" />
                Send Reset Link
              </>
            )}
          </button>

          <Link
            to="/"
            className="block w-full text-center text-blue-600 hover:text-blue-700 text-sm font-medium"
          >
            <ArrowLeft className="w-4 h-4 inline mr-2" />
            Back to login
          </Link>
        </form>
      </div>
    </div>
  );
};

