<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\History\MyLogsActivity;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * User Model
 *
 * Represents application users with authentication, authorization, and activity tracking.
 * Users can be assigned roles and permissions through the Spatie Permission package.
 * The model includes account status management and Filament admin panel access control.
 *
 * @see \Spatie\Permission\Traits\HasRoles
 * @see \Filament\Models\Contracts\FilamentUser
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    use MyLogsActivity {
        MyLogsActivity::getActivitylogOptions as superGetActivitylogOptions;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'is_super_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function __toString()
    {
        return $this->name;
    }

    /**
     * Determine if the user can access the Filament admin panel.
     *
     * Only active users are allowed to access the admin panel.
     *
     * @param  Panel  $panel  The Filament panel instance
     * @return bool True if the user can access the panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    /**
     * Scope a query to only include active users.
     *
     * @param  Builder  $query  The query builder instance
     * @return Builder The modified query builder
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return $this->superGetActivitylogOptions()
            ->logExcept(['password', 'created_at', 'updated_at']);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }
}
