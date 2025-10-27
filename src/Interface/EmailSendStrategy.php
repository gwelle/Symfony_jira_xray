<?php
namespace App\Interface;

interface EmailSendStrategy
{
    /**
     * Summary of sendEmail
     * @param string $email
     * @param string $token
     * @param string $fullName
     * @return void
     */
    public function sendEmail(string $email, string $token, string $fullName): void;
}
