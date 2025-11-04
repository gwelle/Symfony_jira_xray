<?php

namespace App\Interfaces;

use App\Entity\User;

interface UserProviderInterface
{
    /**
     * Finds a user by their ID.
     * @param int $id The ID of the user.
     * @return User|null The user entity or null if not found.
     */
    public function getUserById(int $id): ?User;
}

