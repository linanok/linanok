<?php

namespace Models;

use App\Models\Domain;
use App\Models\Link;
use App\Models\Visit;
use App\Observers\VisitObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an active domain with admin panel to satisfy validation
        Domain::factory()->create([
            'host' => 'default-test-domain.com',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_increments_link_visit_count_when_visit_is_created()
    {
        // Create a link with initial visit count of 0
        $link = Link::factory()->create(['visit_count' => 0]);

        // Create a visit for the link
        $visit = new Visit([
            'link_id' => $link->id,
            'domain_id' => Domain::first()->id,
            'ip' => '127.0.0.1',
        ]);
        $visit->save();

        // Refresh the link from the database
        $link->refresh();

        // Check that the visit count was incremented
        $this->assertEquals(1, $link->visit_count);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_increments_link_visit_count_multiple_times()
    {
        // Create a link with initial visit count of 0
        $link = Link::factory()->create(['visit_count' => 0]);

        // Create multiple visits for the link
        for ($i = 0; $i < 3; $i++) {
            $visit = new Visit([
                'link_id' => $link->id,
                'domain_id' => Domain::first()->id,
                'ip' => '127.0.0.1',
            ]);
            $visit->save();
        }

        // Refresh the link from the database
        $link->refresh();

        // Check that the visit count was incremented for each visit
        $this->assertEquals(3, $link->visit_count);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_manually_calls_observer_methods()
    {
        // Create a link with initial visit count of 0
        $link = Link::factory()->create(['visit_count' => 0]);

        // Create a visit for the link
        $visit = new Visit([
            'link_id' => $link->id,
            'domain_id' => Domain::first()->id,
            'ip' => '127.0.0.1',
        ]);

        // Manually call the observer method
        $observer = new VisitObserver;
        $observer->created($visit);

        // Refresh the link from the database
        $link->refresh();

        // Check that the visit count was incremented
        $this->assertEquals(1, $link->visit_count);
    }
}
