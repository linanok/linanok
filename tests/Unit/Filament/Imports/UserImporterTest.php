<?php

namespace Tests\Unit\Filament\Imports;

use App\Filament\Imports\UserImporter;
use App\Models\User;
use Filament\Actions\Imports\Models\Import as ImportModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserImporterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string,string>  $columnMap
     * @param  array<string,mixed>  $options
     */
    protected function makeImporter(array $columnMap = [], array $options = []): UserImporter
    {
        $import = ImportModel::create([
            'file_name' => 'users.csv',
            'file_path' => 'storage/users.csv',
            'importer' => UserImporter::class,
            'processed_rows' => 0,
            'total_rows' => 1,
            'successful_rows' => 0,
            'user_id' => User::factory()->create()->id,
        ]);

        return new UserImporter($import, $columnMap, $options);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create();
        Permission::findOrCreate('create user');
        Permission::findOrCreate('update user');
        $admin->givePermissionTo('create user');
        $admin->givePermissionTo('update user');
        $this->actingAs($admin);
    }

    #[Test]
    public function it_creates_a_new_user(): void
    {
        $importer = $this->makeImporter([
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'password' => 'password',
            'is_active' => 'is_active',
            'is_super_admin' => 'is_super_admin',
        ]);

        $row = [
            'id' => null,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret1234',
            'is_active' => 'yes',
            'is_super_admin' => 'no',
        ];

        $importer($row);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'is_active' => true,
            'is_super_admin' => false,
        ]);
    }

    #[Test]
    public function it_updates_an_existing_user_and_ignores_empty_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $importer = $this->makeImporter([
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'password' => 'password',
            'is_active' => 'is_active',
            'is_super_admin' => 'is_super_admin',
        ]);

        $row = [
            'id' => (string) $user->id,
            'name' => 'New Name',
            'email' => 'old@example.com',
            'password' => '', // should be removed by beforeFill
            'is_active' => 'true',
            'is_super_admin' => 'false',
        ];

        $importer($row);

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('old@example.com', $user->email);
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->is_super_admin);
    }

    #[Test]
    public function it_validates_unique_email_on_create_and_update(): void
    {
        // Existing user
        User::factory()->create(['email' => 'existing@example.com']);

        $importer = $this->makeImporter([
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'password' => 'password',
        ]);

        // Creating a new user with duplicate email should fail
        $this->expectException(ValidationException::class);
        $importer([
            'id' => null,
            'name' => 'Dup',
            'email' => 'existing@example.com',
            'password' => 'secret1234',
        ]);
    }

    #[Test]
    public function it_denies_create_when_user_lacks_permission(): void
    {
        $noPerm = User::factory()->create();
        $this->actingAs($noPerm);

        $importer = $this->makeImporter([
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'password' => 'password',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $importer([
            'id' => null,
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'supersecret',
        ]);
    }

    #[Test]
    public function it_denies_update_when_user_lacks_permission(): void
    {
        $user = User::factory()->create(['email' => 'target@example.com']);
        $noPerm = User::factory()->create();
        $this->actingAs($noPerm);

        $importer = $this->makeImporter([
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $importer([
            'id' => (string) $user->id,
            'name' => 'Updated',
            'email' => 'target@example.com',
        ]);
    }
}
