<?php

namespace Tests\Unit\Filament\Exports;

use App\Filament\Exports\TagExporter;
use App\Models\Tag;
use App\Models\User;
use App\Models\Visit;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export as ExportModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TagExporterTest extends TestCase
{
    use RefreshDatabase;

    protected function makeExporter(array $columnMap = [], array $options = []): TagExporter
    {
        $export = ExportModel::create([
            'file_disk' => 'local',
            'file_name' => 'tags.csv',
            'exporter' => TagExporter::class,
            'processed_rows' => 0,
            'total_rows' => 1,
            'successful_rows' => 0,
            'user_id' => User::factory()->create()->id,
        ]);

        /** @var TagExporter $exporter */
        $exporter = $export->getExporter($columnMap, $options);

        return $exporter;
    }

    #[Test]
    public function it_defines_expected_columns_and_defaults(): void
    {
        $columns = TagExporter::getColumns();
        $this->assertIsArray($columns);

        $names = array_map(fn (ExportColumn $c) => $c->getName(), $columns);

        $expected = [
            'id',
            'name',
            'visits_count',
            'created_at',
            'updated_at',
        ];

        $this->assertSame($expected, $names);

        $byName = fn (string $name) => collect($columns)->firstWhere(fn (ExportColumn $c) => $c->getName() === $name);
        $this->assertTrue($byName('id')->isEnabledByDefault());
        $this->assertTrue($byName('name')->isEnabledByDefault());
        $this->assertTrue($byName('visits_count')->isEnabledByDefault());
        $this->assertTrue($byName('created_at')->isEnabledByDefault());
        $this->assertFalse($byName('updated_at')->isEnabledByDefault());
    }

    #[Test]
    public function it_counts_visits_in_visits_count_column(): void
    {
        $this->actingAs(User::factory()->create());

        $tag = Tag::factory()->create();
        $domain = \App\Models\Domain::factory()->create(['is_active' => true, 'is_admin_panel_active' => true]);
        $link = \App\Models\Link::factory()->create(['is_active' => true]);
        $link->tags()->attach($tag->id);
        $link->domains()->attach($domain->id);

        Visit::factory()->count(3)->create(['link_id' => $link->id, 'domain_id' => $domain->id]);

        $exporter = $this->makeExporter([
            'visits_count' => 'visits_count',
        ]);

        // Ensure the record has visits_count available
        $tag->loadCount('visits');
        $row = $exporter($tag);

        $this->assertIsArray($row);
        $this->assertEquals(3, (int) $row[0]);
    }

    #[Test]
    public function it_builds_completed_notification_body_and_query_ordering(): void
    {
        $export = ExportModel::create([
            'file_disk' => 'local',
            'file_name' => 'tags.csv',
            'exporter' => TagExporter::class,
            'processed_rows' => 7,
            'total_rows' => 7,
            'successful_rows' => 6,
            'user_id' => User::factory()->create()->id,
        ]);

        $body = TagExporter::getCompletedNotificationBody($export);
        $this->assertStringContainsString('6', $body);
        $this->assertStringContainsString('1', $body);

        $query = Tag::query();
        $modified = TagExporter::modifyQuery($query);
        $this->assertSame($query, $modified);
        $this->assertStringContainsString('order by', strtolower($modified->toSql()));
        $this->assertStringContainsString('id', strtolower($modified->toSql()));
    }
}
