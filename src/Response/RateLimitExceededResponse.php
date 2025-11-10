<?php

namespace App\Response;


class RateLimitExceededResponse 
{
    /**
     * Constructor for RateLimitExceededResponse.
     *
     * @param string $status The status of the error.
     * @param string $message The error message.
     * @param int $statusCode The HTTP status code for the response.
     */
    public function __construct(
        string $status, 
        string $message, 
        int $statusCode)
    {}
}
