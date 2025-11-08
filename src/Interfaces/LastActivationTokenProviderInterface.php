<?php

namespace App\Interfaces;

use App\Entity\ActivationToken;
use App\Entity\LastActivationToken;
use App\Entity\User;

interface LastActivationTokenProviderInterface
{
    public function saveLastToken(ActivationToken $token): void;
    public function findLastArchivedToken(User $user): ?LastActivationToken;
}