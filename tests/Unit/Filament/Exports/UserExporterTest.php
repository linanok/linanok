<?php

namespace Tests\Unit\Filament\Exports;

use App\Filament\Exports\UserExporter;
use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export as ExportModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserExporterTest extends TestCase
{
    use RefreshDatabase;

    protected function makeExporter(array $columnMap = [], array $options = []): UserExporter
    {
        $export = ExportModel::create([
            'file_disk' => 'local',
            'file_name' => 'users.csv',
            'exporter' => UserExporter::class,
            'processed_rows' => 0,
            'total_rows' => 1,
            'successful_rows' => 0,
            'user_id' => User::factory()->create()->id,
        ]);

        /** @var UserExporter $exporter */
        $exporter = $export->getExporter($columnMap, $options);

        return $exporter;
    }

    #[Test]
    public function it_defines_expected_columns_and_defaults(): void
    {
        $columns = UserExporter::getColumns();
        $this->assertIsArray($columns);

        $names = array_map(fn (ExportColumn $c) => $c->getName(), $columns);

        $expected = [
            'id',
            'is_active',
            'name',
            'email',
            'is_super_admin',
            'roles.name',
            'permissions.name',
            'created_at',
            'updated_at',
        ];

        $this->assertSame($expected, $names);

        $byName = fn (string $name) => collect($columns)->firstWhere(fn (ExportColumn $c) => $c->getName() === $name);
        $this->assertTrue($byName('id')->isEnabledByDefault());
        $this->assertFalse($byName('is_active')->isEnabledByDefault());
        $this->assertTrue($byName('name')->isEnabledByDefault());
        $this->assertTrue($byName('email')->isEnabledByDefault());
        $this->assertTrue($byName('is_super_admin')->isEnabledByDefault());
        $this->assertTrue($byName('roles.name')->isEnabledByDefault());
        $this->assertTrue($byName('permissions.name')->isEnabledByDefault());
        $this->assertTrue($byName('created_at')->isEnabledByDefault());
        $this->assertFalse($byName('updated_at')->isEnabledByDefault());
    }

    #[Test]
    public function it_builds_completed_notification_body_and_query_ordering(): void
    {
        $export = ExportModel::create([
            'file_disk' => 'local',
            'file_name' => 'users.csv',
            'exporter' => UserExporter::class,
            'processed_rows' => 5,
            'total_rows' => 5,
            'successful_rows' => 5,
            'user_id' => User::factory()->create()->id,
        ]);

        $body = UserExporter::getCompletedNotificationBody($export);
        $this->assertStringContainsString('5', $body);
        $this->assertStringNotContainsString('failed', strtolower($body));

        $query = User::query();
        $modified = UserExporter::modifyQuery($query);
        $this->assertSame($query, $modified);
        $this->assertStringContainsString('order by', strtolower($modified->toSql()));
        $this->assertStringContainsString('id', strtolower($modified->toSql()));
    }
}
