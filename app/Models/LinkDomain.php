<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Link Domain Pivot Model
 *
 * Represents the many-to-many relationship between links and domains.
 * This pivot table allows links to be associated with multiple domains
 * and domains to host multiple links.
 *
 * @see \App\Models\Link
 * @see \App\Models\Domain
 */
class LinkDomain extends Model
{
    /** @var string The database table name */
    protected $table = 'link_domain';

    /** @var bool Disable timestamps for this pivot table */
    public $timestamps = false;

    /**
     * Get the link that owns this relationship.
     *
     * @return BelongsTo<Link, $this> The link relationship
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    /**
     * Get the domain that owns this relationship.
     *
     * @return BelongsTo<Domain, $this> The domain relationship
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
