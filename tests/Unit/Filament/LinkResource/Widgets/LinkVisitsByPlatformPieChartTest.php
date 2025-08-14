<?php

namespace Filament\LinkResource\Widgets;

use App\Filament\Resources\Links\Widgets\LinkVisitsByPlatformPieChart;
use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkVisitsByPlatformPieChartTest extends TestCase
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
        $this->assertEquals('Visitors By Platform', $this->getProperty($widget, 'chartHeading'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_limit()
    {
        $widget = $this->createWidget();
        $this->assertEquals(8, $this->getProperty($widget, 'limit'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_correct_data_structure()
    {
        // Create visits with different platforms
        LinkVisit::factory()->count(5)->create([
            'link_id' => $this->link->id,
            'platform' => 'Windows',
            'created_at' => now(),
        ]);

        LinkVisit::factory()->count(3)->create([
            'link_id' => $this->link->id,
            'platform' => 'macOS',
            'created_at' => now(),
        ]);

        LinkVisit::factory()->count(2)->create([
            'link_id' => $this->link->id,
            'platform' => 'iOS',
            'created_at' => now(),
        ]);

        $widget = $this->createWidget();
        $data = $this->invokeMethod($widget, 'getData');

        // Check data structure
        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);

        // Check that we have the correct platforms
        $this->assertContains('Windows', $data['labels']);
        $this->assertContains('macOS', $data['labels']);
        $this->assertContains('iOS', $data['labels']);

        // Check that the data counts match
        $windowsIndex = array_search('Windows', $data['labels']);
        $macOSIndex = array_search('macOS', $data['labels']);
        $iOSIndex = array_search('iOS', $data['labels']);

        $this->assertEquals(5, $data['datasets'][0]['data'][$windowsIndex]);
        $this->assertEquals(3, $data['datasets'][0]['data'][$macOSIndex]);
        $this->assertEquals(2, $data['datasets'][0]['data'][$iOSIndex]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_normalizes_platform_names()
    {
        // Create visits with different platform variations
        LinkVisit::factory()->count(2)->create([
            'link_id' => $this->link->id,
            'platform' => 'Windows 10',
            'created_at' => now(),
        ]);

        LinkVisit::factory()->count(3)->create([
            'link_id' => $this->link->id,
            'platform' => 'Windows 11',
            'created_at' => now(),
        ]);

        $widget = $this->createWidget();
        $data = $this->invokeMethod($widget, 'getData');

        // Check that Windows variations are grouped
        $this->assertContains('Windows', $data['labels']);

        // Find Windows index
        $windowsIndex = array_search('Windows', $data['labels']);

        // Should be 5 total (2 + 3)
        $this->assertEquals(5, $data['datasets'][0]['data'][$windowsIndex]);
    }

    /**
     * Create a widget instance with the link record set.
     */
    protected function createWidget()
    {
        $widget = new LinkVisitsByPlatformPieChart;

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
