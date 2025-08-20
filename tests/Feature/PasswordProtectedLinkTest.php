<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordProtectedLinkTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_password_form_for_protected_links(): void
    {
        // Arrange
        $domain = Domain::factory()->create([
            'host' => 'example.com',
            'protocol' => 'https',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        $link = Link::factory()->create([
            'is_active' => true,
            'original_url' => 'https://target-site.com',
            'slug' => 'abc123',
            'password' => 'secret123',
        ]);

        $link->domains()->attach($domain);

        // Act
        $response = $this->get('https://example.com/l/abc123');

        // Assert
        $response->assertOk()
            ->assertSeeText('Password Protected')
            ->assertSeeText('Please enter the password to access this link')
            ->assertSeeLivewire('link-page');
    }

    #[Test]
    public function it_redirects_with_correct_password(): void
    {
        // Arrange
        $currentHost = str(config('app.url'))
            ->remove('http://')
            ->remove('https://');
        $currentProtocol = str(config('app.url'))->startsWith('https://') ? 'https' : 'http';

        $domain = Domain::factory()->create([
            'host' => $currentHost,
            'protocol' => 'http',
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        $link = Link::factory()->create([
            'is_active' => true,
            'original_url' => 'https://target-site.com',
            'slug' => 'abc123',
            'password' => 'secret123',
            'send_ref_query_parameter' => false,
        ]);

        $link->domains()->attach($domain);

        // Act & Assert
        Livewire::test('link-page', [
            'short_path' => 'abc123',
            'link' => $link,
            'data' => [
                'password' => null,
                'errors' => [],
            ],
        ])
            ->set('data.password', 'secret123')
            ->call('submit')
            ->assertRedirect('https://target-site.com');
    }

    #[Test]
    public function it_shows_error_with_wrong_password(): void
    {
        // Arrange
        $currentHost = str(config('app.url'))
            ->remove('http://')
            ->remove('https://')
            ->toString();
        $currentProtocol = str(config('app.url'))->startsWith('https://') ? 'https' : 'http';

        $domain = Domain::factory()->create([
            'host' => $currentHost,
            'protocol' => $currentProtocol,
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        $link = Link::factory()->create([
            'is_active' => true,
            'original_url' => 'https://target-site.com',
            'slug' => 'abc123',
            'password' => 'secret123',
        ]);

        $link->domains()->attach($domain);

        // Mock the forCurrentDomain scope
        $this->mockForCurrentDomainScope($domain);

        // Act & Assert
        Livewire::test('link-page', [
            'short_path' => 'abc123',
            'link' => $link,
            'data' => [
                'password' => null,
                'errors' => [],
            ],
        ])
            ->set('data.password', 'wrongpass')
            ->call('submit')
            ->assertNotified('Password is wrong');
    }

    protected function mockForCurrentDomainScope($domain): void
    {
        // Create a partial mock of the Link model
        $linkMock = Mockery::mock('App\Models\Link')->makePartial();

        // Mock the forCurrentDomain scope to return the query builder
        $linkMock->shouldReceive('scopeForCurrentDomain')
            ->andReturnUsing(function ($query) use ($domain) {
                return $query->whereHas('domains', function (Builder $query) use ($domain) {
                    $query->where('domains.id', $domain->id);
                });
            });

        // Replace the Link model with our mock
        app()->instance('App\Models\Link', $linkMock);
    }

    #[Test]
    public function it_requires_password_field(): void
    {
        // Arrange
        $currentHost = str(config('app.url'))
            ->remove('http://')
            ->remove('https://');
        $currentProtocol = str(config('app.url'))->startsWith('https://') ? 'https' : 'http';

        $domain = Domain::factory()->create([
            'host' => $currentHost,
            'protocol' => $currentProtocol,
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        $link = Link::factory()->create([
            'is_active' => true,
            'original_url' => 'https://target-site.com',
            'slug' => 'abc123',
            'password' => 'secret123',
        ]);

        $link->domains()->attach($domain);

        // Mock the forCurrentDomain scope
        $this->mockForCurrentDomainScope($domain);

        // Act & Assert
        Livewire::test('link-page', [
            'short_path' => 'abc123',
            'link' => $link,
            'data' => [
                'password' => null,
                'errors' => [],
            ],
        ])
            ->set('data.password', '')
            ->call('submit')
            ->assertNotified('Password is required');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
