<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Message\SendConfirmationEmail;
use App\Entity\User;
use Psr\Log\LoggerInterface;

final class UserEmailProcessor implements ProcessorInterface
{
    /**
     * Constructor for UserEmailProcessor.
     * @param ProcessorInterface $processor The next processor in the chain.
     * @param MessageBusInterface $bus The message bus for dispatching messages.
     * @param EntityManagerInterface $entityManager The entity manager for database operations.
     * @param LoggerInterface $logger The logger service for logging errors.
     */
    public function __construct(
        private ProcessorInterface $processor,
        private MessageBusInterface $bus,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    /** 
     * Processes the User entity to send a confirmation email after creation.
     * @param mixed $data The data to process, expected to be an instance of User.
     * @param Operation $operation The operation being executed.
     * @param array $uriVariables The URI variables.
     * @param array $context The context for the operation.
     * @return mixed The processed data, typically the User entity.
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // On extrait le processor qui vient avant le tien dans la chaîne (UserCreationProcessor)
        $user = $this->processor->process($data, $operation, $uriVariables, $context);

        // Si c'est une création d'utilisateur, on envoie l'email de confirmation              
        if (!$user instanceof User) {
            $this->logger->error('Processor reçu une donnée non conforme');
            return null;
        }

        $activationToken = $user->getActivationTokens()->first();
        if($activationToken){
            $activationToken->setAccount($user);
        }
        $tokenPlain = $activationToken->getPlainToken();

        if (!$user->getEmail() || !$tokenPlain) {
            $this->logger->warning('Utilisateur sans email ou token : email non envoyé', [
            'user' => $user->getEmail(),
            ]);
            return $user;
        }

        try {
            $this->bus->dispatch(new SendConfirmationEmail(
                $user->getEmail(),
                $tokenPlain,
                $user->getFullName(),
                false
            ));

            // On nettoie le token en clair après l'envoi de l'email
            $activationToken->setPlainToken(null);
            $this->entityManager->flush();

            
            $this->logger->info('Email de confirmation envoyé à l\'utilisateur', [
                'user' => $user->getEmail()
            ]);
            return $user;
        } 
        catch (\Throwable $e) {
            $this->logger->error('Erreur lors de l’envoi de l’email de confirmation', [
                'user' => $user->getEmail(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}