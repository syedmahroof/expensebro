<?php

namespace App\Policies;

use App\Models\Entity;
use App\Models\User;

class EntityPolicy
{
    public function update(User $user, Entity $entity): bool
    {
        return $user->id === $entity->user_id;
    }

    public function delete(User $user, Entity $entity): bool
    {
        return $user->id === $entity->user_id;
    }
}
