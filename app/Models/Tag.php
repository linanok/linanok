<?php

namespace App\Models;

use App\History\MyLogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Tag Model
 *
 * Represents tags that can be assigned to links for categorization and organization.
 * Tags have a many-to-many relationship with links, allowing multiple tags per link
 * and multiple links per tag.
 *
 * @see \App\Models\Link
 * @see \App\Models\LinkTag
 */
class Tag extends Model
{
    use HasFactory, HasTimestamps, LogsActivity, MyLogsActivity;

    protected $guarded = [];

    public function __toString()
    {
        return $this->name;
    }

    /**
     * Get the links associated with this tag.
     *
     * @return BelongsToMany<Link, $this> The links relationship
     */
    public function links(): BelongsToMany
    {
        return $this->belongsToMany(Link::class, LinkTag::class);
    }

    /**
     * Get all visits for links associated with this tag.
     *
     * @return HasManyThrough<Visit, LinkTag, $this> The visits relationship
     */
    public function visits(): HasManyThrough
    {
        return $this->hasManyThrough(Visit::class, LinkTag::class, 'tag_id', 'link_id', 'id', 'link_id');
    }
}
