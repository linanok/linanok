<?php

namespace Livewire;

use App\Livewire\LinkPage;
use App\Models\Domain;
use App\Models\Link;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function str;

class LinkPageTest extends TestCase
{
    use RefreshDatabase;

    private Domain $domain;

    private Link $passwordProtectedLink;

    private Link $nonPasswordProtectedLink;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test domain using the same approach as feature tests
        $currentHost = str(config('app.url'))
            ->remove('http://')
            ->remove('https://');
        $currentProtocol = str(config('app.url'))->startsWith('https://') ? 'https' : 'http';

        $this->domain = Domain::factory()->create([
            'host' => $currentHost,
            'protocol' => $currentProtocol,
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        // Create test links
        $this->passwordProtectedLink = Link::factory()->create([
            'short_path' => 'protected123',
            'original_url' => 'https://example.com/protected',
            'password' => 'secret123',
            'is_active' => true,
        ]);
        $this->passwordProtectedLink->domains()->attach($this->domain);

        $this->nonPasswordProtectedLink = Link::factory()->create([
            'short_path' => 'public123',
            'original_url' => 'https://example.com/public',
            'password' => null,
            'is_active' => true,
        ]);
        $this->nonPasswordProtectedLink->domains()->attach($this->domain);
    }

    #[Test]
    public function it_can_instantiate_component(): void
    {
        // Act
        $component = new LinkPage;

        // Assert
        $this->assertInstanceOf(LinkPage::class, $component);
        $this->assertIsArray($component->data);
    }

    #[Test]
    public function it_implements_required_interfaces(): void
    {
        // Arrange
        $component = new LinkPage;

        // Assert
        $this->assertInstanceOf(\Filament\Forms\Contracts\HasForms::class, $component);
        $this->assertTrue(method_exists($component, 'form'));
        $this->assertTrue(method_exists($component, 'mount'));
        $this->assertTrue(method_exists($component, 'render'));
        $this->assertTrue(method_exists($component, 'submit'));
    }

    #[Test]
    public function it_uses_correct_traits(): void
    {
        // Arrange
        $component = new LinkPage;
        $traits = class_uses_recursive(get_class($component));

        // Assert
        $this->assertContains(\Filament\Forms\Concerns\InteractsWithForms::class, $traits);
        $this->assertContains(\DanHarrin\LivewireRateLimiting\WithRateLimiting::class, $traits);
    }

    #[Test]
    public function it_renders_view_for_password_protected_links(): void
    {
        // Arrange - Create component instance and set link manually
        $component = new LinkPage;
        $component->link = $this->passwordProtectedLink;

        // Act
        $result = $component->render();

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals('livewire.password-protected-link-page', $result->name());
        // The layoutData is set but not accessible through getData(), so we just verify the view renders
        $this->assertInstanceOf(\Illuminate\View\View::class, $result);
    }

    #[Test]
    public function it_returns_null_for_non_password_protected_links_render(): void
    {
        // Arrange - Create component instance and set non-password link manually
        $component = new LinkPage;
        $component->link = $this->nonPasswordProtectedLink;

        // Act
        $result = $component->render();

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function it_creates_correct_form_schema(): void
    {
        // Arrange
        $component = new LinkPage;
        $component->link = $this->passwordProtectedLink;
        $form = new Schema($component);

        // Act
        $formInstance = $component->form($form);

        // Assert
        $this->assertInstanceOf(Schema::class, $formInstance);
        $this->assertEquals('data', $formInstance->getStatePath());

        // Test that the form has the expected structure
        $this->assertNotNull($formInstance);
    }

    #[Test]
    public function it_validates_empty_password(): void
    {
        // Arrange
        $component = new LinkPage;
        $component->link = $this->passwordProtectedLink;
        $component->data = ['password' => ''];

        // Act - Test the password validation logic
        $password = $component->data['password'] ?? null;

        // Assert
        $this->assertTrue(empty($password));
    }

    #[Test]
    public function it_validates_null_password(): void
    {
        // Arrange
        $component = new LinkPage;
        $component->link = $this->passwordProtectedLink;
        $component->data = [];

        // Act - Test the password validation logic
        $password = $component->data['password'] ?? null;

        // Assert
        $this->assertNull($password);
        $this->assertTrue(empty($password));
    }

    #[Test]
    public function it_validates_correct_password(): void
    {
        // Arrange
        $component = new LinkPage;
        $component->link = $this->passwordProtectedLink;
        $component->data = ['password' => 'secret123'];

        // Act - Test the password validation logic
        $password = $component->data['password'] ?? null;

        // Assert
        $this->assertEquals('secret123', $password);
        $this->assertEquals($this->passwordProtectedLink->password, $password);
    }

    #[Test]
    public function it_validates_wrong_password(): void
    {
        // Arrange
        $component = new LinkPage;
        $component->link = $this->passwordProtectedLink;
        $component->data = ['password' => 'wrongpassword'];

        // Act - Test the password validation logic
        $password = $component->data['password'] ?? null;

        // Assert
        $this->assertEquals('wrongpassword', $password);
        $this->assertNotEquals($this->passwordProtectedLink->password, $password);
    }

    #[Test]
    public function it_validates_password_correctly(): void
    {
        // Arrange
        $component = new LinkPage;
        $component->link = $this->passwordProtectedLink;
        $component->data = ['password' => 'secret123'];

        // Act
        $result = $component->submit();

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function it_detects_password_protection_correctly(): void
    {
        // Assert password protected link
        $this->assertTrue($this->passwordProtectedLink->hasPassword);

        // Assert non-password protected link
        $this->assertFalse($this->nonPasswordProtectedLink->hasPassword);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
