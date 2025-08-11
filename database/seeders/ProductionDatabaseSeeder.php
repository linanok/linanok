<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class ProductionDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $models = ['link', 'domain', 'tag', 'user', 'role'];
        $actions = ['view', 'create', 'update', 'delete'];

        // Generate all CRUD permission names
        $permissionNames = collect($models)
            ->flatMap(fn ($model) => collect($actions)->map(fn ($action) => "$action $model"))
            ->merge([
                'view app performance',
                'view queue monitoring',
            ])
            ->unique()
            ->values();

        // Find which permissions don't exist yet
        $existingPermissionNames = Permission::where('guard_name', 'web')->pluck('name');
        $newPermissions = $permissionNames
            ->diff($existingPermissionNames)
            ->map(fn ($name) => [
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->toArray();

        // Insert only the missing ones
        if (! empty($newPermissions)) {
            Permission::insert($newPermissions);
        }
    }
}
