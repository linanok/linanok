<?php

namespace Filament\Resources;

use App\Filament\Resources\Tags\TagResource;
use App\Models\Domain;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagResourceTest extends TestCase
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
    public function it_has_correct_model()
    {
        $this->assertEquals(Tag::class, TagResource::getModel());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_navigation_group()
    {
        $this->assertEquals('Link Management', TagResource::getNavigationGroup());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_navigation_icon()
    {
        $this->assertEquals('heroicon-o-tag', TagResource::getNavigationIcon());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_slug()
    {
        $this->assertEquals('tags', TagResource::getSlug());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_record_title_attribute()
    {
        $this->assertEquals('name', TagResource::getRecordTitleAttribute());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_pages()
    {
        $pages = TagResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
        $this->assertArrayHasKey('history', $pages);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_relations()
    {
        $relations = TagResource::getRelations();

        $this->assertCount(1, $relations);
        $this->assertContains('App\Filament\Resources\Tags\RelationManagers\LinksRelationManager', $relations);
    }
}
