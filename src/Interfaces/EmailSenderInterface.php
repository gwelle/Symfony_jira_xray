<?php   

namespace App\Interfaces;

use App\Interfaces\EmailStrategyInterface;
use App\Strategy\EmailData;

interface EmailSenderInterface
{
    /**
     * Sends an email using the specified strategy.
     * @param EmailData $data The data to use for building the email.
     * @return void
     */
    public function sendEmail(EmailStrategyInterface $strategy, EmailData $data): void;
}
