<?php

namespace App\Providers;

use App\Models\Domain;
use App\Models\Link;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Policies\DomainPolicy;
use App\Policies\LinkPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * Authorization Service Provider
 *
 * Configures the authorization system for the application by:
 * - Mapping models to their corresponding policies
 * - Setting up the super admin gate that bypasses all permission checks
 * - Integrating with the Spatie Permission package
 *
 * @see \App\Policies\BasePolicy
 * @see \Spatie\Permission\PermissionServiceProvider
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * Maps each model class to its corresponding policy class for authorization.
     * These policies extend BasePolicy and implement standard CRUD permissions.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Link::class => LinkPolicy::class,
        Domain::class => DomainPolicy::class,
        Tag::class => TagPolicy::class,
        User::class => UserPolicy::class,
        Permission::class => PermissionPolicy::class,
        Role::class => RolePolicy::class,
    ];

    /**
     * Sets up the authorization system by:
     * - Registering all model policies
     * - Creating a gate that grants super admins all permissions
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Implicitly grant super admin users all permissions
        // This gate runs before all other authorization checks
        Gate::before(function (User $user, $ability) {
            return $user->is_super_admin ? true : null;
        });
        Gate::define('viewPulse', function (User $user) {
            return current_domain()?->is_admin_panel_active && $user->can('view app performance');
        });
        Gate::define('viewHorizon', function (User $user) {
            return current_domain()?->is_admin_panel_active && $user->can('view queue monitoring');
        });
    }
}
