<?php

namespace App\Strategy;

use App\Interface\EmailSendStrategy;
use App\Service\MailerService;

final class SendStrategy implements EmailSendStrategy
{

    /**
     * Summary of __construct
     * @param \App\Service\MailerService $mailerService
     */
    public function __construct(private MailerService $mailerService){}

    /**
     * Summary of sendEmail
     * @param string $email
     * @param string $token
     * @param string $fullName
     * @return void
     */
    public function sendEmail(string $email, string $token, string $fullName): void
    {
        $this->mailerService->sendConfirmationEmail($email, $token, $fullName);
    }
}