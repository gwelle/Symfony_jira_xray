<?php

namespace App\Service;

use App\Dto\RateLimitCheckResult;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use App\Interfaces\RateLimiterInterface;

class RateLimiterService implements RateLimiterInterface
{
    /**
     * Checks the rate limiter for the given identifier.
     * @param RateLimiterFactory $limiter The rate limiter factory.
     * @param string $identifier The unique identifier for rate limiting (e.g., user ID, IP address).
     * @return RateLimitCheckResult|null Returns a RateLimitCheckResult indicating if the limit is reached, or null if not.
     * 
     */
    public function check(RateLimiterFactory $limiter, string $identifier): ?RateLimitCheckResult
    {
        // create a rate limiter for expired token requests based on the identifier(email,ip,etc.)
        // each call consumes 1 token
        $limit = $limiter->create($identifier)->consume(1);

        // if the limit is reached, return an error response
        if (!$limit->isAccepted()) {
            return new RateLimitCheckResult(
                blocked: true,
                retryAfter: $limit->getRetryAfter()
            );
        }

        return null;
    }

}
