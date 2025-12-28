import React, { useEffect, useState, useCallback } from 'react';
import { Plus, Calendar, Clock, FileText, Edit2, Trash2, Save, X, Loader2 } from 'lucide-react';
import { apiRequest } from './api';
import type { Event } from './api';

interface Props {
  token: string;
}

export const EventsPage: React.FC<Props> = ({ token }) => {
  const [events, setEvents] = useState<Event[]>([]);
  const [loading, setLoading] = useState(false);
  const [title, setTitle] = useState('');
  const [occursAt, setOccursAt] = useState('');
  const [description, setDescription] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [editingDescription, setEditingDescription] = useState('');

  const loadEvents = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await apiRequest<Event[]>('/events', {}, token);
      setEvents(data);
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Failed to load events');
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }, [token]);

  useEffect(() => {
    void loadEvents();
  }, [loadEvents]);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await apiRequest<Event>(
        '/events',
        {
          method: 'POST',
          body: JSON.stringify({ title, occurs_at: occursAt, description }),
        },
        token,
      );
      setTitle('');
      setOccursAt('');
      setDescription('');
      await loadEvents();
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Failed to create event');
      setError(error.message);
    }
  };

  const handleDelete = async (eventId: number) => {
    if (!confirm('Are you sure you want to delete this event?')) return;
    try {
      await apiRequest<void>(
        `/events/${eventId}`,
        {
          method: 'DELETE',
        },
        token,
      );
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Failed to delete event');
      setError(error.message);
    }
    await loadEvents();
  };

  const startEdit = (event: Event) => {
    setEditingId(event.id);
    setEditingDescription(event.description ?? '');
  };

  const cancelEdit = () => {
    setEditingId(null);
    setEditingDescription('');
  };

  const saveEdit = async (eventId: number) => {
    try {
      await apiRequest<Event>(
        `/events/${eventId}`,
        {
          method: 'PUT',
          body: JSON.stringify({ description: editingDescription }),
        },
        token,
      );
      setEditingId(null);
      setEditingDescription('');
      await loadEvents();
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Failed to update event');
      setError(error.message);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-3xl font-bold text-slate-900 flex items-center gap-2">
          <Calendar className="w-8 h-8 text-blue-600" />
          Your Events
        </h2>
      </div>

      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
          {error}
        </div>
      )}

      <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h3 className="text-xl font-semibold text-slate-900 mb-4 flex items-center gap-2">
          <Plus className="w-5 h-5 text-blue-600" />
          Create Event
        </h3>
        <form onSubmit={handleCreate} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-2">
              Title <span className="text-red-500">*</span>
            </label>
            <input
              value={title}
              onChange={e => setTitle(e.target.value)}
              required
              className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
              placeholder="Event title"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-2">
              <Clock className="w-4 h-4" />
              Occurrence (date & time) <span className="text-red-500">*</span>
            </label>
            <input
              type="datetime-local"
              value={occursAt}
              onChange={e => setOccursAt(e.target.value)}
              required
              className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-2">
              <FileText className="w-4 h-4" />
              Description
            </label>
            <textarea
              value={description}
              onChange={e => setDescription(e.target.value)}
              rows={3}
              className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors resize-none"
              placeholder="Optional description"
            />
          </div>
          <button
            type="submit"
            className="w-full bg-blue-600 text-white py-2.5 px-4 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center justify-center gap-2"
          >
            <Plus className="w-4 h-4" />
            Create Event
          </button>
        </form>
      </div>

      <div>
        <h3 className="text-xl font-semibold text-slate-900 mb-4">Your Events</h3>
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
          </div>
        ) : events.length === 0 ? (
          <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
            <Calendar className="w-12 h-12 text-slate-400 mx-auto mb-4" />
            <p className="text-slate-600">No events yet. Create your first event above!</p>
          </div>
        ) : (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {events.map(ev => (
              <div
                key={ev.id}
                className="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition-shadow"
              >
                <h4 className="text-lg font-semibold text-slate-900 mb-2">{ev.title}</h4>
                <div className="flex items-center gap-2 text-sm text-slate-600 mb-3">
                  <Clock className="w-4 h-4" />
                  <span>{new Date(ev.occurs_at).toLocaleString()}</span>
                </div>
                {editingId === ev.id ? (
                  <div className="space-y-3">
                    <div>
                      <label className="block text-sm font-medium text-slate-700 mb-2">
                        Description
                      </label>
                      <textarea
                        value={editingDescription}
                        onChange={e => setEditingDescription(e.target.value)}
                        rows={3}
                        className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors resize-none text-sm"
                      />
                    </div>
                    <div className="flex gap-2">
                      <button
                        type="button"
                        onClick={() => saveEdit(ev.id)}
                        className="flex-1 bg-blue-600 text-white py-2 px-3 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex items-center justify-center gap-2"
                      >
                        <Save className="w-4 h-4" />
                        Save
                      </button>
                      <button
                        type="button"
                        onClick={cancelEdit}
                        className="flex-1 bg-slate-200 text-slate-700 py-2 px-3 rounded-lg text-sm font-medium hover:bg-slate-300 transition-colors flex items-center justify-center gap-2"
                      >
                        <X className="w-4 h-4" />
                        Cancel
                      </button>
                    </div>
                  </div>
                ) : (
                  <>
                    {ev.description && (
                      <p className="text-sm text-slate-600 mb-3 line-clamp-2">
                        {ev.description}
                      </p>
                    )}
                    <div className="flex gap-2 pt-3 border-t border-slate-200">
                      <button
                        type="button"
                        onClick={() => startEdit(ev)}
                        className="flex-1 bg-slate-100 text-slate-700 py-2 px-3 rounded-lg text-sm font-medium hover:bg-slate-200 transition-colors flex items-center justify-center gap-2"
                      >
                        <Edit2 className="w-4 h-4" />
                        Edit
                      </button>
                      <button
                        type="button"
                        onClick={() => handleDelete(ev.id)}
                        className="flex-1 bg-red-100 text-red-700 py-2 px-3 rounded-lg text-sm font-medium hover:bg-red-200 transition-colors flex items-center justify-center gap-2"
                      >
                        <Trash2 className="w-4 h-4" />
                        Delete
                      </button>
                    </div>
                  </>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};
