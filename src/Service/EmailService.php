<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use App\Interfaces\EmailSenderInterface;
use App\Interfaces\EmailStrategyInterface;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class EmailService implements EmailSenderInterface{

    /**
     * constructor
     * @var MailerInterface
     */
    private MailerInterface $mailer;
    private LoggerInterface $logger;

    /**
     * EmailService constructor.
     * @param MailerInterface $mailer The mailer service for sending emails.
     * @param LoggerInterface $logger The logger service for logging errors.
     */
    public function __construct(MailerInterface $mailer, LoggerInterface $logger){
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    /**
     * Sends an email using the specified strategy.
     * @param EmailStrategyInterface $strategy The email strategy to use for building the email.
     * @param User $user The user to whom the email will be sent.
     * @return void
     */
    public function sendEmail(EmailStrategyInterface $strategy, User $user): void
    {
        try {
            $email = $strategy->buildEmail($user);
            $this->mailer->send($email);
            $this->logger->info("Email envoyé à {$user->getEmail()} avec stratégie " . $strategy->getName());

        } 
        catch (TransportExceptionInterface $e) {
            // Gérer l'erreur d'envoi d'email (journalisation, notification, etc.)
            $this->logger->error("Erreur lors de l'envoi de l'email à {$user->getEmail()} avec stratégie " . $strategy->getName() . " : " . $e->getMessage());

        }
    }
}
