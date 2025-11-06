<?php

namespace App\Interfaces;

use App\Strategy\EmailData;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Email;

interface EmailStrategyInterface
{
    /**
     * Build an email for the specified user.
     * @param EmailData $data
     * @return Email
     */
    public function buildEmail(EmailData $data): Email;

    /**
     * Get the name of the strategy.
     * @return string
     */
    public function getName(): string;
}
