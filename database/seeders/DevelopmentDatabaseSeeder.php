<?php

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\Link;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ProductionDatabaseSeeder::class);

        $tagManager = Role::create([
            'name' => 'Tag Manager',
        ]);

        $tagManager->givePermissionTo([
            'create tag',
            'view tag',
            'update tag',
            'delete tag',
        ]);

        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@linanok.com',
            'password' => 'admin',
            'is_super_admin' => true,
        ]);

        // Create user with specific link permissions
        $linkManager = User::factory()->create([
            'name' => 'Link Manager',
            'email' => 'LinkManager@linanok.com',
            'password' => 'LinkManager',
        ]);

        // Assign specific permissions
        $linkManager->givePermissionTo([
            'create link',
            'view link',
            'update link',
            'delete link',
        ]);

        Domain::factory()->create([
            'protocol' => 'http',
            'host' => 'localhost:8000',
            'is_admin_panel_active' => true,
            'is_active' => false,
        ]);
        Domain::factory()->create([
            'protocol' => 'http',
            'host' => '127.0.0.1:8000',
            'is_admin_panel_active' => false,
            'is_active' => true,
        ]);

        Tag::factory(10)->create();
        Link::factory(100)
            ->create()
            ->each(function ($link) {
                $link->domains()->attach(Domain::all());
                $link->tags()->attach(
                    Tag::inRandomOrder()->limit(fake()->randomElement(range(1, 10)))->get()
                );
            });

        Link::factory()
            ->hasVisits(1000)
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
