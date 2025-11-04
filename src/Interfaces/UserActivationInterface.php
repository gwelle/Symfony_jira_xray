<?php

namespace App\Interfaces;

interface UserActivationInterface
{
    /**
     * Activates a user account based on the provided hashed token.
     * @param string $hashedToken The hashed activation token.
     * @return ActivateStatusResponse The response indicating the activation status.
     * @throws \Exception if the activation process fails.
     */
    public function activateAccount(string $hashedToken): ActivateStatusResponse;

    /**
     * Activates the user in database.
     * @return bool True if activation was successful, false otherwise.
     * @throws \Exception if the activation process fails.
     */
    public function activate(): bool;
}
