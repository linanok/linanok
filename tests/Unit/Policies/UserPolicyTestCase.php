<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;

class UserPolicyTestCase extends BasePolicyTestCase
{
    protected function setUp(): void
    {
        $this->modelName = 'user';
        $this->policyClass = UserPolicy::class;
        $this->model = new User;

        parent::setUp();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_update_another_users_profile_without_permission()
    {
        // Create two test users
        $testUser1 = User::factory()->create();
        $testUser2 = User::factory()->create();

        // Set the authenticated user
        $this->actingAs($testUser1);

        // User should not be able to update another user's profile without permission
        $this->assertFalse($testUser1->can('update', $testUser2));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_with_permission_can_update_another_users_profile()
    {
        // Create a test user with permission
        $testUser = User::factory()->create();
        $testUser->givePermissionTo('update user');

        // Create another user
        $anotherUser = User::factory()->create();

        // Set the authenticated user
        $this->actingAs($testUser);

        // User with permission should be able to update another user's profile
        $this->assertTrue($testUser->can('update', $anotherUser));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function non_super_admin_user_cannot_update_their_own_profile()
    {
        // Create a regular user (not super admin)
        $regularUser = User::factory()->create([
            'is_super_admin' => false,
        ]);

        // Give them update user permission
        $regularUser->givePermissionTo('update user');

        // Set the authenticated user
        $this->actingAs($regularUser);

        // Non-super-admin user should NOT be able to update their own profile
        // This is the specific logic in UserPolicy::update()
        $this->assertFalse($regularUser->can('update', $regularUser));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function super_admin_can_update_their_own_profile()
    {
        // Create a super admin user
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        // Set the authenticated user
        $this->actingAs($superAdmin);

        // Super admin should be able to update their own profile
        $this->assertTrue($superAdmin->can('update', $superAdmin));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function non_super_admin_with_permission_can_update_other_users()
    {
        // Create a regular user with update permission
        $userWithPermission = User::factory()->create([
            'is_super_admin' => false,
        ]);
        $userWithPermission->givePermissionTo('update user');

        // Create another user to update
        $otherUser = User::factory()->create();

        // Set the authenticated user
        $this->actingAs($userWithPermission);

        // User with permission should be able to update other users (just not themselves)
        $this->assertTrue($userWithPermission->can('update', $otherUser));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function non_super_admin_without_permission_cannot_update_any_user()
    {
        // Create a regular user without update permission
        $regularUser = User::factory()->create([
            'is_super_admin' => false,
        ]);

        // Create another user
        $otherUser = User::factory()->create();

        // Set the authenticated user
        $this->actingAs($regularUser);

        // User without permission should not be able to update any user
        $this->assertFalse($regularUser->can('update', $otherUser));
        $this->assertFalse($regularUser->can('update', $regularUser));
    }
}
