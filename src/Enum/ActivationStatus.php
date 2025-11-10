<?php

namespace App\Enums;

enum ActivationStatus: string
{
    case ACTIVATED = 'success';
    case ALREADY_ACTIVATED = 'already activated';
    case EXPIRED = 'expired';
    case INVALID = 'invalid';
    case BLOCKED = 'blocked';
}
