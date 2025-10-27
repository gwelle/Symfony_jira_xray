<?php

namespace App\Strategy;

use App\Interface\EmailSendStrategy;
use App\Service\MailerService;

final class ResendStrategy implements EmailSendStrategy
{

    public function __construct(private MailerService $mailerService){}
    public function sendEmail(string $email, string $token, string $fullName): void
    {
        $this->mailerService->sendConfirmationEmail($email, $token, $fullName);
    }
}