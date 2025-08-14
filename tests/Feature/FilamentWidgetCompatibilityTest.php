<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\VisitsChart;
use App\Filament\Resources\Links\Widgets\LinkVisitsCountChart;
use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FilamentWidgetCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_render_visits_chart_with_sqlite(): void
    {
        // Create test data
        $this->createTestData();

        // Create a user for authentication
        $user = User::factory()->create();
        $this->actingAs($user);

        // Test that the widget can be instantiated
        $widget = new VisitsChart;
        $widget->filter = 'week';

        // Test that we can call the protected getData method via reflection
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);

        // This should not throw an exception
        $data = $method->invoke($widget);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);
    }

    #[Test]
    public function it_can_render_link_visits_count_chart_with_sqlite(): void
    {
        // Create test data
        $link = $this->createTestData();

        // Create a user for authentication
        $user = User::factory()->create();
        $this->actingAs($user);

        // Test that the widget can be instantiated
        $widget = new LinkVisitsCountChart;
        $widget->record = $link;
        $widget->filter = 'week';

        // Test that we can call the protected getData method via reflection
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);

        // This should not throw an exception
        $data = $method->invoke($widget);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);
    }

    #[Test]
    public function it_handles_database_specific_date_truncation(): void
    {
        // Test the DatabaseCompatible trait directly
        $widget = new VisitsChart;

        // Test different intervals using reflection to access protected method
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getDateTruncSql');
        $method->setAccessible(true);

        $intervals = ['hour', 'day', 'week', 'month', 'year'];

        foreach ($intervals as $interval) {
            $sql = $method->invoke($widget, $interval, 'created_at');
            $this->assertIsString($sql);
            $this->assertNotEmpty($sql);

            // For SQLite, should not contain 'date_trunc'
            if (DB::connection()->getDriverName() === 'sqlite') {
                $this->assertStringNotContainsString('date_trunc', $sql);
            }
        }
    }

    #[Test]
    public function it_can_execute_database_compatible_queries(): void
    {
        // Create test data
        $this->createTestData();

        $widget = new VisitsChart;

        // Test that we can execute a query with the database-compatible date truncation
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getDateTruncSql');
        $method->setAccessible(true);

        $dateTruncSql = $method->invoke($widget, 'day', 'created_at');

        // This should not throw a database error
        $result = DB::table('link_visits')
            ->selectRaw("$dateTruncSql as date_group, COUNT(*) as count")
            ->groupBy('date_group')
            ->get();

        $this->assertIsObject($result);
    }

    #[Test]
    public function it_supports_different_database_features(): void
    {
        $widget = new VisitsChart;

        // Test feature detection using reflection
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('databaseSupports');
        $method->setAccessible(true);

        $this->assertIsBool($method->invoke($widget, 'json_operations'));
        $this->assertIsBool($method->invoke($widget, 'full_text_search'));
        $this->assertIsBool($method->invoke($widget, 'window_functions'));

        // SQLite should support JSON operations
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->assertTrue($method->invoke($widget, 'json_operations'));
        }
    }

    #[Test]
    public function it_handles_empty_data_gracefully(): void
    {
        // Don't create any test data - test with empty database

        // Create a user for authentication
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a domain for the link
        $domain = Domain::factory()->create([
            'host' => 'test.com',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        // Create a link with no visits
        $link = Link::factory()->create();
        $link->domains()->attach($domain);

        // Test VisitsChart with no data
        $visitsWidget = new VisitsChart;
        $visitsWidget->filter = 'week';

        $reflection = new \ReflectionClass($visitsWidget);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);
        $data = $method->invoke($visitsWidget);
        $this->assertIsArray($data);

        // Test LinkVisitsCountChart with no data
        $linkWidget = new LinkVisitsCountChart;
        $linkWidget->record = $link;
        $linkWidget->filter = 'week';

        $reflection = new \ReflectionClass($linkWidget);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);
        $data = $method->invoke($linkWidget);
        $this->assertIsArray($data);
    }

    #[Test]
    public function it_produces_consistent_results_across_database_types(): void
    {
        // Create test data
        $this->createTestData();

        $widget = new VisitsChart;

        // Test that date truncation produces valid SQL for current database
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getDateTruncSql');
        $method->setAccessible(true);

        $intervals = ['day', 'week', 'month'];

        foreach ($intervals as $interval) {
            $sql = $method->invoke($widget, $interval, 'created_at');

            // Execute a simple query to ensure the SQL is valid
            $result = DB::select("SELECT $sql as truncated_date FROM link_visits LIMIT 1");

            // Should not throw an exception and should return a result
            $this->assertIsArray($result);
        }
    }

    /**
     * Create test data for widget testing
     */
    private function createTestData(): Link
    {
        // Create a domain
        $domain = Domain::factory()->create([
            'host' => 'widget-test.com',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        // Create a link
        $link = Link::factory()->create([
            'original_url' => 'https://example.com',
            'slug' => 'widget-test',
            'is_active' => true,
        ]);

        $link->domains()->attach($domain);

        // Create some visits with different timestamps
        $now = now();
        LinkVisit::factory()->create([
            'link_id' => $link->id,
            'domain_id' => $domain->id,
            'ip' => '192.168.1.1',
            'created_at' => $now->copy()->subDays(1),
        ]);

        LinkVisit::factory()->create([
            'link_id' => $link->id,
            'domain_id' => $domain->id,
            'ip' => '192.168.1.2',
            'created_at' => $now->copy()->subDays(2),
        ]);

        LinkVisit::factory()->create([
            'link_id' => $link->id,
            'domain_id' => $domain->id,
            'ip' => '192.168.1.1', // Same IP as first visit
            'created_at' => $now->copy()->subDays(3),
        ]);

        return $link;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure we have a default domain for tests
        if (! Domain::where('host', 'default-test-domain.com')->exists()) {
            Domain::factory()->create([
                'host' => 'default-test-domain.com',
                'is_active' => true,
                'is_admin_panel_active' => true,
            ]);
        }
    }
}
