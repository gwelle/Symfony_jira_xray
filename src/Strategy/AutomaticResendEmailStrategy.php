<?php

namespace App\Strategy;

use App\Strategy\EmailData;
use App\Interfaces\EmailStrategyInterface;
use Symfony\Component\Mime\Email;
use App\Exception\EmailBuildException;

final class AutomaticResendEmailStrategy  implements EmailStrategyInterface
{

    private string $activationAccountUrl;

    /**
     * Constructor.
     * @param string $activationAccountUrl
     */
    public function __construct(string $activationAccountUrl)
    {
        $this->activationAccountUrl = rtrim($activationAccountUrl, '/'); // pour éviter les doubles slashes
    }

    /**
     * Send an email to the specified user.
     * @param EmailData $data
     * @return Email
     * @throws EmailBuildException if email construction fails
     */
    public function buildEmail(EmailData $data): Email
    {
        $user = $data->user;

        // Valider l'adresse email de l'utilisateur
        if(empty($user->getEmail()) || !filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new EmailBuildException("Adresse email invalide : {$user->getEmail()}");
        }

        // Construire l'URL de renvoi
        $rawResendUrl = $this->activationAccountUrl . "/resend_activation_account/".urlencode($user->getEmail());
        if (!filter_var($rawResendUrl, FILTER_VALIDATE_URL)) {
            throw new EmailBuildException("Resend URL invalide : {$rawResendUrl}");
        }

        // Échapper les données dynamiques : prévenir les attaques XSS
        $fullName  = htmlspecialchars($user->getFullName(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $resenActivationdUrl = htmlspecialchars($rawResendUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        $html = "
            <p>Bonjour {$fullName},</p>
            <p>Voici un nouveau lien pour activer votre compte :</p>
            <p><a href='{$resenActivationdUrl}'>Activer mon compte</a></p>
        ";

        // Retour de l'Email (sans catch → laissé au EmailService)
        return (new Email())
            ->from('no-reply@account.com')
            ->to($user->getEmail())
            ->subject('Renvoi du lien de confirmation de votre compte')
            ->html($html);
    }

    /**
     * Get the name of the strategy.
     * @return string
     */
    public function getName(): string
    {
        return 'automatic_resend';
    }
}