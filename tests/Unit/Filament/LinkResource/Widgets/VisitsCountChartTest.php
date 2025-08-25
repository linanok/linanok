<?php

namespace Filament\LinkResource\Widgets;

use App\Filament\Resources\Links\Widgets\VisitsCountChart;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitsCountChartTest extends TestCase
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
    public function it_has_correct_chart_type()
    {
        $widget = $this->createWidget();
        $this->assertEquals('line', $this->invokeMethod($widget, 'getType'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_interval_based_on_filter()
    {
        $widget = $this->createWidget();

        // Test different filters
        $this->setProperty($widget, 'filter', 'today');
        $this->assertEquals('hour', $this->invokeMethod($widget, 'getInterval'));

        $this->setProperty($widget, 'filter', 'week');
        $this->assertEquals('day', $this->invokeMethod($widget, 'getInterval'));

        $this->setProperty($widget, 'filter', 'month');
        $this->assertEquals('day', $this->invokeMethod($widget, 'getInterval'));

        $this->setProperty($widget, 'filter', 'year');
        $this->assertEquals('month', $this->invokeMethod($widget, 'getInterval'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_formats_labels_correctly()
    {
        $widget = $this->createWidget();
        $date = '2023-01-15 14:30:00';

        // Test different intervals
        $this->assertEquals('02:30 PM', $this->invokeMethod($widget, 'formatLabel', [$date, 'hour']));
        $this->assertEquals('Jan 15', $this->invokeMethod($widget, 'formatLabel', [$date, 'day']));
        $this->assertEquals('Jan 15', $this->invokeMethod($widget, 'formatLabel', [$date, 'week']));
        $this->assertEquals('Jan 2023', $this->invokeMethod($widget, 'formatLabel', [$date, 'month']));
        $this->assertEquals('2023', $this->invokeMethod($widget, 'formatLabel', [$date, 'year']));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_correct_data_structure()
    {
        // Create some visits
        $today = Carbon::today();

        // Create visits for today
        Visit::factory()->count(3)->create([
            'link_id' => $this->link->id,
            'created_at' => $today,
            'ip' => '192.168.1.1',
        ]);

        // Create visits for yesterday with different IPs
        Visit::factory()->count(2)->create([
            'link_id' => $this->link->id,
            'created_at' => $today->copy()->subDay(),
            'ip' => '192.168.1.2',
        ]);

        Visit::factory()->count(1)->create([
            'link_id' => $this->link->id,
            'created_at' => $today->copy()->subDay(),
            'ip' => '192.168.1.3',
        ]);

        $widget = $this->createWidget();
        $data = $this->invokeMethod($widget, 'getData');

        // Check data structure
        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);

        // Check that we have two datasets (total visits and unique visitors)
        $this->assertCount(2, $data['datasets']);
        $this->assertEquals('Total Visits', $data['datasets'][0]['label']);
        $this->assertEquals('Unique Visitors', $data['datasets'][1]['label']);
    }

    /**
     * Create a widget instance with the link record set.
     */
    protected function createWidget()
    {
        $widget = new VisitsCountChart;

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
}
