<?php

namespace App\Filament\Actions\Imports;

use Filament\Actions\Imports\ImportColumn;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SyncRelationshipImportColumn extends ImportColumn
{
    public function saveRelationships(mixed $state): void
    {
        if ($this->saveRelationshipsUsing) {
            $this->evaluate($this->saveRelationshipsUsing, [
                'state' => $state,
            ]);

            return;
        }

        $relationship = $this->getRelationship();

        if (! $relationship instanceof BelongsToMany) {
            return;
        }

        $relationship->sync($this->resolveRelatedRecords($state));
    }
}
