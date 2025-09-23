<?php

namespace App\Filament\Imports;

use App\Filament\Actions\Imports\SyncRelationshipImportColumn;
use App\Models\Link;
use App\Models\Tag;
use Filament\Actions\Concerns\CanBeAuthorized;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\Checkbox;
use Illuminate\Support\Number;

class LinkImporter extends Importer
{
    use CanBeAuthorized;

    protected static ?string $model = Link::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->requiredMapping()
                ->rules(fn (?Link $record): array => [
                    function ($attribute, $value, $fail, $validator) {
                        if (! isset($record)) {
                            if (! auth()->user()->can('create link')) {
                                return $fail('You do not have permission to create links.');
                            }
                        } else {
                            if (! is_numeric($value) || $value < 1) {
                                return $fail('The ID must be a positive integer.');
                            }
                            if (! auth()->user()->can('update link', [$record])) {
                                return $fail('You do not have permission to update this link.');
                            }
                        }
                    }])
                ->helperText('Optional: ID of existing link to update')
                ->example(1),

            ImportColumn::make('original_url')
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'url', 'max:2048'])
                ->example('https://google.com/search'),

            ImportColumn::make('slug')
                ->rules(['nullable', 'string', 'max:255'])
                ->helperText('Optional custom slug for the short URL')
                ->example('google'),

            ImportColumn::make('password')
                ->rules(['nullable', 'string', 'max:255'])
                ->helperText('Optional password protection'),

            ImportColumn::make('is_active')
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->examples(['yes', 'true', 'on', 1]),

            ImportColumn::make('available_at')
                ->rules(['nullable', 'date'])
                ->helperText('When the link becomes available (optional)')
                ->example(now()),

            ImportColumn::make('unavailable_at')
                ->rules(['nullable', 'date', 'after:available_at'])
                ->helperText('When the link expires (optional)')
                ->example(now()->addWeek()),

            ImportColumn::make('forward_query_parameters')
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->examples(['yes', 'true', 'on', 1]),

            ImportColumn::make('send_ref_query_parameter')
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->examples(['yes', 'true', 'on', 1]),

            SyncRelationshipImportColumn::make('domains')
                ->relationship(resolveUsing: 'host')
                ->multiple()
                ->helperText('Semicolon-separated tag names (e.g., "localhost:8000;google.com")')
                ->example('localhost:8000;google.com'),

            SyncRelationshipImportColumn::make('tags')
                ->relationship(resolveUsing: function (array $options, array $state) {
                    if (($options['create_missing_tags'] ?? false) && auth()->user()->can('create tag')) {
                        $existingTags = Tag::whereIn('name', $state)->pluck('name');
                        $missingTags = collect($state)->diff($existingTags);
                        $newTagRecords = $missingTags->map(fn ($name) => ['name' => $name])->all();

                        if (! empty($newTagRecords)) {
                            $missingTags->each(fn ($name) => Tag::create(['name' => $name]));
                        }

                        return Tag::whereIn('name', $state)->get();
                    }

                    return ['name'];
                })
                ->multiple()
                ->helperText('Semicolon-separated tag names (e.g., "tag1;tag2;tag3")')
                ->example('tag1;tag2;tag3'),

            ImportColumn::make('description')
                ->rules(['nullable', 'string'])
                ->helperText('Optional description for the link'),

        ];
    }

    public function resolveRecord(): ?Link
    {
        return Link::findOrNew($this->data['id']);
    }

    protected function beforeFill(): void
    {
        // If updating, unset the slug to prevent it from being updated
        if (! empty($this->data['id'])) {
            unset($this->data['slug']);
        }

        // Remove ID from data if it's empty or null to prevent constraint violations
        if (empty($this->data['id'])) {
            unset($this->data['id']);
        }

        // Set default values for fields which are not provided
        if (! isset($this->data['is_active'])) {
            $this->data['is_active'] = true;
        }

        if (! isset($this->data['forward_query_parameters'])) {
            $this->data['forward_query_parameters'] = false;
        }

        if (! isset($this->data['send_ref_query_parameter'])) {
            $this->data['send_ref_query_parameter'] = false;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your link import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Checkbox::make('create_missing_tags')
                ->label('Create missing tags')
                ->visible(fn () => auth()->user()->can('create tag')),
        ];
    }

    private function validateIdWithRecord($attribute, $value, $fail): void
    {
        $record = $this->getRecord(); // <- you have it here

        if (empty($value)) {
            if (! auth()->user()->can('create link')) {
                $fail('You do not have permission to create links.');
            }
        } else {
            if (! is_numeric($value) || $value < 1) {
                $fail('The ID must be a positive integer.');
            }
            if (! auth()->user()->can('update link')) {
                $fail('You do not have permission to update links.');
            }
        }

        if ($record && ! $record->exists) {
            // you can check stuff on the record
        }
    }
}
