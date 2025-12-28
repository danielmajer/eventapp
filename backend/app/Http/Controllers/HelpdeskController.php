<?php

namespace App\Http\Controllers;

use App\Models\HelpdeskChat;
use App\Models\HelpdeskMessage;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HelpdeskController extends Controller
{
    public function startChat(Request $request)
    {
        $chat = HelpdeskChat::create([
            'user_id' => $request->user()->id,
            'status' => 'open',
        ]);

        if ($request->filled('message')) {
            $this->addMessage($chat, 'user', $request->user()->id, $request->input('message'));

            // Generate bot reply using Google Gemini
            $botReply = $this->generateBotReply($chat, $request->input('message'));
            $this->addMessage($chat, 'bot', null, $botReply);
        }

        return response()->json($chat->load('messages'), Response::HTTP_CREATED);
    }

    public function addMessageFromUser(Request $request, HelpdeskChat $chat)
    {
        $this->authorize('view', $chat);

        // Prevent adding messages to closed chats
        if ($chat->status === 'closed') {
            AuditLogService::logAccessDenied($request->user(), 'helpdesk_chats', $chat->id, 'Chat is closed');
            return response()->json(['message' => 'This chat is closed. No new messages can be sent.'], Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'message' => 'required|string',
        ]);

        $userMessage = $request->input('message');
        $message = $this->addMessage($chat, 'user', $request->user()->id, $userMessage);

        // Check if user wants to transfer to human
        if ($this->isTransferRequest($userMessage)) {
            $chat->status = 'transferred';
            $chat->save();
            $botMessage = $this->addMessage($chat, 'bot', null, 'This chat has been transferred to a human agent. They will respond shortly.');
            return response()->json([$message, $botMessage], Response::HTTP_CREATED);
        }

        // If chat is already transferred, don't generate bot replies - wait for human agent
        // User can still send messages to the agent
        if ($chat->status === 'transferred') {
            // User can send messages, but no bot reply - waiting for human agent
            return response()->json($message, Response::HTTP_CREATED);
        }

        // Generate bot reply using Google Gemini
        $botReply = $this->generateBotReply($chat, $userMessage);
        $botMessage = $this->addMessage($chat, 'bot', null, $botReply);

        return response()->json([$message, $botMessage], Response::HTTP_CREATED);
    }

    /**
     * Get all chats for the authenticated user
     */
    public function getUserChats(Request $request)
    {
        $chats = HelpdeskChat::where('user_id', $request->user()->id)
            ->with('messages')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($chats);
    }

    public function listChats()
    {
        $chats = HelpdeskChat::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($chats);
    }

    public function showChat(HelpdeskChat $chat)
    {
        $chat->load(['user', 'messages']);

        return response()->json($chat);
    }

    public function addMessageFromAgent(Request $request, HelpdeskChat $chat)
    {
        // Prevent adding messages to closed chats
        if ($chat->status === 'closed') {
            AuditLogService::logAccessDenied($request->user(), 'helpdesk_chats', $chat->id, 'Chat is closed');
            return response()->json(['message' => 'This chat is closed. No new messages can be sent.'], Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'message' => 'required|string',
        ]);

        $message = $this->addMessage($chat, 'agent', $request->user()->id, $request->input('message'));

        // Log agent message
        AuditLogService::log('helpdesk.agent_message', $request->user(), 'helpdesk_messages', $message->id, [
            'chat_id' => $chat->id,
            'message_length' => strlen($request->input('message')),
        ]);

        return response()->json($message, Response::HTTP_CREATED);
    }

    public function transferToHuman(HelpdeskChat $chat)
    {
        $chat->status = 'transferred';
        $chat->save();

        // Add automatic bot message when transferred by agent
        $this->addMessage($chat, 'bot', null, 'This chat has been transferred to a human agent. They will respond shortly.');

        // In a real system, trigger a notification to a human agent queue.

        return response()->json($chat->load('messages'));
    }

    /**
     * Close a chat (user completes the chat)
     */
    public function closeChat(Request $request, HelpdeskChat $chat)
    {
        $this->authorize('view', $chat);

        $chat->status = 'closed';
        $chat->save();

        return response()->json($chat);
    }

    /**
     * Close a chat from agent side
     */
    public function closeChatByAgent(HelpdeskChat $chat)
    {
        $chat->status = 'closed';
        $chat->save();

        return response()->json($chat);
    }

    /**
     * Check if the message is a transfer request
     */
    protected function isTransferRequest(string $message): bool
    {
        $lower = mb_strtolower(trim($message));
        $transferPhrases = [
            'transfer me',
            'i want to talk to a human',
            'talk to human',
            'human agent',
            'transfer to human',
            'connect me to human',
        ];

        foreach ($transferPhrases as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        return false;
    }

    protected function addMessage(HelpdeskChat $chat, string $senderType, ?int $senderId, string $content): HelpdeskMessage
    {
        return HelpdeskMessage::create([
            'helpdesk_chat_id' => $chat->id,
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'content' => $content,
        ]);
    }

    /**
     * Generate bot reply using Google Gemini AI
     */
    protected function generateBotReply(HelpdeskChat $chat, string $userMessage): string
    {
        $apiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY');

        Log::debug('HelpdeskController::generateBotReply', [
            'chat_id' => $chat->id,
            'has_api_key' => !empty($apiKey),
            'api_key' => $apiKey ? substr($apiKey, 0, 4) . str_repeat('*', max(0, strlen($apiKey) - 4)) : null,
            'api_key_length' => $apiKey ? strlen($apiKey) : 0,
            'user_message_length' => strlen($userMessage),
        ]);

        if (!$apiKey) {
            Log::warning('GEMINI_API_KEY not configured, falling back to simple bot');
            return $this->generateSimpleBotReply($userMessage);
        }

        try {
            // Build conversation context from previous messages
            $context = $this->buildConversationContext($chat);

            // System prompt for the helpdesk bot
            $systemPrompt = "You are a helpful customer support assistant for an Event Management application.
            You can help users with:
            - Creating, viewing, updating, and deleting events - which can be done at http://localhost:5173/ page after logging in.
            - Account management - at http://localhost:5173/preferences page user can enable / disable 2fa
            - General questions about the application:
                Helpdesk users can navigate to http://localhost:5173/helpdesk to see chats
                http://localhost:5173/chat at chat page you can start talking with a bot
                Logout button is at the top right corner next to user email address
            - Let the user know that human agents are available on request, but you should try to help them first.
            - in case user needs human agent, they can say \"transfer me\" or \"I want to talk to a human\"

            Be friendly, concise, and helpful. If you cannot answer a question, offer to transfer them to a human agent.
            Keep responses under 200 words.";

            $fullPrompt = $systemPrompt . "\n\nConversation history:\n" . $context . "\n\nUser: " . $userMessage . "\n\nAssistant:";

            // Call Gemini API via HTTP
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $fullPrompt]
                        ]
                    ]
                ]
            ]);

            Log::debug('Gemini API response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->status() === 200) {
                $data = $response->json();
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return trim($data['candidates'][0]['content']['parts'][0]['text']);
                }
            }

            Log::warning('Gemini API returned unexpected response. Status: ' . $response->status());
            return $this->generateSimpleBotReply($userMessage);
        } catch (\Exception $e) {
            Log::error('Gemini API error: ' . $e->getMessage());
            return $this->generateSimpleBotReply($userMessage);
        }
    }

    /**
     * Build conversation context from chat history
     */
    protected function buildConversationContext(HelpdeskChat $chat): string
    {
        $messages = $chat->messages()->orderBy('created_at', 'asc')->get();
        $context = '';

        foreach ($messages as $msg) {
            $sender = $msg->sender_type === 'user' ? 'User' : 'Assistant';
            $context .= $sender . ': ' . $msg->content . "\n";
        }

        return $context ?: 'No previous messages.';
    }

    /**
     * Fallback simple bot reply if Gemini is not available
     */
    protected function generateSimpleBotReply(string $userMessage): string
    {
        $lower = mb_strtolower($userMessage);

        if (str_contains($lower, 'human') || str_contains($lower, 'agent')) {
            return 'I can connect you to a human agent. Please say "transfer me" to confirm.';
        }

        if (str_contains($lower, 'password')) {
            return 'If you forgot your password, use the "Forgot password" link on the login page to request a reset email.';
        }

        if (str_contains($lower, 'event')) {
            return 'You can create, list, update, and delete your events from the Events page after logging in.';
        }

        return 'I am a simple virtual assistant. I can answer questions about events and your account, or I can transfer you to a human agent on request.';
    }
}


