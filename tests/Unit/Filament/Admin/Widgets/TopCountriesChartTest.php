<?php

namespace Filament\Admin\Widgets;

use App\Filament\Admin\Widgets\TopCountriesChart;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TopCountriesChartTest extends TestCase
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

        Livewire::test(TopCountriesChart::class)
            ->assertSuccessful();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_chart_type()
    {
        $this->actingAs($this->user);

        $widget = new TopCountriesChart;
        $this->assertEquals('pie', $this->invokeMethod($widget, 'getType'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_correct_data_structure()
    {
        $this->actingAs($this->user);

        // Create some visits with different countries
        Visit::factory()->count(5)->create([
            'link_id' => $this->link->id,
            'country' => 'United States',
            'created_at' => now(),
        ]);

        Visit::factory()->count(3)->create([
            'link_id' => $this->link->id,
            'country' => 'Canada',
            'created_at' => now(),
        ]);

        $widget = new TopCountriesChart;
        $data = $this->invokeMethod($widget, 'getData');

        // Check data structure
        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);

        // Check that we have the correct countries
        $this->assertContains('United States', $data['labels']);
        $this->assertContains('Canada', $data['labels']);

        // Check that the data counts match
        $usIndex = array_search('United States', $data['labels']);
        $caIndex = array_search('Canada', $data['labels']);

        $this->assertEquals(5, $data['datasets'][0]['data'][$usIndex]);
        $this->assertEquals(3, $data['datasets'][0]['data'][$caIndex]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_groups_countries_beyond_limit()
    {
        $this->actingAs($this->user);

        // Create visits for 12 different countries (more than the default limit of 10)
        $countries = [
            'United States', 'Canada', 'United Kingdom', 'Germany', 'France',
            'Spain', 'Italy', 'Japan', 'Australia', 'Brazil', 'China', 'India',
        ];

        foreach ($countries as $index => $country) {
            Visit::factory()->count($index + 1)->create([
                'link_id' => $this->link->id,
                'country' => $country,
                'created_at' => now(),
            ]);
        }

        $widget = new TopCountriesChart;
        // Set limit to 5 for testing
        $this->setProperty($widget, 'limit', 5);

        $data = $this->invokeMethod($widget, 'getData');

        // Check that we have 6 items (5 top countries + "Others")
        $this->assertCount(6, $data['labels']);
        $this->assertContains('Others', $data['labels']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_color_palette()
    {
        $this->actingAs($this->user);

        $widget = new TopCountriesChart;
        $colors = $this->invokeMethod($widget, 'generateColorPalette', [5]);

        // Check that we get the correct number of colors
        $this->assertCount(5, $colors);

        // Check that all colors are valid hex colors
        foreach ($colors as $color) {
            $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/i', $color);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_is_only_visible_to_users_with_view_link_permission()
    {
        // User with permission
        $this->actingAs($this->user);
        $this->assertTrue(TopCountriesChart::canView());

        // User without permission
        $userWithoutPermission = User::factory()->create();
        $this->actingAs($userWithoutPermission);
        $this->assertFalse(TopCountriesChart::canView());
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

    /**
     * Set protected/private property of a class.
     *
     * @param  object  &$object  Instantiated object that we will set property on.
     * @param  string  $propertyName  Property name to set.
     * @param  mixed  $value  Value to set property to.
     * @return void
     */
    protected function setProperty(&$object, $propertyName, $value)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
