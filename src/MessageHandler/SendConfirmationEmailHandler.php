<?php 

namespace App\MessageHandler;

use App\Service\MailerService;
use App\Message\SendConfirmationEmail;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

// indique à Symfony que c'est un handler pour Messenger
#[AsMessageHandler] 
class SendConfirmationEmailHandler
{

    /**
     * Constructor for SendConfirmationEmailHandler.
     * @param MailerService $mailerService The mailer service to send emails.
     */
    public function __construct(private MailerService $mailerService){}

    /**
     * Handles the SendConfirmationEmail message.
     * This method is invoked automatically by the Messenger component.
     * It uses the MailerService to send a confirmation email.
     * @param \App\Message\SendConfirmationEmail $message
     * @return void
     */
    public function __invoke(SendConfirmationEmail $message)
    {
        $this->mailerService->sendConfirmationEmail(
            $message->email,
            $message->token,
            $message->userName,
            $message->isResend
        );
    }   
}
