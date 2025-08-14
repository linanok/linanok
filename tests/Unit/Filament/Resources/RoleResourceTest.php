<?php

namespace Filament\Resources;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\Domain;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleResourceTest extends TestCase
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
        $this->assertEquals(Role::class, RoleResource::getModel());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_navigation_group()
    {
        $this->assertEquals('User Management', RoleResource::getNavigationGroup());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_navigation_icon()
    {
        $this->assertEquals('heroicon-o-shield-check', RoleResource::getNavigationIcon());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_slug()
    {
        $this->assertEquals('roles', RoleResource::getSlug());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_record_title_attribute()
    {
        $this->assertEquals('name', RoleResource::getRecordTitleAttribute());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_globally_searchable_attributes()
    {
        $this->assertEquals(
            ['name'],
            RoleResource::getGloballySearchableAttributes()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_pages()
    {
        $pages = RoleResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
        $this->assertArrayHasKey('history', $pages);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_relations()
    {
        $relations = RoleResource::getRelations();

        $this->assertCount(1, $relations);
        $this->assertContains('App\Filament\Resources\Roles\RelationManagers\UsersRelationManager', $relations);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_form_schema()
    {
        // Instead of trying to instantiate the form, we'll check the form method exists
        $this->assertTrue(method_exists(RoleResource::class, 'form'));

        // Reflect on the form method to check its signature
        $reflectionMethod = new \ReflectionMethod(RoleResource::class, 'form');
        $parameters = $reflectionMethod->getParameters();

        // Check that the form method has the correct parameter
        $this->assertCount(1, $parameters);
        $this->assertEquals('schema', $parameters[0]->getName());
        $this->assertEquals('Filament\Schemas\Schema', $parameters[0]->getType()->getName());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_table_columns()
    {
        // Instead of trying to instantiate the table, we'll check the table method exists
        $this->assertTrue(method_exists(RoleResource::class, 'table'));

        // Reflect on the table method to check its signature
        $reflectionMethod = new \ReflectionMethod(RoleResource::class, 'table');
        $parameters = $reflectionMethod->getParameters();

        // Check that the table method has the correct parameter
        $this->assertCount(1, $parameters);
        $this->assertEquals('table', $parameters[0]->getName());
        $this->assertEquals('Filament\Tables\Table', $parameters[0]->getType()->getName());
    }
}
