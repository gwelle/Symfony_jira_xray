<?php

namespace App\Response;

use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectFrontEndResponse extends RedirectResponse
{
    public function __construct(array $params = [], int $status = 302, array $headers = [])
    {
        $frontendLoginUrl = $_ENV['URL_LOGIN_FRONT'] . '/login';
        $url = $frontendLoginUrl . '?' . http_build_query($params);

        parent::__construct($url, $status, $headers);
    }
}
