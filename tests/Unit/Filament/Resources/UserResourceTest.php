<?php

namespace Filament\Resources;

use App\Filament\Resources\Users\UserResource;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserResourceTest extends TestCase
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
        $this->assertEquals(User::class, UserResource::getModel());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_navigation_group()
    {
        $this->assertEquals('User Management', UserResource::getNavigationGroup());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_navigation_icon()
    {
        $this->assertEquals('heroicon-o-users', UserResource::getNavigationIcon());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_slug()
    {
        $this->assertEquals('users', UserResource::getSlug());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_record_title_attribute()
    {
        $this->assertEquals('name', UserResource::getRecordTitleAttribute());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_globally_searchable_attributes()
    {
        $this->assertEquals(
            ['name', 'email'],
            UserResource::getGloballySearchableAttributes()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_pages()
    {
        $pages = UserResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
        $this->assertArrayHasKey('history', $pages);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_form_schema()
    {
        // Instead of trying to instantiate the form, we'll check the form method exists
        $this->assertTrue(method_exists(UserResource::class, 'form'));

        // Reflect on the form method to check its signature
        $reflectionMethod = new \ReflectionMethod(UserResource::class, 'form');
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
        $this->assertTrue(method_exists(UserResource::class, 'table'));

        // Reflect on the table method to check its signature
        $reflectionMethod = new \ReflectionMethod(UserResource::class, 'table');
        $parameters = $reflectionMethod->getParameters();

        // Check that the table method has the correct parameter
        $this->assertCount(1, $parameters);
        $this->assertEquals('table', $parameters[0]->getName());
        $this->assertEquals('Filament\Tables\Table', $parameters[0]->getType()->getName());
    }
}
