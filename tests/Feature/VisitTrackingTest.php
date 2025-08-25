<?php

namespace Tests\Feature;

use App\Jobs\SaveVisitJob;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Visit;
use App\Services\VisitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class VisitTrackingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_dispatches_visit_tracking_job(): void
    {
        // Arrange
        Queue::fake();

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

        Queue::assertPushed(SaveVisitJob::class, function ($job) use ($link) {
            // We can't access private properties directly, so we'll use reflection
            $reflection = new ReflectionClass($job);
            $property = $reflection->getProperty('linkId');

            return $property->getValue($job) === $link->id;
        });
    }

    #[Test]
    public function it_records_visit_with_correct_data(): void
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

        $visit = Visit::latest()->first();
        $this->assertNotNull($visit);
        $this->assertEquals($link->id, $visit->link_id);
        $this->assertEquals($domain->id, $visit->domain_id);
        $this->assertEquals('Symfony', $visit->browser);
    }

    #[Test]
    public function it_tracks_visits_for_password_protected_links(): void
    {
        // Arrange
        Queue::fake();

        $domain = Domain::factory()->create([
            'host' => 'example.com',
            'protocol' => 'https',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        $link = Link::factory()->create([
            'original_url' => 'https://target-site.com',
            'slug' => 'abc123',
            'password' => 'secret123',
            'send_ref_query_parameter' => false,
        ]);

        $link->domains()->attach($domain);

        // Mock the request
        $request = \Illuminate\Http\Request::create('https://example.com/abc123');
        $request->headers->set('Host', 'example.com');
        $this->app->instance('request', $request);

        // Act
        $response = VisitService::redirectToOriginalUrl($link);

        // Assert
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('https://target-site.com', $response->headers->get('Location'));

        // Verify the job was dispatched with the correct link ID
        Queue::assertPushed(SaveVisitJob::class, function ($job) use ($link) {
            $reflection = new ReflectionClass($job);
            $property = $reflection->getProperty('linkId');

            return $property->getValue($job) === $link->id;
        });
    }
}
