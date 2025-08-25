<?php

namespace App\Models;

use App\Observers\VisitObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Visit Model
 *
 * Represents analytics data for individual link visits.
 * Each visit record captures information about when and how a link was accessed,
 * including browser, platform, country, and IP address information.
 *
 * @see \App\Models\Link
 * @see \App\Jobs\SaveVisitJob
 * @see \App\Observers\VisitObserver
 */
#[ObservedBy([VisitObserver::class])]
class Visit extends Model
{
    use HasFactory, HasTimestamps;

    /** @var string|null Disable updated_at timestamp as visits are immutable */
    public const UPDATED_AT = null;

    protected $guarded = [];

    /**
     * Get the link that was visited.
     *
     * @return BelongsTo<Link> The link relationship
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
