<?php

namespace App\Interfaces;

use Symfony\Component\RateLimiter\RateLimiterFactory;
use App\Dto\RateLimitCheckResult;

interface RateLimiterInterface
{
    public function check(RateLimiterFactory $limiter, string $identifier):?RateLimitCheckResult;
}