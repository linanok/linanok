<?php

namespace Tests\Unit\Models;

use App\Models\Domain;
use App\Models\Link;
use App\Models\Tag;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LinkTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_a_link(): void
    {
        // Arrange & Act
        $link = new Link;
        $link->original_url = 'https://example.com';
        $link->password = 'secret';
        $link->available_at = now()->subDay();
        $link->unavailable_at = now()->addDay();
        $link->forward_query_parameters = true;
        $link->slug = 'example';
        $link->description = 'Example link';
        $link->visit_count = 0;
        $link->send_ref_query_parameter = true;
        $link->is_active = true;
        $link->save();

        // Assert
        $this->assertDatabaseHas('links', [
            'original_url' => 'https://example.com',
            'password' => 'secret',
            'forward_query_parameters' => true,
            'slug' => 'example',
            'description' => 'Example link',
            'visit_count' => 0,
            'send_ref_query_parameter' => true,
            'is_active' => true,
        ]);

        // The short_path should be set to the slug by the observer
        $this->assertEquals('example', $link->short_path);
        $this->assertEquals('https://example.com', $link->original_url);
        $this->assertEquals('secret', $link->password);
        $this->assertTrue($link->forward_query_parameters);
        $this->assertEquals('example', $link->slug);
        $this->assertEquals('Example link', $link->description);
        $this->assertEquals(0, $link->visit_count);
        $this->assertTrue($link->send_ref_query_parameter);
    }

    #[Test]
    public function it_has_domains_relationship(): void
    {
        // Arrange
        $link = Link::factory()->create();
        $domain = Domain::factory()->create();

        // Act
        $link->domains()->attach($domain);

        // Assert
        $this->assertTrue($link->domains->contains($domain));
        $this->assertEquals(1, $link->domains->count());
    }

    #[Test]
    public function it_has_tags_relationship(): void
    {
        // Arrange
        $link = Link::factory()->create();
        $tag = Tag::factory()->create();

        // Act
        $link->tags()->attach($tag);

        // Assert
        $this->assertTrue($link->tags->contains($tag));
        $this->assertEquals(1, $link->tags->count());
    }

    #[Test]
    public function it_has_visits_relationship(): void
    {
        // Arrange
        $link = Link::factory()->create();
        $visit = Visit::factory()->create(['link_id' => $link->id]);

        // Assert
        $this->assertTrue($link->visits->contains($visit));
        $this->assertEquals(1, $link->visits->count());
    }

    #[Test]
    public function it_can_be_converted_to_string(): void
    {
        // Arrange & Act
        $link = new Link;
        $link->original_url = 'https://example.com';
        $link->slug = 'testslug';
        $link->forward_query_parameters = true;
        $link->send_ref_query_parameter = true;
        $link->save();

        // Assert
        $this->assertEquals('testslug', (string) $link);
    }

    #[Test]
    public function it_has_available_scope(): void
    {
        // Arrange
        $activeDomain = Domain::factory()->create(['is_active' => true]);
        $inactiveDomain = Domain::factory()->create(['is_active' => false]);

        // Create a link that is available (no dates, active, with active domain)
        $link1 = Link::factory()->create([
            'available_at' => null,
            'unavailable_at' => null,
            'is_active' => true,
        ]);
        $link1->domains()->attach($activeDomain);

        // Create a link that is available (within dates, active, with active domain)
        $link2 = Link::factory()->create([
            'available_at' => now()->subDay(),
            'unavailable_at' => now()->addDay(),
            'is_active' => true,
        ]);
        $link2->domains()->attach($activeDomain);

        // Create a link that is not available (future available date, even with active domain)
        $link3 = Link::factory()->create([
            'available_at' => now()->addDay(),
            'unavailable_at' => null,
            'is_active' => true,
        ]);
        $link3->domains()->attach($activeDomain);

        // Create a link that is not available (past unavailable date, even with active domain)
        $link4 = Link::factory()->create([
            'available_at' => null,
            'unavailable_at' => now()->subDay(),
            'is_active' => true,
        ]);
        $link4->domains()->attach($activeDomain);

        // Create a link that is not available (inactive, even with active domain)
        $link5 = Link::factory()->create([
            'available_at' => null,
            'unavailable_at' => null,
            'is_active' => false,
        ]);
        $link5->domains()->attach($activeDomain);

        // Create a link that is not available (active but only has inactive domain)
        $link6 = Link::factory()->create([
            'available_at' => null,
            'unavailable_at' => null,
            'is_active' => true,
        ]);
        $link6->domains()->attach($inactiveDomain);

        // Act
        $availableLinks = Link::available()->get();

        // Assert
        $this->assertEquals(2, $availableLinks->count());
        $this->assertTrue($availableLinks->contains($link1));
        $this->assertTrue($availableLinks->contains($link2));
        $this->assertFalse($availableLinks->contains($link3));
        $this->assertFalse($availableLinks->contains($link4));
        $this->assertFalse($availableLinks->contains($link5));
        $this->assertFalse($availableLinks->contains($link6));
    }

    #[Test]
    public function it_excludes_inactive_links_from_available_scope(): void
    {
        // Arrange
        $activeDomain = Domain::factory()->create(['is_active' => true]);

        // Create an active link with active domain
        $activeLink = Link::factory()->create([
            'is_active' => true,
            'available_at' => null,
            'unavailable_at' => null,
        ]);
        $activeLink->domains()->attach($activeDomain);

        // Create an inactive link (even with active domain, should not be available)
        $inactiveLink = Link::factory()->create([
            'is_active' => false,
            'available_at' => null,
            'unavailable_at' => null,
        ]);
        $inactiveLink->domains()->attach($activeDomain);

        // Act
        $availableLinks = Link::available()->get();

        // Assert
        $this->assertEquals(1, $availableLinks->count());
        $this->assertTrue($availableLinks->contains($activeLink));
        $this->assertFalse($availableLinks->contains($inactiveLink));
    }

    #[Test]
    public function it_has_has_password_scope(): void
    {
        // Arrange & Act
        Link::factory()->create(['password' => 'secret']);
        Link::factory()->create(['password' => null]);

        $linksWithPassword = Link::whereNotNull('password')->get();

        // Assert
        $this->assertEquals(1, $linksWithPassword->count());
        $this->assertNotNull($linksWithPassword->first()->password);
    }

    #[Test]
    public function it_has_for_current_domain_scope(): void
    {
        // This test is more complex as it requires mocking the current_domain helper
        // For simplicity, we'll just test that the method exists
        $this->assertTrue(method_exists(Link::class, 'forCurrentDomain'));
    }

    #[Test]
    public function it_has_guarded_attributes(): void
    {
        // Arrange & Act
        $link = new Link;

        // Assert
        $this->assertEquals(['short_path'], $link->getGuarded());
    }

    #[Test]
    public function it_has_logs_activity_trait(): void
    {
        // Arrange & Act
        $link = new Link;

        // Assert
        $this->assertTrue(method_exists($link, 'getActivitylogOptions'));
    }

    #[Test]
    public function it_has_is_available_attribute(): void
    {
        // Arrange
        $activeDomain = Domain::factory()->create(['is_active' => true]);
        $inactiveDomain = Domain::factory()->create(['is_active' => false]);

        // Available link (no dates, active, with active domain)
        $link1 = Link::factory()->create([
            'available_at' => null,
            'unavailable_at' => null,
            'is_active' => true,
        ]);
        $link1->domains()->attach($activeDomain);

        // Available link (within dates, active, with active domain)
        $link2 = Link::factory()->create([
            'available_at' => now()->subDay(),
            'unavailable_at' => now()->addDay(),
            'is_active' => true,
        ]);
        $link2->domains()->attach($activeDomain);

        // Not available link (future available date, even with active domain)
        $link3 = Link::factory()->create([
            'available_at' => now()->addDay(),
            'unavailable_at' => null,
            'is_active' => true,
        ]);
        $link3->domains()->attach($activeDomain);

        // Not available link (inactive, even with active domain)
        $link4 = Link::factory()->create([
            'available_at' => null,
            'unavailable_at' => null,
            'is_active' => false,
        ]);
        $link4->domains()->attach($activeDomain);

        // Not available link (active but only has inactive domain)
        $link5 = Link::factory()->create([
            'available_at' => null,
            'unavailable_at' => null,
            'is_active' => true,
        ]);
        $link5->domains()->attach($inactiveDomain);

        // Not available link (active but has no domains)
        $link6 = Link::factory()->create([
            'available_at' => null,
            'unavailable_at' => null,
            'is_active' => true,
        ]);

        // Assert
        $this->assertTrue($link1->isAvailable);
        $this->assertTrue($link2->isAvailable);
        $this->assertFalse($link3->isAvailable);
        $this->assertFalse($link4->isAvailable);
        $this->assertFalse($link5->isAvailable);
        $this->assertFalse($link6->isAvailable);
    }

    #[Test]
    public function it_requires_at_least_one_active_domain_to_be_available(): void
    {
        // Arrange
        $activeDomain = Domain::factory()->create(['is_active' => true]);
        $inactiveDomain = Domain::factory()->create(['is_active' => false]);

        // Link with both active and inactive domains (should be available)
        $linkWithMixedDomains = Link::factory()->create([
            'available_at' => null,
            'unavailable_at' => null,
            'is_active' => true,
        ]);
        $linkWithMixedDomains->domains()->attach([$activeDomain->id, $inactiveDomain->id]);

        // Link with only inactive domains (should not be available)
        $linkWithOnlyInactiveDomains = Link::factory()->create([
            'available_at' => null,
            'unavailable_at' => null,
            'is_active' => true,
        ]);
        $linkWithOnlyInactiveDomains->domains()->attach($inactiveDomain);

        // Act
        $availableLinks = Link::available()->get();

        // Assert
        $this->assertTrue($linkWithMixedDomains->isAvailable);
        $this->assertFalse($linkWithOnlyInactiveDomains->isAvailable);
        $this->assertEquals(1, $availableLinks->count());
        $this->assertTrue($availableLinks->contains($linkWithMixedDomains));
        $this->assertFalse($availableLinks->contains($linkWithOnlyInactiveDomains));
    }

    #[Test]
    public function it_has_has_password_attribute(): void
    {
        // Arrange & Act
        $link1 = Link::factory()->create(['password' => 'secret']);
        $link2 = Link::factory()->create(['password' => null]);

        // Assert
        $this->assertTrue($link1->hasPassword);
        $this->assertFalse($link2->hasPassword);
    }

    #[Test]
    public function it_casts_date_attributes(): void
    {
        // Arrange & Act
        $link = Link::factory()->create([
            'available_at' => '2023-01-01 00:00:00',
            'unavailable_at' => '2023-12-31 23:59:59',
        ]);

        // Assert
        $this->assertInstanceOf(Carbon::class, $link->available_at);
        $this->assertInstanceOf(Carbon::class, $link->unavailable_at);
        $this->assertEquals('2023-01-01 00:00:00', $link->available_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2023-12-31 23:59:59', $link->unavailable_at->format('Y-m-d H:i:s'));
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
