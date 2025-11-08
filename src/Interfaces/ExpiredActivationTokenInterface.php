<?php

namespace App\Interfaces;

interface ExpiredActivationTokenInterface
{
    public function findExpiredTokens(?\DateInterval $interval = null, int $limit = 50): array;
}