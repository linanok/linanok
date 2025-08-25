<?php

namespace Filament\LinkResource\Widgets;

use App\Filament\Resources\Links\Widgets\VisitsByCountryPieChart;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitsByCountryPieChartTest extends TestCase
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

        // Create a user
        $this->user = User::factory()->create();

        // Create a link
        $this->link = Link::factory()->create([
            'original_url' => 'https://example.org',
            'short_path' => 'test-link',
        ]);
        $this->link->domains()->attach($this->domain);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_chart_heading()
    {
        $widget = $this->createWidget();
        $this->assertEquals('Visitors By Country', $this->getProperty($widget, 'chartHeading'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_limit()
    {
        $widget = $this->createWidget();
        $this->assertEquals(10, $this->getProperty($widget, 'limit'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_correct_data_structure()
    {
        // Create visits with different countries
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

        Visit::factory()->count(2)->create([
            'link_id' => $this->link->id,
            'country' => 'United Kingdom',
            'created_at' => now(),
        ]);

        $widget = $this->createWidget();
        $data = $this->invokeMethod($widget, 'getData');

        // Check data structure
        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);

        // Check that we have the correct countries
        $this->assertContains('United States', $data['labels']);
        $this->assertContains('Canada', $data['labels']);
        $this->assertContains('United Kingdom', $data['labels']);

        // Check that the data counts match
        $usIndex = array_search('United States', $data['labels']);
        $caIndex = array_search('Canada', $data['labels']);
        $ukIndex = array_search('United Kingdom', $data['labels']);

        $this->assertEquals(5, $data['datasets'][0]['data'][$usIndex]);
        $this->assertEquals(3, $data['datasets'][0]['data'][$caIndex]);
        $this->assertEquals(2, $data['datasets'][0]['data'][$ukIndex]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_groups_countries_beyond_limit()
    {
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

        $widget = $this->createWidget();
        // Set limit to 5 for testing
        $this->setProperty($widget, 'limit', 5);

        $data = $this->invokeMethod($widget, 'getData');

        // Check that we have 6 items (5 top countries + "Others")
        $this->assertCount(6, $data['labels']);
        $this->assertContains('Others', $data['labels']);
    }

    /**
     * Create a widget instance with the link record set.
     */
    protected function createWidget()
    {
        $widget = new VisitsByCountryPieChart;

        // Set the record property using reflection
        $this->setProperty($widget, 'record', $this->link);

        return $widget;
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

    /**
     * Get protected/private property of a class.
     *
     * @param  object  &$object  Instantiated object that we will get property from.
     * @param  string  $propertyName  Property name to get.
     * @return mixed Property value.
     */
    protected function getProperty(&$object, $propertyName)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
