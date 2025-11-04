<?php   

namespace App\Interfaces;

use App\Interfaces\EmailStrategyInterface;
use App\Entity\User;

interface EmailSenderInterface
{
    /**
     * Sends an email using the specified strategy.
     * @param User $user The user to whom the email will be sent.
     * @return void
     */
    public function sendEmail(EmailStrategyInterface $strategy, User $user): void;
}
