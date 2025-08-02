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

        $permissions = [];
        foreach ($models as $model) {
            foreach ($actions as $action) {
                $permissions[] = [
                    'name' => $action.' '.$model,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $permissions[] = [
            'name' => 'view app performance',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $permissions[] = [
            'name' => 'view queue monitoring',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        Permission::insertOrIgnore($permissions);
    }
}
