<?php

namespace Tests\Unit\Filament\Exports;

use App\Filament\Exports\LinkExporter;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export as ExportModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LinkExporterTest extends TestCase
{
    use RefreshDatabase;

    protected function makeExporter(array $columnMap = [], array $options = []): LinkExporter
    {
        $export = ExportModel::create([
            'file_disk' => 'local',
            'file_name' => 'test.csv',
            'exporter' => LinkExporter::class,
            'processed_rows' => 0,
            'total_rows' => 1,
            'successful_rows' => 0,
            'user_id' => User::factory()->create()->id,
        ]);

        /** @var LinkExporter $exporter */
        $exporter = $export->getExporter($columnMap, $options);

        return $exporter;
    }

    #[Test]
    public function it_defines_expected_columns_and_defaults(): void
    {
        $columns = LinkExporter::getColumns();

        $this->assertIsArray($columns);
        $names = array_map(fn (ExportColumn $c) => $c->getName(), $columns);

        $expected = [
            'id',
            'is_active',
            'is_available',
            'slug',
            'original_url',
            'short_path',
            'short_url',
            'tags.name',
            'available_at',
            'unavailable_at',
            'forward_query_parameters',
            'visit_count',
            'send_ref_query_parameter',
            'created_at',
            'updated_at',
        ];

        $this->assertSame($expected, $names);

        // Check enabledByDefault flags for several columns
        $byName = fn (string $name) => collect($columns)->firstWhere(fn (ExportColumn $c) => $c->getName() === $name);

        $this->assertTrue($byName('id')->isEnabledByDefault());
        $this->assertFalse($byName('is_active')->isEnabledByDefault());
        $this->assertTrue($byName('is_available')->isEnabledByDefault());
        $this->assertTrue($byName('slug')->isEnabledByDefault());
        $this->assertTrue($byName('original_url')->isEnabledByDefault());
        $this->assertFalse($byName('short_path')->isEnabledByDefault());
        $this->assertTrue($byName('short_url')->isEnabledByDefault());
        $this->assertTrue($byName('tags.name')->isEnabledByDefault());
        $this->assertFalse($byName('available_at')->isEnabledByDefault());
        $this->assertFalse($byName('unavailable_at')->isEnabledByDefault());
        $this->assertFalse($byName('forward_query_parameters')->isEnabledByDefault());
        $this->assertFalse($byName('visit_count')->isEnabledByDefault());
        $this->assertFalse($byName('send_ref_query_parameter')->isEnabledByDefault());
        $this->assertTrue($byName('created_at')->isEnabledByDefault());
        $this->assertFalse($byName('updated_at')->isEnabledByDefault());
    }

    #[Test]
    public function it_formats_short_path_with_prefix_and_generates_short_url_from_options(): void
    {
        $this->actingAs(User::factory()->create());

        $domain = Domain::factory()->create([
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        $link = Link::factory()->create([
            'slug' => 'abc123',
            'is_active' => true,
        ]);
        $link->domains()->attach($domain->id);

        $exporter = $this->makeExporter([
            'short_path' => 'short_path',
            'short_url' => 'short_url',
        ], options: ['domain' => $domain->id]);

        $row = $exporter($link);

        // Order matches the columnMap order above
        [$formattedShortPath, $generatedShortUrl] = $row;

        $this->assertEquals('/l/abc123', $formattedShortPath);
        $this->assertNotEmpty($generatedShortUrl);
        $this->assertStringContainsString($domain->host, $generatedShortUrl);
    }

    #[Test]
    public function it_builds_completed_notification_body_with_success_and_fail_counts(): void
    {
        $export = ExportModel::create([
            'file_disk' => 'local',
            'file_name' => 'test.csv',
            'exporter' => LinkExporter::class,
            'processed_rows' => 10,
            'total_rows' => 10,
            'successful_rows' => 8,
            'user_id' => User::factory()->create()->id,
        ]);

        $body = LinkExporter::getCompletedNotificationBody($export);

        $this->assertStringContainsString('8', $body);
        $this->assertStringContainsString('2', $body); // failed rows
        $this->assertStringContainsString('link export has completed', $body);
    }

    #[Test]
    public function it_modifies_query_to_order_by_id(): void
    {
        $query = Link::query();
        $modified = LinkExporter::modifyQuery($query);

        $this->assertSame($query, $modified);
        // Ensure ordering by id is applied by checking SQL ordering
        $this->assertStringContainsString('order by', strtolower($modified->toSql()));
        $this->assertStringContainsString('id', strtolower($modified->toSql()));
    }
}
