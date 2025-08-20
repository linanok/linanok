<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LinkAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_access_within_availability_window(): void
    {
        // Arrange
        $domain = Domain::factory()->create([
            'host' => 'example.com',
            'protocol' => 'https',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        $link = Link::factory()->create([
            'original_url' => 'https://target-site.com',
            'slug' => 'abc123',
            'available_at' => now()->subDay(),
            'unavailable_at' => now()->addDay(),
            'send_ref_query_parameter' => false,
            'is_active' => true,
        ]);

        $link->domains()->attach($domain);

        // Act
        $response = $this->get('https://example.com/l/abc123');

        // Assert
        $response->assertRedirect('https://target-site.com');
    }

    #[Test]
    public function it_denies_access_to_inactive_links(): void
    {
        // Arrange
        $domain = Domain::factory()->create([
            'host' => 'example.com',
            'protocol' => 'https',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        $link = Link::factory()->create([
            'original_url' => 'https://target-site.com',
            'slug' => 'inactive123',
            'is_active' => false,
            'send_ref_query_parameter' => false,
        ]);

        $link->domains()->attach($domain);

        // Act
        $response = $this->get('https://example.com/l/inactive123');

        // Assert
        $response->assertStatus(404);
    }

    #[Test]
    public function it_denies_access_before_availability(): void
    {
        // Arrange
        $domain = Domain::factory()->create([
            'host' => 'example.com',
            'protocol' => 'https',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        $link = Link::factory()->create([
            'original_url' => 'https://target-site.com',
            'slug' => 'abc123',
            'available_at' => now()->addDay(),
            'unavailable_at' => now()->addDays(2),
        ]);

        $link->domains()->attach($domain);

        // Act
        $response = $this->get('https://example.com/l/abc123');

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function it_denies_access_after_expiry(): void
    {
        // Arrange
        $domain = Domain::factory()->create([
            'host' => 'example.com',
            'protocol' => 'https',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        $link = Link::factory()->create([
            'original_url' => 'https://target-site.com',
            'slug' => 'abc123',
            'available_at' => now()->subDays(2),
            'unavailable_at' => now()->subDay(),
        ]);

        $link->domains()->attach($domain);

        // Act
        $response = $this->get('https://example.com/l/abc123');

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function it_allows_access_with_null_availability_dates(): void
    {
        // Arrange
        $domain = Domain::factory()->create([
            'host' => 'example.com',
            'protocol' => 'https',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        $link = Link::factory()->create([
            'is_active' => true,
            'original_url' => 'https://target-site.com',
            'slug' => 'abc123',
            'available_at' => null,
            'unavailable_at' => null,
            'send_ref_query_parameter' => false,
        ]);

        $link->domains()->attach($domain);

        // Act
        $response = $this->get('https://example.com/l/abc123');

        // Assert
        $response->assertRedirect('https://target-site.com');
    }

    #[Test]
    public function it_denies_access_for_wrong_domain(): void
    {
        // Arrange
        $link = Link::factory()->create([
            'original_url' => 'https://target-site.com',
            'slug' => 'abc123',
        ]);

        $wrongDomain = Domain::factory()->create([
            'host' => 'wrong-domain.com',
            'protocol' => 'https',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        $link->domains()->attach($wrongDomain);

        // Act
        $response = $this->get('https://example.com/abc123');

        // Assert
        $response->assertNotFound();
    }
}
