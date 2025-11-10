<?php

namespace App\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiErrorResponse extends JsonResponse
{
    /**
     * Constructor for ApiErrorResponse.
     *
     * @param string $status The status of the error.
     * @param string $message The error message.
     * @param int $statusCode The HTTP status code for the response.
     */
    public function __construct(string $status, string $message, int $statusCode)
    {
        parent::__construct([
            'status'  => $status,
            'error'   => $message,
        ], $statusCode);
    }
}
