<?php

namespace App\Interfaces;

use App\Entity\User;

interface GenerateTokenInterface
{
    /**
     * Generates a new token for the given user.
     * @param User $user The user for whom to generate the token.
     * @return string The generated token.
     * @throws \Exception if token generation fails.
     */
    public function generateToken(User $user): string;
}