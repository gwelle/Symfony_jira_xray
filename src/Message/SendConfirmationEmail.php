<?php
namespace App\Message;
class SendConfirmationEmail
{
    public function __construct(
        public string $email,
        public string $token,
        public string $userName,
        public bool $isResend = false
    ) {}
}
