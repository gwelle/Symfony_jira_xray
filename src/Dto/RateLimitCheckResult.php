<?php

namespace App\Dto;

class RateLimitCheckResult
{
    /**
     * Constructor for RateLimitCheckResult.
     * 
     * @param bool $blocked Indicates if the rate limit has been exceeded.
     * @param \DateTimeImmutable|null $retryAfter Optional time when the user can retry.
     */
    public function __construct(
        public bool $blocked,
        public ?\DateTimeImmutable $retryAfter = null
    ) {}
}