<?php

namespace App\Interfaces;

use App\Entity\ActivationToken;

interface RegenerateTokenInterface
{
    /**
     * Generates a new token for the given user.
     * @param ActivationToken $oldActivationToken The old activation token.
     * @return string The newly generated token.
     * @throws \Exception if the token regeneration process fails.
     */
    public function regenerateToken(ActivationToken $oldActivationToken): string;
}