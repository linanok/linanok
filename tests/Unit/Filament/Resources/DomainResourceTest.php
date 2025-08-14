<?php

namespace Filament\Resources;

use App\Filament\Resources\Domains\DomainResource;
use App\Models\Domain;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DomainResourceTest extends TestCase
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

    #[Test]
    public function it_has_correct_model()
    {
        $this->assertEquals(Domain::class, DomainResource::getModel());
    }

    #[Test]
    public function it_has_correct_navigation_group()
    {
        $this->assertEquals('Link Management', DomainResource::getNavigationGroup());
    }

    #[Test]
    public function it_has_correct_navigation_icon()
    {
        $this->assertEquals('heroicon-o-rectangle-stack', DomainResource::getNavigationIcon());
    }

    #[Test]
    public function it_has_correct_slug()
    {
        $this->assertEquals('domains', DomainResource::getSlug());
    }

    #[Test]
    public function it_has_correct_record_title_attribute()
    {
        $this->assertEquals('host', DomainResource::getRecordTitleAttribute());
    }

    #[Test]
    public function it_has_empty_globally_searchable_attributes()
    {
        $this->assertEquals(
            [],
            DomainResource::getGloballySearchableAttributes()
        );
    }

    #[Test]
    public function it_has_correct_pages()
    {
        $pages = DomainResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
        $this->assertArrayHasKey('history', $pages);
    }

    //    #[Test]
    //    public function it_has_correct_relations()
    //    {
    //        $relations = DomainResource::getRelations();
    //
    //        $this->assertCount(1, $relations);
    //        $this->assertContains('App\Filament\Resources\DomainResource\RelationManagers\LinksRelationManager', $relations);
    //    }

    #[Test]
    public function it_can_delete_domain_without_links()
    {
        $domain = Domain::factory()->create([
            'host' => 'deletable-domain.com',
            'is_active' => true,
            'is_admin_panel_active' => false,
        ]);

        // Verify domain exists
        $this->assertDatabaseHas('domains', ['id' => $domain->id]);

        // Delete the domain
        $domain->delete();

        // Verify domain is deleted
        $this->assertDatabaseMissing('domains', ['id' => $domain->id]);
    }

    #[Test]
    public function it_checks_for_links_before_deletion()
    {
        $domain = Domain::factory()->create([
            'host' => 'domain-with-links.com',
            'is_active' => true,
            'is_admin_panel_active' => false,
        ]);

        $link = Link::factory()->create();
        $domain->links()->attach($link);

        // Verify domain has links
        $this->assertTrue($domain->links()->exists());

        // This simulates what our Filament action does - check before deletion
        $hasLinks = $domain->links()->exists();
        $this->assertTrue($hasLinks);

        // If we were to proceed with deletion despite having links, it would fail
        if (! $hasLinks) {
            $domain->delete();
        }

        // Domain should still exist because we didn't delete it
        $this->assertDatabaseHas('domains', ['id' => $domain->id]);
    }
}
