<?php

namespace Tests\Unit\Policies;

use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;

class RolePolicyTestCase extends BasePolicyTestCase
{
    protected function setUp(): void
    {
        $this->modelName = 'role';
        $this->policyClass = RolePolicy::class;

        // Create the model after parent setup to ensure the database is properly set up
        parent::setUp();

        // Use an existing role instead of creating a new one
        $this->model = Role::first();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function non_super_admin_user_cannot_update_role_they_have()
    {
        // Create a regular user (not super admin)
        $regularUser = User::factory()->create([
            'is_super_admin' => false,
        ]);

        // Give them update role permission
        $regularUser->givePermissionTo('update role');

        // Create a role and assign it to the user
        $role = Role::create([
            'name' => 'Test Role',
            'guard_name' => 'web',
        ]);
        $regularUser->assignRole($role);

        // Set the authenticated user
        $this->actingAs($regularUser);

        // Non-super-admin user should NOT be able to update a role they have
        // This is the specific logic in RolePolicy::update()
        $this->assertFalse($regularUser->can('update', $role));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function super_admin_can_update_role_they_have()
    {
        // Create a super admin user
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        // Create a role and assign it to the super admin
        $role = Role::create([
            'name' => 'Admin Role',
            'guard_name' => 'web',
        ]);
        $superAdmin->assignRole($role);

        // Set the authenticated user
        $this->actingAs($superAdmin);

        // Super admin should be able to update any role, even ones they have
        $this->assertTrue($superAdmin->can('update', $role));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function non_super_admin_with_permission_can_update_roles_they_dont_have()
    {
        // Create a regular user with update permission
        $userWithPermission = User::factory()->create([
            'is_super_admin' => false,
        ]);
        $userWithPermission->givePermissionTo('update role');

        // Create a role but don't assign it to the user
        $role = Role::create([
            'name' => 'Other Role',
            'guard_name' => 'web',
        ]);

        // Set the authenticated user
        $this->actingAs($userWithPermission);

        // User with permission should be able to update roles they don't have
        $this->assertTrue($userWithPermission->can('update', $role));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function non_super_admin_without_permission_cannot_update_any_role()
    {
        // Create a regular user without update permission
        $regularUser = User::factory()->create([
            'is_super_admin' => false,
        ]);

        // Create a role
        $role = Role::create([
            'name' => 'Some Role',
            'guard_name' => 'web',
        ]);

        // Set the authenticated user
        $this->actingAs($regularUser);

        // User without permission should not be able to update any role
        $this->assertFalse($regularUser->can('update', $role));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function non_super_admin_with_permission_can_update_one_role_but_not_another_they_have()
    {
        // Create a regular user with update permission
        $userWithPermission = User::factory()->create([
            'is_super_admin' => false,
        ]);
        $userWithPermission->givePermissionTo('update role');

        // Create two roles
        $roleUserHas = Role::create([
            'name' => 'User Role',
            'guard_name' => 'web',
        ]);
        $roleUserDoesntHave = Role::create([
            'name' => 'Other Role',
            'guard_name' => 'web',
        ]);

        // Assign one role to the user
        $userWithPermission->assignRole($roleUserHas);

        // Set the authenticated user
        $this->actingAs($userWithPermission);

        // User should be able to update the role they don't have
        $this->assertTrue($userWithPermission->can('update', $roleUserDoesntHave));

        // But should NOT be able to update the role they do have
        $this->assertFalse($userWithPermission->can('update', $roleUserHas));
    }
}
