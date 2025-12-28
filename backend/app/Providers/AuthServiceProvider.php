<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\HelpdeskChat;
use App\Policies\EventPolicy;
use App\Policies\HelpdeskChatPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Event::class => EventPolicy::class,
        HelpdeskChat::class => HelpdeskChatPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('act-as-helpdesk-agent', function ($user) {
            // Force refresh from database to ensure we have latest role
            if ($user->exists) {
                $user->refresh();
            }

            $role = $user->role;
            $result = in_array($role, ['helpdesk_agent', 'admin']);

            return $result;
        });
    }
}


