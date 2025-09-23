<?php

namespace Tests\Unit\Filament\Imports;

use App\Filament\Imports\LinkImporter;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Tag;
use App\Models\User;
use Filament\Actions\Imports\Models\Import as ImportModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LinkImporterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a LinkImporter instance ready to process a row.
     *
     * @param  array<string,string>  $columnMap
     * @param  array<string,mixed>  $options
     */
    protected function makeImporter(array $columnMap = [], array $options = []): LinkImporter
    {
        $import = ImportModel::create([
            'file_name' => 'test.csv',
            'file_path' => 'storage/test.csv',
            'importer' => LinkImporter::class,
            'processed_rows' => 0,
            'total_rows' => 1,
            'successful_rows' => 0,
            'user_id' => User::factory()->create()->id,
        ]);

        return new LinkImporter($import, $columnMap, $options);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Default authenticated user with broad permissions.
        $user = User::factory()->create();
        // Ensure permissions exist before assigning
        Permission::findOrCreate('create link');
        Permission::findOrCreate('update link');
        Permission::findOrCreate('create tag');
        $user->givePermissionTo('create link');
        $user->givePermissionTo('update link');
        $user->givePermissionTo('create tag');
        $this->actingAs($user);

        // Ensure at least one active domain exists for availability scopes used elsewhere
        Domain::factory()->create([
            'host' => 'default-test-domain.com',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);
    }

    #[Test]
    public function it_creates_a_new_link_with_defaults_and_relationships(): void
    {
        $domainA = Domain::factory()->create(['host' => 'localhost:8000', 'is_active' => true]);
        $domainB = Domain::factory()->create(['host' => 'google.com', 'is_active' => true]);
        $tag1 = Tag::factory()->create(['name' => 'tag1']);

        $importer = $this->makeImporter([
            'id' => 'id',
            'original_url' => 'original_url',
            'slug' => 'slug',
            'is_active' => 'is_active',
            'forward_query_parameters' => 'forward_query_parameters',
            'send_ref_query_parameter' => 'send_ref_query_parameter',
            'domains' => 'domains',
            'tags' => 'tags',
            'description' => 'description',
        ], options: ['create_missing_tags' => true]);

        $row = [
            'id' => null,
            'original_url' => 'https://example.com/page',
            'slug' => 'custom-slug',
            'is_active' => 'yes',
            'forward_query_parameters' => 'no',
            'send_ref_query_parameter' => '1',
            // Comma separated per ImportColumn default separator
            'domains' => 'localhost:8000,google.com',
            'tags' => 'tag1,tag2',
            'description' => 'A sample link',
        ];

        // Invoke importer for one row
        $importer($row);

        $this->assertDatabaseHas('links', [
            'original_url' => 'https://example.com/page',
            'slug' => 'custom-slug',
            'is_active' => true,
            'forward_query_parameters' => false,
            'send_ref_query_parameter' => true,
            'description' => 'A sample link',
        ]);

        $link = Link::first();
        $this->assertNotNull($link);
        $this->assertTrue($link->domains()->whereIn('domains.host', ['localhost:8000', 'google.com'])->count() === 2);
        $this->assertTrue($link->tags()->whereIn('tags.name', ['tag1', 'tag2'])->count() === 2);
        // tag2 should have been auto-created
        $this->assertDatabaseHas('tags', ['name' => 'tag2']);
    }

    #[Test]
    public function it_updates_existing_link_and_does_not_override_slug(): void
    {
        $link = Link::factory()->create([
            'original_url' => 'https://old.com',
            'slug' => 'kept-slug',
            'is_active' => true,
        ]);

        $importer = $this->makeImporter([
            'id' => 'id',
            'original_url' => 'original_url',
            'slug' => 'slug',
            'is_active' => 'is_active',
        ]);

        $row = [
            'id' => (string) $link->id,
            'original_url' => 'https://new.com',
            'slug' => 'new-slug-should-be-ignored',
            'is_active' => 'true',
        ];

        $importer($row);

        $link->refresh();
        $this->assertEquals('https://new.com', $link->original_url);
        $this->assertEquals('kept-slug', $link->slug);
        $this->assertTrue($link->is_active);
    }

    #[Test]
    public function it_validates_required_fields_and_throws_on_invalid_url(): void
    {
        $importer = $this->makeImporter([
            'id' => 'id',
            'original_url' => 'original_url',
        ]);

        $this->expectException(ValidationException::class);

        $row = [
            'id' => null,
            'original_url' => 'not-a-url',
        ];

        $importer($row);
    }

    #[Test]
    public function it_denies_create_when_user_lacks_permission(): void
    {
        // Act as a fresh user without permissions
        $noPermUser = User::factory()->create();
        $this->actingAs($noPermUser);

        $importer = $this->makeImporter([
            'id' => 'id',
            'original_url' => 'original_url',
        ]);

        $row = [
            'id' => null,
            'original_url' => 'https://valid.example.com',
        ];

        try {
            $importer($row);
            $this->fail('Expected ValidationException due to missing create permission.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('You do not have permission to create links.', $e->errors()['id'][0]);
        }
    }

    #[Test]
    public function it_denies_update_when_user_lacks_permission(): void
    {
        $link = Link::factory()->create();

        // Act as a fresh user without permissions
        $noPermUser = User::factory()->create();
        $this->actingAs($noPermUser);

        $importer = $this->makeImporter([
            'id' => 'id',
        ]);

        $row = [
            'id' => (string) $link->id,
        ];

        $this->expectException(ValidationException::class);
        $importer($row);
    }
}
