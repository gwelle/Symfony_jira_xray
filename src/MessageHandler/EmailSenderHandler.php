<?php 

namespace App\MessageHandler;

use App\Message\EmailSender;
use App\Registry\EmailStrategyRegistry;
use App\Interfaces\EmailSenderInterface;
use App\Interfaces\UserProviderInterface;
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
     */
    public function __construct(
        private EmailSenderInterface $emailSender,
        private EmailStrategyRegistry $emailStrategyRegistry,
        private UserProviderInterface $userProvider
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
            // utilisateur non trouvé → log ou ignorer
            return;
        }

        $strategy = $this->emailStrategyRegistry->get($message->strategyName);
        $this->emailSender->sendEmail($strategy, $user);
    }
}
