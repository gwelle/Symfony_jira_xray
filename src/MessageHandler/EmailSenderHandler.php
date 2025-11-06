<?php 

namespace App\MessageHandler;

use App\Message\EmailSender;
use App\Registry\EmailStrategyRegistry;
use App\Interfaces\EmailSenderInterface;
use App\Interfaces\UserProviderInterface;
use Psr\Log\LoggerInterface;
use App\Strategy\EmailData;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

// indique à Symfony que c'est un handler pour Messenger
#[AsMessageHandler] 
class EmailSenderHandler
{

    /**
     * Constructor for EmailSenderHandler.
     * @param EmailSenderInterface $emailSender The email sender interface.
     * @param EmailStrategyRegistry $emailStrategyRegistry The email strategy registry.
     * @param UserProviderInterface $userProvider The user provider interface.
     * @param LoggerInterface $logger The logger interface.
     */
    public function __construct(
        private EmailSenderInterface $emailSender,
        private EmailStrategyRegistry $emailStrategyRegistry,
        private UserProviderInterface $userProvider,
        private LoggerInterface $logger
    ){}

    /**
     * Handles the SendConfirmationEmail message.
     * @param EmailSender $message The message containing email details.
     * @return void
     */
    public function __invoke(EmailSender $message)
    {
        $user = $this->userProvider->getUserById($message->userId);

        if (!$user) {
            $this->logger->warning("User #{$message->userId} not found");
            return; // Stop si utilisateur absent
        }

        $token = $message->token;
        if (!$token) {
            $this->logger->warning("Aucun token d'activation pour {$user->getEmail()}");
            return;
        }

        $emailData = new EmailData(
            user: $user, 
            token: $token
        );

        try {
            $strategy = $this->emailStrategyRegistry->get($message->strategyName);
            $this->emailSender->sendEmail($strategy, $emailData);
        } 
        catch (\InvalidArgumentException $e) {
            $this->logger->error("Unknown email strategy: {$message->strategyName}");
            return;
        }
    }
}
