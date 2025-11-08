<?php

namespace App\Interfaces;

interface ExpiredTokenRefreshInterface
{
    /**
     * Refreshes expired tokens created within the specified interval.
     * @param \DateInterval|null $interval The time interval to look back from now. Defaults to 1 hour if null.
     * @param int $limit The maximum number of tokens to refresh.
     * @return void
     * @throws \Exception if the refresh process fails.
     */
    public function refreshExpiredTokens(?\DateInterval $interval = null, int $limit = 50): void;
}
