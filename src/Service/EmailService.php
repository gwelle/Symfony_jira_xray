<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use App\Interfaces\EmailSenderInterface;
use App\Interfaces\EmailStrategyInterface;
use App\Strategy\EmailData;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Exception\EmailBuildException;
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
     * @param EmailData $data The data to use for building the email.
     * @return void
     * @throws TransportExceptionInterface If there is an error during email sending.
     * @throws EmailBuildException If there is an error during email building.
     */
    public function sendEmail(EmailStrategyInterface $strategy, EmailData $data): void
    {
        try {
            $email = $strategy->buildEmail($data);
            $user = $data->user;
            $this->mailer->send($email);
            $this->logger->info("Email envoyé à {$user->getEmail()} avec stratégie " . $strategy->getName());

        } 
        catch (EmailBuildException $e) {
            $this->logger->error("Erreur lors de la construction de l'email : " . $e->getMessage());
            return;
        }
        catch (TransportExceptionInterface $e) {
            $this->logger->error("Erreur lors de l'envoi de l'email à {$user->getEmail()} avec stratégie " . $strategy->getName() . " : " . $e->getMessage());
        }
    }
}
