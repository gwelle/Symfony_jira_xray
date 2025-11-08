<?php

namespace App\Service;

use App\Interfaces\AccountActivationInterface;
use App\Response\ActivateStatusResponse;
use App\Entity\User;

class AccountActivationService implements AccountActivationInterface
{   
    public function activatedAccount(string $hashedToken): ActivateStatusResponse
    {
        return new ActivateStatusResponse('success');
    }

    /**
     * Activates the user in database.
     * @param User $user The user to be activated.
     * @return bool True if activation was successful, false otherwise.
     */
    public function markUserAsActivated(User $user): bool
    {
        if(!$user->isActivated()) {
            $user->setIsActivated(true);
            return true;
        }
        return false;
    }
}