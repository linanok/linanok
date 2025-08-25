<?php

namespace Filament\Admin\Widgets;

use App\Filament\Admin\Widgets\StatsOverview;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StatsOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Domain $domain;

    protected Link $link;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a domain for testing
        $this->domain = Domain::factory()->create([
            'host' => 'example.com',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        // Create a user with permissions
        $this->user = User::factory()->create();
        $role = Role::create(['name' => 'Admin']);
        Permission::create(['name' => 'view link']);
        $role->givePermissionTo('view link');
        $this->user->assignRole('Admin');

        // Create a link
        $this->link = Link::factory()->create([
            'original_url' => 'https://example.org',
            'short_path' => 'test-link',
        ]);
        $this->link->domains()->attach($this->domain);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_render_the_widget()
    {
        $this->actingAs($this->user);

        Livewire::test(StatsOverview::class)
            ->assertSuccessful();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_displays_correct_number_of_stats()
    {
        $this->actingAs($this->user);

        $component = new StatsOverview;
        $stats = $this->invokeMethod($component, 'getStats');

        $this->assertCount(6, $stats);
        $this->assertContainsOnlyInstancesOf(Stat::class, $stats);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_displays_correct_total_links_stat()
    {
        $this->actingAs($this->user);

        // Create additional links
        Link::factory()->count(4)->create()->each(function ($link) {
            $link->domains()->attach($this->domain);
        });

        $component = new StatsOverview;
        $stats = $this->invokeMethod($component, 'getStats');

        // First stat should be Total Links
        $totalLinksStat = $stats[0];
        $this->assertEquals('Total Links', $totalLinksStat->getLabel());
        $this->assertEquals('5', $totalLinksStat->getValue()); // 1 from setup + 4 created here
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_displays_correct_visits_stat()
    {
        $this->actingAs($this->user);

        // Create some visits
        Visit::factory()->count(10)->create([
            'link_id' => $this->link->id,
            'created_at' => now(),
        ]);

        $component = new StatsOverview;
        $stats = $this->invokeMethod($component, 'getStats');

        // Second stat should be Visits This Month
        $visitsStat = $stats[1];
        $this->assertEquals('Visits This Month', $visitsStat->getLabel());
        $this->assertEquals('10', $visitsStat->getValue());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_is_only_visible_to_users_with_view_link_permission()
    {
        // User with permission
        $this->actingAs($this->user);
        $this->assertTrue(StatsOverview::canView());

        // User without permission
        $userWithoutPermission = User::factory()->create();
        $this->actingAs($userWithoutPermission);
        $this->assertFalse(StatsOverview::canView());
    }

    /**
     * Call protected/private method of a class.
     *
     * @param  object  &$object  Instantiated object that we will run method on.
     * @param  string  $methodName  Method name to call
     * @param  array  $parameters  Array of parameters to pass into method.
     * @return mixed Method return.
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
