<?php

namespace Tests\Unit\Filament\Resources;

use App\Filament\Resources\Links\LinkResource;
use App\Models\Domain;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LinkResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_correct_model(): void
    {
        $this->assertEquals(Link::class, LinkResource::getModel());
    }

    #[Test]
    public function it_has_correct_navigation_group(): void
    {
        $this->assertEquals('Link Management', LinkResource::getNavigationGroup());
    }

    #[Test]
    public function it_has_correct_navigation_icon(): void
    {
        $this->assertEquals('heroicon-o-link', LinkResource::getNavigationIcon());
    }

    #[Test]
    public function it_has_correct_slug(): void
    {
        $this->assertEquals('links', LinkResource::getSlug());
    }

    #[Test]
    public function it_has_correct_record_title_attribute(): void
    {
        $this->assertEquals('short_path', LinkResource::getRecordTitleAttribute());
    }

    #[Test]
    public function it_has_correct_globally_searchable_attributes(): void
    {
        $this->assertEquals(
            ['slug', 'original_url', 'description'],
            LinkResource::getGloballySearchableAttributes()
        );
    }

    #[Test]
    public function it_has_correct_pages(): void
    {
        // Act
        $pages = LinkResource::getPages();

        // Assert
        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
        $this->assertArrayHasKey('history', $pages);
    }

    #[Test]
    public function it_has_correct_widgets(): void
    {
        // Act
        $widgets = LinkResource::getWidgets();

        // Assert
        $this->assertCount(4, $widgets);
        $this->assertContains('App\Filament\Resources\Links\Widgets\LinkVisitsCountChart', $widgets);
        $this->assertContains('App\Filament\Resources\Links\Widgets\LinkVisitsByBrowserPieChart', $widgets);
        $this->assertContains('App\Filament\Resources\Links\Widgets\LinkVisitsByPlatformPieChart', $widgets);
        $this->assertContains('App\Filament\Resources\Links\Widgets\LinkVisitsByCountryPieChart', $widgets);
    }

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
}
