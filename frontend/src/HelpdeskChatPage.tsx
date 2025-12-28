import React, { useEffect, useState, useRef, useCallback } from 'react';
import { MessageSquare, Send, Bot, User as UserIcon, Plus, Loader2, Clock, CheckCircle } from 'lucide-react';
import { apiRequest } from './api';

interface Props {
  token: string;
}

interface Message {
  id: number;
  sender_type: string;
  content: string;
  created_at: string;
}

interface Chat {
  id: number;
  status: string;
  messages: Message[];
  created_at: string;
}

export const HelpdeskChatPage: React.FC<Props> = ({ token }) => {
  const [chats, setChats] = useState<Chat[]>([]);
  const [selectedChat, setSelectedChat] = useState<Chat | null>(null);
  const [message, setMessage] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [sending, setSending] = useState(false);
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
      const data = await apiRequest<Chat[]>('/helpdesk/my-chats', {}, token);
      setChats(data);
      if (data.length > 0 && !selectedChat) {
        setSelectedChat(data[0]);
        await loadChatDetails(data[0].id);
      }
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Failed to load chats');
      setError(error.message);
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [token]);

  const loadChatDetails = async (chatId: number) => {
    try {
      const data = await apiRequest<Chat>(`/helpdesk/chats/${chatId}`, {}, token);
      setSelectedChat(data);
      // Update the chat in the list
      setChats(prev => prev.map(c => (c.id === chatId ? data : c)));
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Failed to load chat');
      setError(error.message);
    }
  };

  useEffect(() => {
    void loadChats();
  }, [loadChats]);

  const handleStartNewChat = async () => {
    setSending(true);
    setError(null);
    try {
      const newChat = await apiRequest<Chat>(
        '/helpdesk/chats',
        {
          method: 'POST',
          body: JSON.stringify({ message: 'Hello, I need help.' }),
        },
        token,
      );
      await loadChats();
      setSelectedChat(newChat);
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Failed to start chat');
      setError(error.message);
    } finally {
      setSending(false);
    }
  };

  const handleSend = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedChat || !message.trim() || sending || selectedChat.status === 'closed') return;

    setSending(true);
    setError(null);
    try {
      await apiRequest<Message[]>(
        `/helpdesk/chats/${selectedChat.id}/messages`,
        {
          method: 'POST',
          body: JSON.stringify({ message }),
        },
        token,
      );
      setMessage('');
      // Reload chat to get updated messages
      await loadChatDetails(selectedChat.id);
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Failed to send message');
      setError(error.message);
    } finally {
      setSending(false);
    }
  };

  const handleCompleteChat = async () => {
    if (!selectedChat) return;
    if (!window.confirm('Are you sure you want to complete this chat? You won\'t be able to send more messages.')) return;

    try {
      await apiRequest<Chat>(
        `/helpdesk/chats/${selectedChat.id}/close`,
        { method: 'POST' },
        token,
      );
      await loadChatDetails(selectedChat.id);
      await loadChats();
    } catch (err: unknown) {
      const error = err instanceof Error ? err : new Error('Failed to complete chat');
      setError(error.message);
    }
  };

  const getSenderIcon = (senderType: string) => {
    switch (senderType) {
      case 'user':
        return <UserIcon className="w-4 h-4" />;
      case 'bot':
        return <Bot className="w-4 h-4" />;
      case 'agent':
        return <UserIcon className="w-4 h-4" />;
      default:
        return <MessageSquare className="w-4 h-4" />;
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-3xl font-bold text-slate-900 flex items-center gap-2">
          <MessageSquare className="w-8 h-8 text-blue-600" />
          Helpdesk Chat
        </h2>
        <button
          type="button"
          onClick={handleStartNewChat}
          disabled={sending}
          className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {sending ? (
            <Loader2 className="w-4 h-4 animate-spin" />
          ) : (
            <Plus className="w-4 h-4" />
          )}
          New Chat
        </button>
      </div>

      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
          {error}
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 h-[calc(100vh-250px)]">
        {/* Chat List */}
        <div className="bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col">
          <div className="p-4 border-b border-slate-200">
            <h3 className="text-lg font-semibold text-slate-900">Your Chats</h3>
          </div>
          <div className="flex-1 overflow-y-auto">
            {loading ? (
              <div className="flex items-center justify-center py-8">
                <Loader2 className="w-6 h-6 animate-spin text-blue-600" />
              </div>
            ) : chats.length === 0 ? (
              <div className="p-8 text-center text-slate-500">
                <MessageSquare className="w-12 h-12 mx-auto mb-2 text-slate-400" />
                <p>No chats yet</p>
                <p className="text-sm mt-2">Start a new chat to get help</p>
              </div>
            ) : (
              <div className="divide-y divide-slate-200">
                {chats.map(chat => (
                  <button
                    key={chat.id}
                    onClick={() => {
                      void loadChatDetails(chat.id);
                    }}
                    className={`w-full text-left p-4 hover:bg-slate-50 transition-colors ${
                      selectedChat?.id === chat.id ? 'bg-blue-50 border-l-4 border-blue-600' : ''
                    }`}
                  >
                    <div className="flex items-center justify-between mb-1">
                      <span className="font-medium text-slate-900 text-sm">
                        Chat #{chat.id}
                      </span>
                      <span
                        className={`text-xs px-2 py-1 rounded-full font-medium ${
                          chat.status === 'open'
                            ? 'bg-green-100 text-green-700'
                            : chat.status === 'transferred'
                              ? 'bg-blue-100 text-blue-700'
                              : 'bg-slate-100 text-slate-700'
                        }`}
                      >
                        {chat.status}
                      </span>
                    </div>
                    <p className="text-xs text-slate-500">
                      {new Date(chat.created_at).toLocaleDateString()}
                    </p>
                  </button>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Chat Detail */}
        <div className="lg:col-span-3 bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col">
          {selectedChat ? (
            <>
              <div className="p-4 border-b border-slate-200 flex items-center justify-between">
                <h3 className="text-lg font-semibold text-slate-900 flex items-center gap-2">
                  <Bot className="w-5 h-5 text-purple-600" />
                  Chat with Support Bot
                </h3>
                {selectedChat.status !== 'closed' && (
                  <button
                    type="button"
                    onClick={handleCompleteChat}
                    className="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors"
                  >
                    <CheckCircle className="w-4 h-4" />
                    Complete
                  </button>
                )}
              </div>
              <div className="flex-1 overflow-y-auto p-4 space-y-4">
                {selectedChat.messages.length === 0 ? (
                  <div className="text-center text-slate-500 py-8">
                    <MessageSquare className="w-12 h-12 mx-auto mb-2 text-slate-400" />
                    <p>No messages yet. Start the conversation!</p>
                  </div>
                ) : (
                  selectedChat.messages.map(msg => {
                    const isUser = msg.sender_type === 'user';
                    const isBot = msg.sender_type === 'bot';
                    return (
                      <div
                        key={msg.id}
                        className={`flex gap-3 ${isUser ? 'flex-row-reverse' : 'flex-row'}`}
                      >
                        <div
                          className={`flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center ${
                            isUser
                              ? 'bg-blue-600 text-white'
                              : isBot
                                ? 'bg-purple-600 text-white'
                                : 'bg-slate-200 text-slate-700'
                          }`}
                        >
                          {getSenderIcon(msg.sender_type)}
                        </div>
                        <div className={`flex-1 ${isUser ? 'text-right' : 'text-left'}`}>
                          <div
                            className={`inline-block max-w-[80%] rounded-lg p-3 ${
                              isUser
                                ? 'bg-blue-600 text-white'
                                : isBot
                                  ? 'bg-purple-100 text-purple-900 border border-purple-200'
                                  : 'bg-slate-100 text-slate-900'
                            }`}
                          >
                            <p className="text-sm whitespace-pre-wrap">{msg.content}</p>
                          </div>
                          <div
                            className={`text-xs text-slate-500 mt-1 flex items-center gap-1 ${
                              isUser ? 'justify-end' : 'justify-start'
                            }`}
                          >
                            <Clock className="w-3 h-3" />
                            {new Date(msg.created_at).toLocaleTimeString()}
                          </div>
                        </div>
                      </div>
                    );
                  })
                )}
                <div ref={messagesEndRef} />
              </div>
              <form onSubmit={handleSend} className="p-4 border-t border-slate-200">
                <div className="flex gap-2">
                  <input
                    value={message}
                    onChange={e => setMessage(e.target.value)}
                    placeholder="Type your message..."
                    required
                    disabled={sending || selectedChat.status === 'closed'}
                    className="flex-1 px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                  />
                  <button
                    type="submit"
                    disabled={sending || !message.trim() || selectedChat.status === 'closed'}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                  >
                    {sending ? (
                      <Loader2 className="w-4 h-4 animate-spin" />
                    ) : (
                      <Send className="w-4 h-4" />
                    )}
                    Send
                  </button>
                </div>
                {selectedChat.status === 'closed' && (
                  <p className="text-sm text-red-600 mt-2 font-medium">
                    This chat is closed. No new messages can be sent.
                  </p>
                )}
                {selectedChat.status === 'transferred' && (
                  <p className="text-sm text-blue-600 mt-2">
                    This chat has been transferred to a human agent. They will respond shortly.
                  </p>
                )}
              </form>
            </>
          ) : (
            <div className="flex-1 flex items-center justify-center p-8">
              <div className="text-center text-slate-500">
                <MessageSquare className="w-16 h-16 mx-auto mb-4 text-slate-400" />
                <p className="text-lg font-medium">No chat selected</p>
                <p className="text-sm mt-2">Select a chat from the list or start a new one</p>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

