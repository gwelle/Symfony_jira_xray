<?php

namespace App\Strategy;

use App\Interfaces\EmailStrategyInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
final class AutomaticResendEmailStrategy  implements EmailStrategyInterface
{

    /**
     * Send an email to the specified user.
     * @param User $user
     * @return Email
     */
    public function buildEmail(User $user): Email
    {
        return (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject('Renvoi automatique')
            //->htmlTemplate('emails/automatic_resend.html.twig')
            ->context(['user' => $user]);
    }

    /**
     * Get the name of the strategy.
     * @return string
     */
    public function getName(): string
    {
        return 'automatic resend';
    }
}