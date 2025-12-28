<?php

namespace App\Policies;

use App\Models\HelpdeskChat;
use App\Models\User;
use App\Services\AuditLogService;

class HelpdeskChatPolicy
{
    public function view(User $user, HelpdeskChat $chat): bool
    {
        // User can view own chat; helpdesk agents can view any
        $allowed = $user->id === $chat->user_id || 
                   ($user->role ?? null) === 'helpdesk_agent' || 
                   ($user->role ?? null) === 'admin';
        
        if (!$allowed) {
            AuditLogService::logAccessDenied($user, 'helpdesk_chats', $chat->id, 'User does not have access to this chat');
        }
        
        return $allowed;
    }
}


