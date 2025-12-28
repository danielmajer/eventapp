import React, { useEffect, useState } from 'react';
import { Routes, Route, useNavigate, Navigate } from 'react-router-dom';
import { LogOut, Settings, MessageSquare, Calendar, User as UserIcon } from 'lucide-react';
import { LoginPage } from './LoginPage';
import { EventsPage } from './EventsPage';
import { HelpdeskAgentPage } from './HelpdeskAgentPage';
import { HelpdeskChatPage } from './HelpdeskChatPage';
import { UserPreferencesPage } from './UserPreferencesPage';
import { PasswordResetRequestPage } from './PasswordResetRequestPage';
import { PasswordResetPage } from './PasswordResetPage';
import { apiRequest } from './api';
import type { User } from './api';

const App: React.FC = () => {
  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  // Rehydrate auth from localStorage on first load
  useEffect(() => {
    const storedToken = localStorage.getItem('auth_token');
    const storedUser = localStorage.getItem('auth_user');
    if (storedToken && storedUser) {
      try {
        setToken(storedToken);
        setUser(JSON.parse(storedUser));
      } catch {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
      }
    }
    setLoading(false);
  }, []);

  if (!token || !user) {
    if (loading) {
      return null;
    }
    return (
      <Routes>
        <Route
          path="/"
          element={
            <LoginPage
              onLogin={(t, u) => {
                setToken(t);
                setUser(u);
                localStorage.setItem('auth_token', t);
                localStorage.setItem('auth_user', JSON.stringify(u));
              }}
            />
          }
        />
        <Route path="/reset-password" element={<PasswordResetRequestPage />} />
        <Route path="/password/reset" element={<PasswordResetPage />} />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    );
  }

  const isHelpdeskAgent = ['helpdesk_agent', 'admin'].indexOf(user.role || '') !== -1;

  const handleLogout = async () => {
    try {
      await apiRequest<void>(
        '/auth/logout',
        { method: 'POST' },
        token ?? undefined,
      );
    } catch {
      // swallow logout errors; token may already be invalid/expired
    } finally {
      setToken(null);
      setUser(null);
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_user');
    }
  };

  return (
    <div className="min-h-screen flex flex-col">
      <header className="bg-white shadow-sm border-b border-slate-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
              <Calendar className="w-6 h-6 text-blue-600" />
              Event Manager
            </h1>
            <div className="flex items-center gap-4">
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={() => navigate('/')}
                  className="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                >
                  <Calendar className="w-4 h-4" />
                  Events
                </button>
                <button
                  type="button"
                  onClick={() => navigate('/chat')}
                  className="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                >
                  <MessageSquare className="w-4 h-4" />
                  Chat
                </button>
                <button
                  type="button"
                  onClick={() => navigate('/preferences')}
                  className="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                >
                  <Settings className="w-4 h-4" />
                  Preferences
                </button>
                {isHelpdeskAgent && (
                  <button
                    type="button"
                    onClick={() => navigate('/helpdesk')}
                    className="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                  >
                    <MessageSquare className="w-4 h-4" />
                    Helpdesk
                  </button>
                )}
                <div className="flex items-center gap-2 text-sm text-slate-600">
                  <UserIcon className="w-4 h-4" />
                  <span>{user.email}</span>
                </div>
                <button
                  type="button"
                  onClick={handleLogout}
                  className="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                >
                  <LogOut className="w-4 h-4" />
                  Logout
                </button>
              </div>
            </div>
          </div>
        </div>
      </header>
      <main className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <Routes>
          <Route path="/" element={<EventsPage token={token} />} />
          <Route path="/chat" element={<HelpdeskChatPage token={token} />} />
          <Route
            path="/preferences"
            element={
              <UserPreferencesPage
                token={token}
                user={user}
                onUserUpdate={updated => {
                  setUser(updated);
                  localStorage.setItem('auth_user', JSON.stringify(updated));
                }}
              />
            }
          />
          {isHelpdeskAgent && (
            <Route path="/helpdesk" element={<HelpdeskAgentPage token={token} />} />
          )}
        </Routes>
      </main>
    </div>
  );
};


export default App;
