import React, { useEffect, useState, useRef, useCallback } from 'react';
import { MessageSquare, Send, User, Bot, Headphones, Clock, Loader2, XCircle, Filter } from 'lucide-react';
import { apiRequest } from './api';

interface Props {
  token: string;
}

interface Chat {
  id: number;
  status: string;
  user: { id: number; email: string };
}

interface Message {
  id: number;
  sender_type: string;
  content: string;
  created_at: string;
}

interface ChatDetail extends Chat {
  messages: Message[];
}

type FilterType = 'all' | 'open' | 'transferred' | 'closed';

export const HelpdeskAgentPage: React.FC<Props> = ({ token }) => {
  const [chats, setChats] = useState<Chat[]>([]);
  const [selectedChat, setSelectedChat] = useState<ChatDetail | null>(null);
  const [message, setMessage] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [filter, setFilter] = useState<FilterType>('all');
  const messagesEndRef = useRef<HTMLDivElement>(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    scrollToBottom();
  }, [selectedChat?.messages]);

  const loadChats = useCallback(async () => {
    setLoading(true);
    try {
      const data = await apiRequest<{ data: Chat[] }>('/helpdesk/chats', {}, token);
      setChats(data.data);
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Failed to load chats');
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }, [token]);

  const loadChat = async (chatId: number) => {
    try {
      const data = await apiRequest<ChatDetail>(`/helpdesk/chats/${chatId}`, {}, token);
      setSelectedChat(data);
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Failed to load chat');
      setError(error.message);
    }
  };

  useEffect(() => {
    void loadChats();
  }, [loadChats]);

  const handleSend = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedChat || !message.trim() || selectedChat.status === 'closed') return;
    try {
      await apiRequest<Message>(
        `/helpdesk/chats/${selectedChat.id}/agent-messages`,
        {
          method: 'POST',
          body: JSON.stringify({ message }),
        },
        token,
      );
      setMessage('');
      await loadChat(selectedChat.id);
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Failed to send message');
      setError(error.message);
    }
  };

  const handleCloseChat = async () => {
    if (!selectedChat) return;
    if (!window.confirm('Are you sure you want to close this chat? No more messages can be sent.')) return;

    try {
      await apiRequest<Chat>(
        `/helpdesk/chats/${selectedChat.id}/close`,
        { method: 'POST' },
        token,
      );
      await loadChat(selectedChat.id);
      await loadChats();
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Failed to close chat');
      setError(error.message);
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'open':
        return 'bg-green-100 text-green-700';
      case 'transferred':
        return 'bg-blue-100 text-blue-700';
      case 'closed':
        return 'bg-slate-100 text-slate-700';
      default:
        return 'bg-slate-100 text-slate-700';
    }
  };

  const getSenderIcon = (senderType: string) => {
    switch (senderType) {
      case 'user':
        return <User className="w-4 h-4" />;
      case 'agent':
        return <Headphones className="w-4 h-4" />;
      case 'bot':
        return <Bot className="w-4 h-4" />;
      default:
        return <MessageSquare className="w-4 h-4" />;
    }
  };

  const filteredChats = chats.filter(chat => {
    if (filter === 'all') return true;
    return chat.status === filter;
  });

  return (
    <div className="space-y-6">
      <h2 className="text-3xl font-bold text-slate-900 flex items-center gap-2">
        <MessageSquare className="w-8 h-8 text-blue-600" />
        Helpdesk Agent Console
      </h2>

      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
          {error}
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 h-[calc(100vh-250px)]">
        {/* Chat List */}
        <div className="bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col">
          <div className="p-4 border-b border-slate-200">
            <div className="flex items-center justify-between mb-3">
              <h3 className="text-lg font-semibold text-slate-900">Chats</h3>
              <Filter className="w-5 h-5 text-slate-500" />
            </div>
            <div className="flex flex-wrap gap-2">
              <button
                onClick={() => setFilter('all')}
                className={`px-3 py-1.5 text-sm font-medium rounded-lg transition-colors ${
                  filter === 'all'
                    ? 'bg-blue-600 text-white'
                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                }`}
              >
                All
              </button>
              <button
                onClick={() => setFilter('open')}
                className={`px-3 py-1.5 text-sm font-medium rounded-lg transition-colors ${
                  filter === 'open'
                    ? 'bg-green-600 text-white'
                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                }`}
              >
                Open
              </button>
              <button
                onClick={() => setFilter('transferred')}
                className={`px-3 py-1.5 text-sm font-medium rounded-lg transition-colors ${
                  filter === 'transferred'
                    ? 'bg-blue-600 text-white'
                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                }`}
              >
                Transferred
              </button>
              <button
                onClick={() => setFilter('closed')}
                className={`px-3 py-1.5 text-sm font-medium rounded-lg transition-colors ${
                  filter === 'closed'
                    ? 'bg-slate-600 text-white'
                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                }`}
              >
                Closed
              </button>
            </div>
          </div>
          <div className="flex-1 overflow-y-auto">
            {loading ? (
              <div className="flex items-center justify-center py-8">
                <Loader2 className="w-6 h-6 animate-spin text-blue-600" />
              </div>
            ) : filteredChats.length === 0 ? (
              <div className="p-8 text-center text-slate-500">
                <MessageSquare className="w-12 h-12 mx-auto mb-2 text-slate-400" />
                <p>No {filter === 'all' ? '' : filter} chats available</p>
              </div>
            ) : (
              <div className="divide-y divide-slate-200">
                {filteredChats.map(chat => (
                  <button
                    key={chat.id}
                    onClick={() => {
                      void loadChat(chat.id);
                    }}
                    className={`w-full text-left p-4 hover:bg-slate-50 transition-colors ${
                      selectedChat?.id === chat.id ? 'bg-blue-50 border-l-4 border-blue-600' : ''
                    }`}
                  >
                    <div className="flex items-center justify-between mb-1">
                      <span className="font-medium text-slate-900">{chat.user.email}</span>
                      <span
                        className={`text-xs px-2 py-1 rounded-full font-medium ${getStatusColor(
                          chat.status,
                        )}`}
                      >
                        {chat.status}
                      </span>
                    </div>
                  </button>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Chat Detail */}
        <div className="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col">
          {selectedChat ? (
            <>
              <div className="p-4 border-b border-slate-200 flex items-center justify-between">
                <h3 className="text-lg font-semibold text-slate-900 flex items-center gap-2">
                  <User className="w-5 h-5 text-blue-600" />
                  Chat with {selectedChat.user.email}
                </h3>
                {selectedChat.status !== 'closed' && (
                  <button
                    type="button"
                    onClick={handleCloseChat}
                    className="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"
                  >
                    <XCircle className="w-4 h-4" />
                    Close
                  </button>
                )}
              </div>
              <div className="flex-1 overflow-y-auto p-4 space-y-4">
                {selectedChat.messages.map(msg => {
                  const isAgent = msg.sender_type === 'agent';
                  const isBot = msg.sender_type === 'bot';
                  return (
                    <div
                      key={msg.id}
                      className={`flex gap-3 ${
                        isAgent ? 'flex-row-reverse' : 'flex-row'
                      }`}
                    >
                      <div
                        className={`flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center ${
                          isAgent
                            ? 'bg-blue-600 text-white'
                            : isBot
                              ? 'bg-purple-600 text-white'
                              : 'bg-slate-200 text-slate-700'
                        }`}
                      >
                        {getSenderIcon(msg.sender_type)}
                      </div>
                      <div className={`flex-1 ${isAgent ? 'text-right' : 'text-left'}`}>
                        <div
                          className={`inline-block max-w-[80%] rounded-lg p-3 ${
                            isAgent
                              ? 'bg-blue-600 text-white'
                              : isBot
                                ? 'bg-purple-100 text-purple-900'
                                : 'bg-slate-100 text-slate-900'
                          }`}
                        >
                          <p className="text-sm">{msg.content}</p>
                        </div>
                        <div
                          className={`text-xs text-slate-500 mt-1 flex items-center gap-1 ${
                            isAgent ? 'justify-end' : 'justify-start'
                          }`}
                        >
                          <Clock className="w-3 h-3" />
                          {new Date(msg.created_at).toLocaleTimeString()}
                        </div>
                      </div>
                    </div>
                  );
                })}
                <div ref={messagesEndRef} />
              </div>
              <form onSubmit={handleSend} className="p-4 border-t border-slate-200">
                <div className="flex gap-2">
                  <input
                    value={message}
                    onChange={e => setMessage(e.target.value)}
                    placeholder="Type your reply..."
                    required
                    disabled={selectedChat.status === 'closed'}
                    className="flex-1 px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                  />
                  <button
                    type="submit"
                    disabled={selectedChat.status === 'closed'}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <Send className="w-4 h-4" />
                    Send
                  </button>
                </div>
                {selectedChat.status === 'closed' && (
                  <p className="text-sm text-red-600 mt-2 font-medium">
                    This chat is closed. No new messages can be sent.
                  </p>
                )}
              </form>
            </>
          ) : (
            <div className="flex-1 flex items-center justify-center p-8">
              <div className="text-center text-slate-500">
                <MessageSquare className="w-16 h-16 mx-auto mb-4 text-slate-400" />
                <p className="text-lg font-medium">Select a chat to view messages</p>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
