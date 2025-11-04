<?php

namespace App\Strategy;

use App\Entity\User;
use App\Interfaces\EmailStrategyInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Email;

final class RegistrationEmailStrategy implements EmailStrategyInterface
{

    /**
     * Build an email for the specified user.
     * @param User $user
     * @return Email
     */
    public function buildEmail(User $user): Email
    {
        // c'est juste un exemple, je vais modifier cela plus tard
        return (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject('Bienvenue !')
            //->htmlTemplate('emails/registration.html.twig')
            ->context(['user' => $user]);
    }

    /**
     * Get the name of the strategy.
     * @return string
     */
    public function getName(): string
    {
        return 'registration';
    }

}