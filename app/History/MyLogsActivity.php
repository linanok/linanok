<?php

namespace App\History;

use Spatie\Activitylog\LogOptions;

/**
 * My Logs Activity Trait
 *
 * Provides standardized activity logging configuration for models.
 * This trait configures the Spatie Activity Log package to log all
 * model changes except timestamps, and only logs when there are
 * actual changes (dirty attributes).
 *
 * @see \Spatie\Activitylog\Traits\LogsActivity
 * @see \Spatie\Activitylog\LogOptions
 */
trait MyLogsActivity
{
    /**
     * Get the activity log options for this model.
     *
     * Configures logging to:
     * - Log all attributes
     * - Only log when attributes are dirty (changed)
     * - Exclude timestamp fields from logging
     * - Don't submit empty logs
     *
     * @return LogOptions The configured log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->logExcept(['created_at', 'updated_at'])
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn () => $this->__toString());
    }
}
