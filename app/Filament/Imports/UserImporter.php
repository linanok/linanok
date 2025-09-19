<?php

namespace App\Filament\Imports;

use App\Filament\Actions\Imports\SyncRelationshipImportColumn;
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;

class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->requiredMapping()
                ->rules([function ($attribute, $value, $fail) {
                    if (empty($value)) {
                        if (! auth()->user()->can('create user')) {
                            return $fail('You do not have permission to create users.');
                        }
                    } else {
                        $record = User::find($value);

                        if (! is_numeric($value) || $value < 1) {
                            return $fail('The ID must be a positive integer.');
                        }
                        if (! auth()->user()->can('update user', [$record])) {
                            return $fail('You do not have permission to update this user.');
                        }
                    }
                }])
                ->helperText('Optional: ID of existing user to update')
                ->example(1),

            ImportColumn::make('name')
                ->rules(['required', 'string', 'max:255'])
                ->helperText('Full name of the user')
                ->example('John Doe'),

            ImportColumn::make('email')
                ->rules(fn (?User $record): array => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($record?->id),
                ])
                ->helperText('Email address (must be unique)')
                ->example('john@gmail.com'),

            ImportColumn::make('password')
                ->requiredMappingForNewRecordsOnly()
                ->rules(['nullable', 'required_if:id,null', 'string', 'min:8', 'max:255'])
                ->sensitive()
                ->helperText('Password (required for new users, optional for updates)'),

            ImportColumn::make('is_active')
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->helperText('Whether the user account is active (default: true)')
                ->example('yes'),

            ImportColumn::make('is_super_admin')
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->helperText('Whether the user has super admin privileges (default: false)')
                ->example('no'),

            SyncRelationshipImportColumn::make('roles')
                ->relationship(resolveUsing: 'name')
                ->multiple()
                ->helperText('Semicolon-separated role names (e.g., "admin;editor;viewer")')
                ->example('DevOps Team'),

            SyncRelationshipImportColumn::make('permissions')
                ->relationship(resolveUsing: 'name')
                ->multiple()
                ->helperText('Semicolon-separated permission names (e.g., "create user;update link")')
                ->example('create user;update link'),
        ];
    }

    public function resolveRecord(): ?User
    {
        return User::findOrNew($this->data['id']);
    }

    protected function beforeFill(): void
    {
        // Remove ID from data if it's empty or null to prevent constraint violations
        if (empty($this->data['id'])) {
            unset($this->data['id']);
        }

        // Remove password from data if it's empty or null to prevent unwanted password change
        if (empty($this->data['password'])) {
            unset($this->data['password']);
        }

        // Set default values for boolean fields if not provided
        if (! isset($this->data['is_active'])) {
            $this->data['is_active'] = true;
        }

        if (! isset($this->data['is_super_admin'])) {
            $this->data['is_super_admin'] = false;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your user import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
