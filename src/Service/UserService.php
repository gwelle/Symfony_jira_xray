<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

class UserService
{
    private UserRepository $userRepository;

    /**
     * Constructor for UserService.
     * @param UserRepository $userRepository The user repository for user-related database operations.
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Upgrades the password for the given user.
     *
     * @param User $user The user whose password is to be upgraded.
     * @param string $newHashedPassword The new hashed password.
     * @return void
     */
    public function upgradeUserPassword(User $user, string $newHashedPassword): void
    {
        $this->userRepository->upgradePassword($user, $newHashedPassword);
    }

    /**
     * Activates the given user account.
     *
     * @param User $user The user to activate.
     * @return void
     */
    public function activate(User $user): void
    {
        if (!$user->isActivated()) {
            $user->setIsActivated(true);
        }
    }

}