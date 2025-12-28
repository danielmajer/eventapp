<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\HelpdeskChat;
use App\Models\HelpdeskMessage;

class HelpdeskChatSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create a regular user for sample chats
        $regularUser = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('password123'),
            ]
        );

        // Get or create a helpdesk agent
        $agent = User::firstOrCreate(
            ['email' => 'agent@example.com'],
            [
                'name' => 'Helpdesk Agent',
                'password' => Hash::make('password123'),
                'role' => 'helpdesk_agent',
            ]
        );

        // Chat 1: Open chat with user and bot messages
        $chat1 = HelpdeskChat::create([
            'user_id' => $regularUser->id,
            'status' => 'open',
        ]);

        HelpdeskMessage::create([
            'helpdesk_chat_id' => $chat1->id,
            'sender_type' => 'user',
            'sender_id' => $regularUser->id,
            'content' => 'Hello, I need help with creating an event.',
        ]);

        HelpdeskMessage::create([
            'helpdesk_chat_id' => $chat1->id,
            'sender_type' => 'bot',
            'sender_id' => null,
            'content' => 'You can create, list, update, and delete your events from the Events page after logging in.',
        ]);

        HelpdeskMessage::create([
            'helpdesk_chat_id' => $chat1->id,
            'sender_type' => 'user',
            'sender_id' => $regularUser->id,
            'content' => 'How do I set the date and time?',
        ]);

        HelpdeskMessage::create([
            'helpdesk_chat_id' => $chat1->id,
            'sender_type' => 'bot',
            'sender_id' => null,
            'content' => 'When creating an event, use the "Occurrence" field to select both date and time. The system will save it in your timezone.',
        ]);

        // Chat 2: Transferred chat with agent response
        $chat2 = HelpdeskChat::create([
            'user_id' => $regularUser->id,
            'status' => 'transferred',
        ]);

        HelpdeskMessage::create([
            'helpdesk_chat_id' => $chat2->id,
            'sender_type' => 'user',
            'sender_id' => $regularUser->id,
            'content' => 'I forgot my password, can you help me reset it?',
        ]);

        HelpdeskMessage::create([
            'helpdesk_chat_id' => $chat2->id,
            'sender_type' => 'bot',
            'sender_id' => null,
            'content' => 'If you forgot your password, use the "Forgot password" link on the login page to request a reset email.',
        ]);

        HelpdeskMessage::create([
            'helpdesk_chat_id' => $chat2->id,
            'sender_type' => 'user',
            'sender_id' => $regularUser->id,
            'content' => 'I tried that but I did not receive the email. Can I speak to a human agent?',
        ]);

        HelpdeskMessage::create([
            'helpdesk_chat_id' => $chat2->id,
            'sender_type' => 'bot',
            'sender_id' => null,
            'content' => 'I can connect you to a human agent. Please say "transfer me" to confirm.',
        ]);

        HelpdeskMessage::create([
            'helpdesk_chat_id' => $chat2->id,
            'sender_type' => 'agent',
            'sender_id' => $agent->id,
            'content' => 'Hello! I can help you with your password reset. Please check your spam folder first. If you still don\'t see it, I can manually reset it for you. What email address did you use?',
        ]);

        // Chat 3: Closed chat
        $chat3 = HelpdeskChat::create([
            'user_id' => $regularUser->id,
            'status' => 'closed',
        ]);

        HelpdeskMessage::create([
            'helpdesk_chat_id' => $chat3->id,
            'sender_type' => 'user',
            'sender_id' => $regularUser->id,
            'content' => 'How do I delete an event?',
        ]);

        HelpdeskMessage::create([
            'helpdesk_chat_id' => $chat3->id,
            'sender_type' => 'bot',
            'sender_id' => null,
            'content' => 'You can create, list, update, and delete your events from the Events page after logging in.',
        ]);

        HelpdeskMessage::create([
            'helpdesk_chat_id' => $chat3->id,
            'sender_type' => 'agent',
            'sender_id' => $agent->id,
            'content' => 'To delete an event, go to the Events page, find the event you want to delete, and click the "Delete" button. This action cannot be undone.',
        ]);

        HelpdeskMessage::create([
            'helpdesk_chat_id' => $chat3->id,
            'sender_type' => 'user',
            'sender_id' => $regularUser->id,
            'content' => 'Thank you! That worked perfectly.',
        ]);

        // Chat 4: Recent open chat
        $chat4 = HelpdeskChat::create([
            'user_id' => $regularUser->id,
            'status' => 'open',
        ]);

        HelpdeskMessage::create([
            'helpdesk_chat_id' => $chat4->id,
            'sender_type' => 'user',
            'sender_id' => $regularUser->id,
            'content' => 'Is there a way to edit event descriptions?',
        ]);

        HelpdeskMessage::create([
            'helpdesk_chat_id' => $chat4->id,
            'sender_type' => 'bot',
            'sender_id' => null,
            'content' => 'You can create, list, update, and delete your events from the Events page after logging in.',
        ]);

        $this->command->info('Created 4 sample helpdesk chats with messages.');
    }
}

