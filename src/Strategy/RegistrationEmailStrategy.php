<?php

namespace App\Strategy;

use App\Strategy\EmailData;
use App\Interfaces\EmailStrategyInterface;
use App\Exception\EmailBuildException;
use Symfony\Component\Mime\Email;

final class RegistrationEmailStrategy implements EmailStrategyInterface
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
     * Build an email for the specified user.
     * @param EmailData $data
     * @return Email
     * @throws EmailBuildException if email construction fails
     */
    public function buildEmail(EmailData $data): Email
    {
        // Récupérer le token d'activation
        $hashedToken = $data->hashedToken;
        if(!$hashedToken){
            throw new EmailBuildException("Aucun token d'activation trouvé pour l'utilisateur : {$data->user->getEmail()}");
        }

        $user = $data->user;

        // Valider l'adresse email de l'utilisateur
        if(empty($user->getEmail()) || !filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new EmailBuildException("Adresse email invalide : {$user->getEmail()}");
        }

        // Construire l'URL d'activation
        $rawActivationUrl = $this->activationAccountUrl . "/activate_account/" . urlencode($hashedToken);
        if (!filter_var($rawActivationUrl, FILTER_VALIDATE_URL)) {
            throw new EmailBuildException("Activation URL invalide : {$rawActivationUrl}");
        }

        // Échapper les données dynamiques : prévenir les attaques XSS
        $fullName  = htmlspecialchars($user->getFullName(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $activationUrl = htmlspecialchars($rawActivationUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        $html = "
            <p>Bonjour {$fullName},</p>
            <p>Merci de vous être inscrit. Pour activer votre compte, cliquez sur ce lien :</p>
            <p><a href='{$activationUrl}'>Activer mon compte</a></p>
            <p>Ce lien est valable 24 heures.</p>
        ";

        // Retour de l'Email (sans catch → laissé au EmailService)
        return (new Email())
            ->from('no-reply@account.com')
            ->to($user->getEmail())
            ->subject('Confirmation de la création de votre compte')
            ->html($html);
    }

    /**
     * Get the name of the strategy.
     * @return string
     */
    public function getName(): string
    {
        return 'registration_send';
    }

}
