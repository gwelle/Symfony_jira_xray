<?php

namespace App\Dto;

use App\Enums\ActivationStatus;

class ActivationResult
{
    /**
     * Constructor for ActivationResult.
     * 
     * @param ActivationStatus $status The status of the activation attempt.
     * @param mixed|null $data Optional additional data related to the activation result.
     */
    public function __construct(
        public ActivationStatus $status,
        public mixed $data = null
    ) {}
}