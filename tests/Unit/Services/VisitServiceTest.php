<?php

namespace Tests\Unit\Services;

use App\Jobs\SaveVisitJob;
use App\Models\Domain;
use App\Models\Link;
use App\Services\VisitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VisitServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_dispatches_save_visit_job(): void
    {
        // Arrange
        Queue::fake();

        // Mock the request
        $request = Request::create('https://example.com/abc123');
        $this->app->instance('request', $request);

        // Create a link
        $link = Link::factory()->create([
            'original_url' => 'https://example.org',
            'forward_query_parameters' => false,
            'send_ref_query_parameter' => false,
        ]);

        // Act
        VisitService::redirectToOriginalUrl($link);

        // Assert
        Queue::assertPushed(SaveVisitJob::class, function ($job) {
            // We can't access private properties directly, so just verify the job was pushed
            return true;
        });
    }

    #[Test]
    public function it_redirects_to_original_url(): void
    {
        // Arrange
        Queue::fake(); // Prevent the job from actually being dispatched

        // Mock the request
        $request = Request::create('https://example.com/abc123');
        $this->app->instance('request', $request);

        // Create a link
        $link = Link::factory()->create([
            'original_url' => 'https://example.org',
            'forward_query_parameters' => false,
            'send_ref_query_parameter' => false,
        ]);

        // Act
        $response = VisitService::redirectToOriginalUrl($link);

        // Assert
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('https://example.org', $response->headers->get('Location'));

        // Verify the job was dispatched
        Queue::assertPushed(SaveVisitJob::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_adds_ref_parameter_when_enabled()
    {
        // Arrange
        $link = Link::factory()->create([
            'original_url' => 'https://example.org',
            'forward_query_parameters' => false,
            'send_ref_query_parameter' => true,
        ]);

        // Mock the request
        $this->app->bind('request', function () {
            $request = Request::create('https://example.com/abc123');

            return $request;
        });

        // Act
        $response = VisitService::redirectToOriginalUrl($link);

        // Assert
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('https://example.org/?ref=example.com', $response->headers->get('Location'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_forwards_query_parameters_when_enabled()
    {
        // Arrange
        $link = Link::factory()->create([
            'original_url' => 'https://example.org',
            'forward_query_parameters' => true,
            'send_ref_query_parameter' => false,
        ]);

        // Mock the request with query parameters
        $this->app->bind('request', function () {
            $request = Request::create('https://example.com/abc123', 'GET', ['utm_source' => 'test', 'utm_medium' => 'email']);

            return $request;
        });

        // Set the global $_GET variable
        $_GET = ['utm_source' => 'test', 'utm_medium' => 'email'];

        // Act
        $response = VisitService::redirectToOriginalUrl($link);

        // Assert
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('https://example.org/?utm_source=test&utm_medium=email', $response->headers->get('Location'));

        // Clean up
        $_GET = [];
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_combines_ref_and_query_parameters()
    {
        // Arrange
        $link = Link::factory()->create([
            'original_url' => 'https://example.org',
            'forward_query_parameters' => true,
            'send_ref_query_parameter' => true,
        ]);

        // Mock the request with query parameters
        $this->app->bind('request', function () {
            $request = Request::create('https://example.com/abc123', 'GET', ['utm_source' => 'test']);

            return $request;
        });

        // Set the global $_GET variable
        $_GET = ['utm_source' => 'test'];

        // Act
        $response = VisitService::redirectToOriginalUrl($link);

        // Assert
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('https://example.org/?ref=example.com&utm_source=test', $response->headers->get('Location'));

        // Clean up
        $_GET = [];
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_preserves_original_url_query_parameters()
    {
        // Arrange
        $link = Link::factory()->create([
            'original_url' => 'https://example.org?existing=param',
            'forward_query_parameters' => true,
            'send_ref_query_parameter' => true,
        ]);

        // Mock the request with query parameters
        $this->app->bind('request', function () {
            $request = Request::create('https://example.com/abc123', 'GET', ['utm_source' => 'test']);

            return $request;
        });

        // Set the global $_GET variable
        $_GET = ['utm_source' => 'test'];

        // Act
        $response = VisitService::redirectToOriginalUrl($link);

        // Assert
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('https://example.org/?existing=param&ref=example.com&utm_source=test', $response->headers->get('Location'));

        // Clean up
        $_GET = [];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create a domain to use as the current domain
        $this->domain = Domain::factory()->create([
            'protocol' => 'https',
            'host' => 'example.com',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        // Mock the current_domain helper
        $this->app->singleton('current_domain', function () {
            return $this->domain;
        });
    }
}
