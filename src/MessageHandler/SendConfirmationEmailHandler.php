<?php 

namespace App\MessageHandler;

use App\Message\SendConfirmationEmail;
use App\Service\MailerService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

// indique à Symfony que c'est un handler pour Messenger
#[AsMessageHandler] 
class SendConfirmationEmailHandler
{
    private MailerService $mailerService;

    /**
     * Constructor for SendConfirmationEmailHandler.
     * @param MailerService $mailerService The mailer service for sending emails.
     */
    public function __construct(MailerService $mailerService)
    {
        $this->mailerService = $mailerService;
    }

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
