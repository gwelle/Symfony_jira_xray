<?php

namespace App\Interfaces;

interface ActiveActivationTokenInterface
{
    public function currentActiveTokenForUser(\App\Entity\User $user): ?string;
}