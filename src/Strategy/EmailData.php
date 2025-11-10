<?php

namespace App\Strategy;

use App\Entity\User;

class EmailData
{
    public function __construct(
        public User $user,
        public ?string $hashedToken = null
    ) {}
}