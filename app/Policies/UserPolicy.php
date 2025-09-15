<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * User Policy
 *
 * Handles authorization for User model operations.
 * Extends the BasePolicy to provide standard CRUD permission checks
 * using the 'user' permission prefix
 *
 * @see \App\Models\User
 * @see \App\Policies\BasePolicy
 */
class UserPolicy extends BasePolicy
{
    /** @var string The model name used for permission checking */
    protected string $modelName = 'user';

    public function update(User $user, Model $model): bool
    {
        if (! $user->is_super_admin && $user->id == $model->id) {
            return false;
        }

        return parent::update($user, $model);
    }
}
