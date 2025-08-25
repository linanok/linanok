<?php

namespace App\Observers;

use App\Models\Link;
use App\Models\Visit;

/**
 * Visit Observer
 *
 * Handles visit model events to maintain visit count statistics.
 * This observer automatically increments the visit_count on the associated
 * link whenever a new visit record is created.
 *
 * @see \App\Models\Visit
 * @see \App\Models\Link
 */
class VisitObserver
{
    /**
     * Handle the Visit "created" event.
     *
     * Automatically increments the visit_count field on the associated link
     * to maintain accurate visit statistics without requiring additional
     * database queries in the main application flow.
     *
     * @param  Visit  $visit  The visit record that was created
     */
    public function created(Visit $visit): void
    {
        Link::where('id', $visit->link_id)->increment('visit_count');
    }
}
