<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SaveVisitJob;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\HeaderBag;
use Tests\TestCase;

class SaveVisitJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a domain
        $this->domain = Domain::factory()->create([
            'host' => 'example.com',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        // Create a link
        $this->link = Link::factory()->create([
            'original_url' => 'https://example.org',
            'forward_query_parameters' => false,
            'send_ref_query_parameter' => false,
        ]);
    }

    #[Test]
    public function it_creates_link_visit_record(): void
    {
        // Skip this test if the GeoLite2 database is not available
        if (! file_exists(storage_path('maxmind/GeoLite2-Country.mmdb'))) {
            $this->markTestSkipped('GeoLite2 database not available');
        }

        // Arrange
        $headers = new HeaderBag([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ]);

        $requestData = [
            'headers' => $headers,
            'ip' => '8.8.8.8', // Google's DNS IP for testing
            'domain_id' => $this->domain->id,
        ];

        // Act
        $job = new SaveVisitJob($this->link->id, $requestData);
        $job->handle();

        // Assert
        $this->assertDatabaseHas('visits', [
            'link_id' => $this->link->id,
            'domain_id' => $this->domain->id,
            'ip' => '8.8.8.8',
        ]);

        // Check that browser and platform were parsed
        $visit = Visit::where('link_id', $this->link->id)->first();
        $this->assertNotNull($visit->browser);
        $this->assertNotNull($visit->platform);
    }

    #[Test]
    public function it_increments_link_visit_count(): void
    {
        // Skip this test if the GeoLite2 database is not available
        if (! file_exists(storage_path('maxmind/GeoLite2-Country.mmdb'))) {
            $this->markTestSkipped('GeoLite2 database not available');
        }

        // Arrange
        $initialVisitCount = $this->link->visit_count;

        $headers = new HeaderBag([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ]);

        $requestData = [
            'headers' => $headers,
            'ip' => '8.8.8.8',
            'domain_id' => $this->domain->id,
        ];

        // Act
        $job = new SaveVisitJob($this->link->id, $requestData);
        $job->handle();

        // Refresh the link from the database
        $this->link->refresh();

        // Assert
        $this->assertEquals($initialVisitCount + 1, $this->link->visit_count);
    }
}
