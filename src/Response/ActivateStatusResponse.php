<?php

namespace App\Response;

class ActivateStatusResponse
{
    public function __construct(
        public string $status,
        public mixed $data = null
    ) {}
}