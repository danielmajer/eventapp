<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuditLogService
{
    /**
     * Log a security-sensitive operation
     *
     * @param  string  $action
     * @param  mixed  $user
     * @param  string  $resourceType
     * @param  mixed  $resourceId
     * @param  array  $metadata
     * @return void
     */
    public static function log(string $action, $user, string $resourceType = null, $resourceId = null, array $metadata = []): void
    {
        $userId = $user ? (is_object($user) ? $user->id : $user) : null;
        $userEmail = $user && is_object($user) ? $user->email : null;

        $logData = [
            'action' => $action,
            'user_id' => $userId,
            'user_email' => $userEmail,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
            'metadata' => $metadata,
        ];

        // Log to Laravel log
        Log::channel('security')->info('Audit: ' . $action, $logData);

        // Optionally store in database for audit trail
        try {
            DB::table('audit_logs')->insert([
                'action' => $action,
                'user_id' => $userId,
                'user_email' => $userEmail,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode($metadata),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // If audit_logs table doesn't exist, just log to file
            Log::warning('Failed to write audit log to database: ' . $e->getMessage());
        }
    }

    /**
     * Log authentication events
     */
    public static function logAuth(string $event, $user, array $metadata = []): void
    {
        self::log("auth.{$event}", $user, 'user', $user?->id, $metadata);
    }

    /**
     * Log resource creation
     */
    public static function logCreate($user, string $resourceType, $resourceId, array $metadata = []): void
    {
        self::log('create', $user, $resourceType, $resourceId, $metadata);
    }

    /**
     * Log resource update
     */
    public static function logUpdate($user, string $resourceType, $resourceId, array $metadata = []): void
    {
        self::log('update', $user, $resourceType, $resourceId, $metadata);
    }

    /**
     * Log resource deletion
     */
    public static function logDelete($user, string $resourceType, $resourceId, array $metadata = []): void
    {
        self::log('delete', $user, $resourceType, $resourceId, $metadata);
    }

    /**
     * Log permission/access denied
     */
    public static function logAccessDenied($user, string $resourceType, $resourceId, string $reason = null): void
    {
        self::log('access_denied', $user, $resourceType, $resourceId, ['reason' => $reason]);
    }

    /**
     * Log security violations
     */
    public static function logSecurityViolation(string $violation, $user = null, array $metadata = []): void
    {
        $logData = [
            'violation' => $violation,
            'user_id' => $user ? (is_object($user) ? $user->id : $user) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
            'metadata' => $metadata,
        ];

        Log::channel('security')->warning('Security Violation: ' . $violation, $logData);
    }
}

