<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService{

    /**
     * constructor
     * @var MailerInterface
     */
    private MailerInterface $mailer;
    private string $activationAccountUrl;

    /**
     * MailerService constructor.
     *
     * @param MailerInterface $mailer The mailer service for sending emails.
     * @param string $activationAccountUrl The base URL for account activation links.
     */
    public function __construct(MailerInterface $mailer, string $activationAccountUrl){
        $this->mailer = $mailer;
        $this->activationAccountUrl = rtrim($activationAccountUrl, '/'); // pour éviter les doubles slashes
    }

    /**
     * Sends a confirmation email to the user with an activation link.
     *
     * @param string $email The recipient's email address.
     * @param string $token The activation token to be included in the email.
     * @param string $userName The name of the user to personalize the email.
     * @param bool $isResend Indicates if this is a resend of the confirmation email.
     */
    public function sendConfirmationEmail(string $email, string $token, string $userName,bool $isResend = false)
    {
        $confirmationUrl = $this->activationAccountUrl . "/activate_account/".urlencode($token);
        $resendUrl = $this->activationAccountUrl . "/resend_activation_account/".urlencode($email);

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
                <p>Ce lien est valable 24 heures.</p>
                <p>Si le lien est expiré, <a href='{$resendUrl}'>cliquez ici pour demander un nouvel e-mail de confirmation</a>.</p>";
    }

        $emailMessage = (new Email())
            ->from('no-reply@account.com')
            ->to($email)
            ->subject($subject)
            ->html($html);

        $this->mailer->send($emailMessage);
    }
}
