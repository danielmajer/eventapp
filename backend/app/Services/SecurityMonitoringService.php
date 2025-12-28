<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SecurityMonitoringService
{
    /**
     * Check for suspicious patterns and generate alerts
     *
     * @return array
     */
    public static function checkSecurityAlerts(): array
    {
        $alerts = [];

        // Check for multiple failed login attempts from same IP
        $failedLogins = self::checkFailedLoginAttempts();
        if (!empty($failedLogins)) {
            $alerts[] = [
                'type' => 'multiple_failed_logins',
                'severity' => 'high',
                'message' => 'Multiple failed login attempts detected',
                'data' => $failedLogins,
            ];
        }

        // Check for unusual access patterns
        $unusualAccess = self::checkUnusualAccess();
        if (!empty($unusualAccess)) {
            $alerts[] = [
                'type' => 'unusual_access',
                'severity' => 'medium',
                'message' => 'Unusual access patterns detected',
                'data' => $unusualAccess,
            ];
        }

        // Check for access denied patterns
        $accessDenied = self::checkAccessDeniedPatterns();
        if (!empty($accessDenied)) {
            $alerts[] = [
                'type' => 'access_denied_patterns',
                'severity' => 'medium',
                'message' => 'Multiple access denied attempts detected',
                'data' => $accessDenied,
            ];
        }

        return $alerts;
    }

    /**
     * Check for multiple failed login attempts
     *
     * @return array
     */
    protected static function checkFailedLoginAttempts(): array
    {
        try {
            $recentFailed = DB::table('audit_logs')
                ->where('action', 'auth.login_failed')
                ->where('created_at', '>=', Carbon::now()->subHours(1))
                ->select('ip_address', DB::raw('COUNT(*) as attempts'))
                ->groupBy('ip_address')
                ->having('attempts', '>=', 5)
                ->get()
                ->toArray();

            return $recentFailed;
        } catch (\Exception $e) {
            // Table might not exist yet
            return [];
        }
    }

    /**
     * Check for unusual access patterns
     *
     * @return array
     */
    protected static function checkUnusualAccess(): array
    {
        try {
            // Check for same user accessing from multiple IPs in short time
            $unusual = DB::table('audit_logs')
                ->where('created_at', '>=', Carbon::now()->subHours(1))
                ->whereNotNull('user_id')
                ->select('user_id', 'user_email', DB::raw('COUNT(DISTINCT ip_address) as unique_ips'))
                ->groupBy('user_id', 'user_email')
                ->having('unique_ips', '>=', 3)
                ->get()
                ->toArray();

            return $unusual;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check for access denied patterns
     *
     * @return array
     */
    protected static function checkAccessDeniedPatterns(): array
    {
        try {
            $denied = DB::table('audit_logs')
                ->where('action', 'access_denied')
                ->where('created_at', '>=', Carbon::now()->subHours(1))
                ->select('user_id', 'ip_address', DB::raw('COUNT(*) as attempts'))
                ->groupBy('user_id', 'ip_address')
                ->having('attempts', '>=', 3)
                ->get()
                ->toArray();

            return $denied;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Send security alerts
     *
     * @param  array  $alerts
     * @return void
     */
    public static function sendAlerts(array $alerts): void
    {
        foreach ($alerts as $alert) {
            if ($alert['severity'] === 'high') {
                Log::channel('security')->critical('SECURITY ALERT: ' . $alert['message'], $alert);
            } else {
                Log::channel('security')->warning('SECURITY ALERT: ' . $alert['message'], $alert);
            }
        }
    }

    /**
     * Get security statistics
     *
     * @param  int  $hours
     * @return array
     */
    public static function getSecurityStats(int $hours = 24): array
    {
        try {
            $since = Carbon::now()->subHours($hours);

            $stats = [
                'failed_logins' => DB::table('audit_logs')
                    ->where('action', 'auth.login_failed')
                    ->where('created_at', '>=', $since)
                    ->count(),
                'successful_logins' => DB::table('audit_logs')
                    ->where('action', 'auth.login_success')
                    ->where('created_at', '>=', $since)
                    ->count(),
                'access_denied' => DB::table('audit_logs')
                    ->where('action', 'access_denied')
                    ->where('created_at', '>=', $since)
                    ->count(),
                'password_resets' => DB::table('audit_logs')
                    ->where('action', 'auth.password_reset_completed')
                    ->where('created_at', '>=', $since)
                    ->count(),
                'mfa_enabled' => DB::table('audit_logs')
                    ->where('action', 'auth.mfa_enabled')
                    ->where('created_at', '>=', $since)
                    ->count(),
            ];

            return $stats;
        } catch (\Exception $e) {
            return [];
        }
    }
}

