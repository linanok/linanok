<?php

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\Link;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ProductionDatabaseSeeder::class);

        $tagManagerRole = Role::create([
            'name' => 'Tag Manager',
        ]);
        $tagManagerRole->givePermissionTo([
            'create tag',
            'view tag',
            'update tag',
            'delete tag',
        ]);

        $linkManagerRole = Role::create([
            'name' => 'Link Manager',
        ]);
        $linkManagerRole->givePermissionTo([
            'create link',
            'view link',
            'update link',
            'delete link',
        ]);

        $userManagerRole = Role::create([
            'name' => 'User Manager',
        ]);
        $userManagerRole->givePermissionTo([
            'create user',
            'view user',
            'update user',
            'delete user',
        ]);

        $viewerUser = User::factory()->create([
            'name' => 'Viewer',
            'email' => 'demo@linanok.com',
            'password' => 'demo',
            'is_super_admin' => false,
        ]);
        $viewerUser->givePermissionTo([
            'view link',
            'view domain',
            'view tag',
            'view user',
            'view role',
            'view app performance',
            'view queue monitoring',
        ]);

        Domain::factory()->create([
            'protocol' => 'https',
            'host' => 'demo.linanok.com',
            'is_admin_panel_active' => true,
            'is_active' => false,
        ]);
        Domain::factory()->create([
            'protocol' => 'https',
            'host' => 's.linanok.com',
            'is_admin_panel_active' => false,
            'is_active' => true,
        ]);

        Tag::factory(10)->create();
        Link::factory(10)
            ->create()
            ->each(function ($link) {
                $link->domains()->attach(Domain::all());
                $link->tags()->attach(
                    Tag::inRandomOrder()->limit(fake()->randomElement(range(1, 10)))->get()
                );
            });

        Link::factory()
            ->create([
                'original_url' => 'https://google.com/',
                'slug' => 'google',
                'is_active' => true,
            ])->domains()->attach(Domain::orderBy('id', 'desc')->first());
        Link::factory()->create([
            'original_url' => 'https://linanok.com/',
            'slug' => 'linanok',
            'password' => 'linanok',
            'is_active' => true,
        ])->domains()->attach(Domain::all());
        Link::factory()->create([
            'original_url' => 'https://unavailable.com/',
            'slug' => 'unavailable',
            'unavailable_at' => '2020-01-01',
            'is_active' => true,
        ])->domains()->attach(Domain::all());
        Link::factory()->create([
            'original_url' => 'https://example.com/?a=b',
            'slug' => 'example',
            'send_ref_query_parameter' => true,
            'is_active' => true,
        ])->domains()->attach(Domain::all());
    }
}
