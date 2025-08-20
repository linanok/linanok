<?php

namespace Helpers;

use App\Enums\Protocol;
use App\Models\Domain;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class GetShortUrlTest extends TestCase
{
    use RefreshDatabase;

    protected Domain $domain1;

    protected Domain $domain2;

    protected Link $link;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test domains
        $this->domain1 = Domain::factory()->create([
            'host' => 'example.com',
            'protocol' => Protocol::HTTPS,
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        $this->domain2 = Domain::factory()->create([
            'host' => 'other-domain.com',
            'protocol' => Protocol::HTTP,
            'is_active' => true,
            'is_admin_panel_active' => false,
        ]);

        // Create a test link with a fixed short_path for predictable testing
        $this->link = Link::factory()->create([
            'original_url' => 'https://original-url.com',
        ]);

        // Manually set the short_path to ensure it's consistent for tests
        $this->link->short_path = 'test-link';
        $this->link->save(['timestamps' => false]);

        // Associate the link with both domains
        $this->link->domains()->attach([$this->domain1->id, $this->domain2->id]);
    }

    /**
     * Test that get_short_url returns the correct URL when a domain is provided
     * and the link is associated with that domain.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_url_with_provided_domain_when_link_is_associated()
    {
        // Act
        $result = get_short_url($this->link, $this->domain1);

        // Assert
        $this->assertEquals('https://example.com/l/test-link', $result);
    }

    /**
     * Test that get_short_url falls back to the first associated domain
     * when a domain is provided but the link is not associated with it.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_falls_back_to_first_domain_when_provided_domain_not_associated()
    {
        // Arrange
        $unassociatedDomain = Domain::factory()->create([
            'host' => 'unassociated.com',
            'protocol' => Protocol::HTTPS,
        ]);

        // Act
        $result = get_short_url($this->link, $unassociatedDomain);

        // Assert
        $this->assertEquals('https://example.com/l/test-link', $result);
    }

    /**
     * Test that get_short_url uses the current domain when no domain is provided
     * and the link is associated with the current domain.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_current_domain_when_no_domain_provided_and_link_associated_with_current_domain()
    {
        // Arrange - Mock the request to use domain1 as the current domain
        $this->app->instance('request', Request::create('https://example.com'));

        // Act
        $result = get_short_url($this->link);

        // Assert
        $this->assertEquals('https://example.com/l/test-link', $result);
    }

    /**
     * Test that get_short_url falls back to the first associated domain
     * when no domain is provided and the link is not associated with the current domain.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_falls_back_to_first_domain_when_no_domain_provided_and_link_not_associated_with_current_domain()
    {
        // Arrange - Mock the request with a domain that exists but is not associated with the link
        $unassociatedDomain = Domain::factory()->create([
            'host' => 'unassociated.com',
            'protocol' => Protocol::HTTPS,
        ]);
        $this->app->instance('request', Request::create('https://unassociated.com'));

        // Act
        $result = get_short_url($this->link);

        // Assert
        $this->assertEquals('https://example.com/l/test-link', $result);
    }

    /**
     * Test that get_short_url handles the case when current_domain() returns null.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_null_current_domain()
    {
        // Arrange - Mock the request with a domain that doesn't exist
        $this->app->instance('request', Request::create('https://non-existent-domain.com'));

        // Act
        $result = get_short_url($this->link);

        // Assert
        $this->assertEquals('https://example.com/l/test-link', $result);
    }

    /**
     * Test that get_short_url uses the second domain when specified.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_second_domain_when_specified()
    {
        // Act
        $result = get_short_url($this->link, $this->domain2);

        // Assert
        $this->assertEquals('http://other-domain.com/l/test-link', $result);
    }
}
