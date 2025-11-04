<?php
namespace App\Interfaces;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Email;

interface EmailStrategyInterface
{
    /**
     * Build an email for the specified user.
     * @param User $user
     * @return Email
     */
    public function buildEmail(User $user): Email;

    /**
     * Get the name of the strategy.
     * @return string
     */
    public function getName(): string;
}
