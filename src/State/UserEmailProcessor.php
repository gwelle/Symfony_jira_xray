<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\ActivationToken;
use Symfony\Component\Messenger\MessageBusInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Message\EmailSender;
use App\Entity\User;
use Psr\Log\LoggerInterface;

/** 
 * UserEmailProcessor is responsible for sending a confirmation email to the user after creation.
 * It retrieves the activation token and dispatches a message to send the email.
 * @implements ProcessorInterface<User, User> $processor The next processor in the chain.
 */
final class UserEmailProcessor implements ProcessorInterface
{
    /**
     * Constructor for UserEmailProcessor.
     * @param ProcessorInterface<User, User> $processor The next processor in the chain.
     * @param MessageBusInterface $bus The message bus for dispatching messages.
     * @param EntityManagerInterface $entityManager The entity manager for database operations.
     * @param LoggerInterface $logger The logger service for logging errors.
     */
    public function __construct(
        private ProcessorInterface $previousProcessor,
        private MessageBusInterface $bus,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    /** 
     * Processes the User entity to send a confirmation email after creation.
     * @param mixed $data The data to process, expected to be an instance of User.
     * @param Operation $operation The operation being executed.
     * @param array<string, string> $uriVariables The URI variables.
     * @param array<string, mixed> $context The context for the operation.
     * @return mixed The processed data, typically the User entity.
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // On extrait le processor qui vient avant le tien dans la chaîne (UserCreationProcessor)
        $user = $this->previousProcessor->process($data, $operation, $uriVariables, $context);

        // Si c'est une création d'utilisateur, on envoie l'email de confirmation              
        if (!$user instanceof User) {
            $this->logger->error('Processor a reçu une donnée non conforme');
            return null;
        }

        $activationToken = $user->getActivationTokens()->first();
        $tokenPlain = null;

        if ($activationToken instanceof ActivationToken) {
            $activationToken->setAccount($user);
            $tokenPlain = $activationToken?->getPlainToken();
        }
        
        if (!$user->getEmail() || !$tokenPlain) {
            $this->logger->warning('Email ou token absent : email non envoyé', [
            'email' => $user->getEmail(),
            ]);
            return $user;
        }

        try {
            $this->bus->dispatch(new EmailSender(
                'registration_send',
                $user->getId(),
                $tokenPlain
            ));

            // On nettoie le token en clair après l'envoi de l'email
            $activationToken->setPlainToken(null);
            $this->entityManager->flush();

            $this->logger->info('Email de confirmation envoyé avec succès', [
                'email' => $user->getEmail()
            ]);     

            return $user;
        } 
        catch (\Throwable $e) {
            $this->logger->error('Erreur lors de l\'envoi du mail de confirmation', [
                'email' => $user->getEmail(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}