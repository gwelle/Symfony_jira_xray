<?php

namespace App\Interfaces;

use App\Entity\ActivationToken;
use App\Entity\User;

interface ActivationTokenProviderInterface
{
    public function findByToken(string $hashedToken): ?ActivationToken;
    public function finfdAllTokensForUser(User $user): array;
    public function deleteTokensForUser(User $user): void;

}