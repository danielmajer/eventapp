<?php

namespace App\Console\Commands;

use App\Services\SecurityMonitoringService;
use Illuminate\Console\Command;

class SecurityMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:monitor 
                            {--stats : Show security statistics}
                            {--alerts : Check for security alerts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor security events and generate alerts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('stats')) {
            return $this->showStats();
        }

        if ($this->option('alerts')) {
            return $this->checkAlerts();
        }

        // Default: show both
        $this->showStats();
        $this->newLine();
        return $this->checkAlerts();
    }

    /**
     * Show security statistics
     */
    protected function showStats(): int
    {
        $this->info('Security Statistics (Last 24 Hours):');
        $this->newLine();

        $stats = SecurityMonitoringService::getSecurityStats(24);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Failed Logins', $stats['failed_logins'] ?? 0],
                ['Successful Logins', $stats['successful_logins'] ?? 0],
                ['Access Denied', $stats['access_denied'] ?? 0],
                ['Password Resets', $stats['password_resets'] ?? 0],
                ['MFA Enabled', $stats['mfa_enabled'] ?? 0],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Check for security alerts
     */
    protected function checkAlerts(): int
    {
        $this->info('Checking for Security Alerts...');
        $this->newLine();

        $alerts = SecurityMonitoringService::checkSecurityAlerts();

        if (empty($alerts)) {
            $this->info('✓ No security alerts detected.');
            return Command::SUCCESS;
        }

        foreach ($alerts as $alert) {
            $severity = $alert['severity'];
            $icon = $severity === 'high' ? '⚠️' : 'ℹ️';
            $color = $severity === 'high' ? 'error' : 'warn';

            $this->{$color}("{$icon} [{$severity}] {$alert['message']}");
            
            if (!empty($alert['data'])) {
                $this->table(
                    array_keys((array) $alert['data'][0] ?? []),
                    array_map(fn($item) => (array) $item, $alert['data'])
                );
            }
        }

        // Send alerts to log
        SecurityMonitoringService::sendAlerts($alerts);

        return Command::SUCCESS;
    }
}

