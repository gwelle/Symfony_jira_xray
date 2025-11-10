<?php

namespace App\Interfaces;

use App\Dto\ActivationResult;
use App\Entity\User;

interface AccountActivationInterface
{
    /**
     * Activates a user account based on the provided hashed token.
     * @param string $hashedToken The hashed activation token.
     * @return ActivationResult An ActivationResult object containing the status of the activation.
     * @throws \Exception if the activation process fails.
     */
    public function activatedAccount(string $hashedToken): ActivationResult;

    /**
     * Activates the user in database.
     * @param User $user The user to be activated.
     * @return bool True if activation was successful, false otherwise.
     */
    public function markUserAsActivated(User $user): bool;
}
