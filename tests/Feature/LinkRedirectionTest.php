<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LinkRedirectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_to_original_url(): void
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
            'send_ref_query_parameter' => false,
        ]);

        $link->domains()->attach($domain);

        // Act
        $response = $this->get('https://example.com/l/abc123');

        // Assert
        $response->assertRedirect('https://target-site.com');
    }

    #[Test]
    public function it_adds_ref_parameter_when_configured(): void
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
            'send_ref_query_parameter' => true,
        ]);

        $link->domains()->attach($domain);

        // Act
        $response = $this->get('https://example.com/l/abc123');

        // Assert
        $response->assertRedirect('https://target-site.com/?ref=example.com');
    }

    #[Test]
    public function it_forwards_query_parameters_when_configured(): void
    {
        // Arrange
        $domain = Domain::factory()->create([
            'protocol' => 'https',
            'host' => 'example.com',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        $link = Link::factory()->create([
            'is_active' => true,
            'original_url' => 'https://target-site.com',
            'slug' => 'abc123',
            'forward_query_parameters' => true,
            'send_ref_query_parameter' => false,
        ]);

        $link->domains()->attach($domain);

        // Act
        $response = $this->get('https://example.com/l/abc123?param1=value1&param2=value2');

        // Assert
        $response->assertRedirect('https://target-site.com/?param1=value1&param2=value2');
    }

    #[Test]
    public function it_returns_404_when_link_not_found(): void
    {
        // Act
        $response = $this->get('/l/nonexistent', ['HTTP_HOST' => 'example.com']);

        // Assert
        $response->assertNotFound();
    }
}
