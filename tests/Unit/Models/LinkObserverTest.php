<?php

namespace Models;

use App\Models\Domain;
use App\Models\Link;
use App\Observers\LinkObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkObserverTest extends TestCase
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
    public function it_sets_short_path_to_slug_when_slug_is_provided()
    {
        $link = new Link;
        $link->original_url = 'https://example.com';
        $link->slug = 'custom-slug';
        $link->forward_query_parameters = true;
        $link->send_ref_query_parameter = true;
        $link->save();

        $this->assertEquals('custom-slug', $link->short_path);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_random_short_path_when_no_slug_is_provided()
    {
        $link = new Link;
        $link->original_url = 'https://example.com';
        $link->forward_query_parameters = true;
        $link->send_ref_query_parameter = true;
        $link->save();

        $this->assertNotNull($link->short_path);
        $this->assertNotEmpty($link->short_path);
        $this->assertEquals(6, strlen($link->short_path)); // Default random string length
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_appends_random_string_to_slug_when_slug_already_exists()
    {
        // Create first link with slug
        $link1 = new Link;
        $link1->original_url = 'https://example.com';
        $link1->slug = 'duplicate-slug';
        $link1->forward_query_parameters = true;
        $link1->send_ref_query_parameter = true;
        $link1->save();

        // Create second link with same slug
        $link2 = new Link;
        $link2->original_url = 'https://another-example.com';
        $link2->slug = 'duplicate-slug';
        $link2->forward_query_parameters = true;
        $link2->send_ref_query_parameter = true;
        $link2->save();

        $this->assertEquals('duplicate-slug', $link1->short_path);
        $this->assertNotEquals('duplicate-slug', $link2->short_path);
        $this->assertStringStartsWith('duplicate-slug', $link2->short_path);
        // The length will be the slug length (13) + random string (6) + 1 character
        $this->assertGreaterThan(13, strlen($link2->short_path));
        $this->assertLessThan(25, strlen($link2->short_path));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_unique_short_path_when_slug_already_exists()
    {
        // Create a link with a specific slug
        $link1 = new Link;
        $link1->original_url = 'https://example.com';
        $link1->slug = 'unique-slug';
        $link1->forward_query_parameters = true;
        $link1->send_ref_query_parameter = true;
        $link1->save();

        // Create another link with the same slug
        $link2 = new Link;
        $link2->original_url = 'https://another-example.com';
        $link2->slug = 'unique-slug';
        $link2->forward_query_parameters = true;
        $link2->send_ref_query_parameter = true;
        $link2->save();

        $this->assertEquals('unique-slug', $link1->short_path);
        $this->assertNotEquals('unique-slug', $link2->short_path);
        $this->assertStringStartsWith('unique-slug', $link2->short_path);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_short_path_against_reserved_routes()
    {
        // Assuming 'admin' is a reserved route
        $link = new Link;
        $link->original_url = 'https://example.com';
        $link->slug = 'admin';
        $link->forward_query_parameters = true;
        $link->send_ref_query_parameter = true;
        $link->save();

        $this->assertNotEquals('admin', $link->short_path);
        $this->assertStringStartsWith('_admin', $link->short_path); // Check if it was modified
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_short_path_when_slug_is_index_php()
    {
        $link = new Link;
        $link->original_url = 'https://example.com';
        $link->slug = 'index.php';
        $link->forward_query_parameters = true;
        $link->send_ref_query_parameter = true;
        $link->save();

        $this->assertNotEquals('index.php', $link->short_path);
        $this->assertStringStartsWith('_index.php', $link->short_path); // Check if it was modified
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_manually_calls_observer_methods()
    {
        $observer = new LinkObserver;
        $link = new Link;
        $link->original_url = 'https://example.com';

        $observer->creating($link);

        $this->assertNotNull($link->short_path);
        $this->assertNotEmpty($link->short_path);
    }
}
