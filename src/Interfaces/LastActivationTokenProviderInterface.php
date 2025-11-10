<?php

namespace App\Interfaces;

use App\Entity\ActivationToken;
use App\Entity\LastActivationToken;


interface LastActivationTokenProviderInterface
{
    public function saveLastToken(ActivationToken $token): void;
    public function findLastArchivedToken(string $hashedToken): ?LastActivationToken;
}