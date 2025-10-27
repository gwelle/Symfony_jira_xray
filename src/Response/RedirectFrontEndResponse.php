<?php

namespace App\Response;

use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectFrontEndResponse extends RedirectResponse
{
    /**
     * Constructor for RedirectFrontEndResponse.
     * @param array<string, string> $params The query parameters to append to the URL.
     * @param int $status The HTTP status code for the redirection.
     * @param array<string, string> $headers The headers for the response.
     */
    public function __construct(array $params = [], int $status = 302, array $headers = [])
    {
        $frontendLoginUrl = $_ENV['URL_LOGIN_FRONT'] . '/login';
        $url = $frontendLoginUrl . '?' . http_build_query($params);

        parent::__construct($url, $status, $headers);
    }
}
