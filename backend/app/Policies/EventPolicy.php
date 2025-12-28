<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use App\Services\AuditLogService;

class EventPolicy
{
    public function view(User $user, Event $event): bool
    {
        $allowed = $user->id === $event->user_id;
        
        if (!$allowed) {
            AuditLogService::logAccessDenied($user, 'events', $event->id, 'User does not own this event');
        }
        
        return $allowed;
    }

    public function update(User $user, Event $event): bool
    {
        $allowed = $user->id === $event->user_id;
        
        if (!$allowed) {
            AuditLogService::logAccessDenied($user, 'events', $event->id, 'User does not own this event');
        }
        
        return $allowed;
    }

    public function delete(User $user, Event $event): bool
    {
        $allowed = $user->id === $event->user_id;
        
        if (!$allowed) {
            AuditLogService::logAccessDenied($user, 'events', $event->id, 'User does not own this event');
        }
        
        return $allowed;
    }
}


