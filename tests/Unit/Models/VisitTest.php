<?php

namespace Models;

use App\Models\Domain;
use App\Models\Link;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VisitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_a_link_visit()
    {
        $link = Link::factory()->create();
        $domain = Domain::factory()->create();

        $visit = new Visit([
            'link_id' => $link->id,
            'domain_id' => $domain->id,
            'country' => 'US',
            'browser' => 'Chrome',
            'platform' => 'Windows',
            'ip' => '127.0.0.1',
        ]);
        $visit->save();

        $this->assertDatabaseHas('visits', [
            'link_id' => $link->id,
            'domain_id' => $domain->id,
            'country' => 'US',
            'browser' => 'Chrome',
            'platform' => 'Windows',
            'ip' => '127.0.0.1',
        ]);

        $this->assertEquals($link->id, $visit->link_id);
        $this->assertEquals($domain->id, $visit->domain_id);
        $this->assertEquals('US', $visit->country);
        $this->assertEquals('Chrome', $visit->browser);
        $this->assertEquals('Windows', $visit->platform);
        $this->assertEquals('127.0.0.1', $visit->ip);
    }

    #[Test]
    public function it_belongs_to_a_link()
    {
        $link = Link::factory()->create();
        $visit = new Visit([
            'link_id' => $link->id,
            'domain_id' => Domain::first()->id,
            'ip' => '127.0.0.1',
        ]);
        $visit->save();

        $this->assertEquals($link->id, $visit->link->id);
    }

    #[Test]
    public function it_has_only_created_at_timestamp()
    {
        $link = Link::factory()->create();
        $visit = new Visit([
            'link_id' => $link->id,
            'domain_id' => Domain::first()->id,
            'ip' => '127.0.0.1',
        ]);
        $visit->save();

        $this->assertNotNull($visit->created_at);
        $this->assertNull($visit->updated_at);
    }

    #[Test]
    public function it_has_guarded_attributes()
    {
        $visit = new Visit;

        $this->assertEquals([], $visit->getGuarded());
    }

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
}
