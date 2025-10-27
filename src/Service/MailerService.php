<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MailerService{

    /**
     * constructor
     * @var MailerInterface
     */
    private MailerInterface $mailer;
    private string $activationAccountUrl;
    private LoggerInterface $logger;

    /**
     * MailerService constructor.
     * @param MailerInterface $mailer The mailer service for sending emails.
     * @param string $activationAccountUrl The base URL for account activation links.
     * @param LoggerInterface $logger The logger service for logging errors.
     */
    public function __construct(MailerInterface $mailer, string $activationAccountUrl, 
        LoggerInterface $logger){
        $this->mailer = $mailer;
        $this->activationAccountUrl = rtrim($activationAccountUrl, '/'); // pour éviter les doubles slashes
        $this->logger = $logger;
    }

    /**
     * Sends a confirmation email to the user with an activation link.
     * @param string $email The recipient's email address.
     * @param string $token The activation token to be included in the email.
     * @param string $userName The name of the user to personalize the email.
     * @param bool $isResend Indicates if this is a resend of the confirmation email.
     * @return void
     */
    public function sendConfirmationEmail(string $email, string $token, string $userName,bool $isResend = false)
    {
        $confirmationUrl = $this->activationAccountUrl . "/activate_account/".urlencode($token);
        $resendUrl = $this->activationAccountUrl . "/resend_activation_account/".urlencode($email);

        if(!empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->logger->error("Adresse email invalide : {$email}");
            return;
        }

        if ($isResend) {
        $subject = 'Renvoi du lien de confirmation de votre compte';
        $html = "<p>Bonjour {$userName},</p>
                 <p>Voici un nouveau lien pour activer votre compte :</p>
                 <p><a href='{$confirmationUrl}'>Activer mon compte</a></p>";
        }
        else {
            $subject = 'Confirmation de votre compte';
            $html = "
                <p>Bonjour {$userName},</p>
                <p>Merci de vous être inscrit. Pour activer votre compte, cliquez sur ce lien :</p>
                <p><a href='{$confirmationUrl}'>Activer mon compte</a></p>
                <p>Ce lien est valable 24 heures.</p>";
        }

        $emailMessage = (new Email())
            ->from('no-reply@account.com')
            ->to($email)
            ->subject($subject)
            ->html($html);

        try{
            $this->mailer->send($emailMessage);
            $this->logger->info("Email de confirmation envoyé à {$email}");
        }
        catch (TransportExceptionInterface $e){
            // Gérer l'erreur d'envoi d'email (journalisation, notification, etc.)
            $this->logger->error("Erreur lors de l'envoi de l'email de confirmation à {$email} : " . $e->getMessage());
        }
    }
}
