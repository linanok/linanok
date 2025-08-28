<?php

namespace App\Models;

use App\Enums\Protocol;
use App\History\MyLogsActivity;
use App\Observers\DomainObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Domain Model
 *
 * Represents domains that can be used for hosting shortened links.
 * Domains can be active/inactive and can optionally host the admin panel.
 * Each domain can be associated with multiple links through a many-to-many relationship.
 *
 * @property \App\Enums\Protocol $protocol The protocol (HTTP/HTTPS) for this domain
 * @property string $host The hostname for this domain
 *
 * @see \App\Observers\DomainObserver
 * @see \App\Enums\Protocol
 */
#[ObservedBy([DomainObserver::class])]
class Domain extends Model
{
    use HasFactory, HasTimestamps, LogsActivity, MyLogsActivity;

    public const UPDATED_AT = null;

    protected $guarded = [];

    /**
     * Get the links associated with this domain.
     *
     * @return BelongsToMany<Link, $this> The links relationship
     */
    public function links(): BelongsToMany
    {
        return $this->belongsToMany(Link::class, LinkDomain::class);
    }

    /**
     * Get the hostname without the port number.
     *
     * This accessor extracts just the hostname part from the host field,
     * removing any port number that might be specified.
     *
     * @return Attribute<string, never> The hostname without port
     */
    public function hostWithoutPort(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                if (empty($this->host)) {
                    return '';
                }

                // Return the full host if it doesn't contain a colon
                if (! Str::contains($this->host, ':')) {
                    return $this->host;
                }

                // Return the part before the colon
                return Str::before($this->host, ':');
            },
        );
    }

    /**
     * Scope a query to only include active domains.
     *
     * @param  Builder  $query  The query builder instance
     * @return Builder The modified query builder
     */
    #[Scope]
    protected function available(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include domains where the admin panel is available.
     *
     * This scope filters domains where the admin panel is marked as available.
     *
     * @param  Builder  $query  The query builder instance.
     * @return Builder The modified query builder.
     */
    #[Scope]
    protected function adminPanelAvailable(Builder $query): Builder
    {
        return $query->where('is_admin_panel_active', true);
    }

    public function __toString()
    {
        return $this->host;
    }

    protected function casts(): array
    {
        return [
            'protocol' => Protocol::class,
        ];
    }
}
