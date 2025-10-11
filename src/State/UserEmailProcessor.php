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
        // On délègue au processor principal de Doctrine (il fait le persist + flush)
        $user = $this->processor->process($data, $operation, $uriVariables, $context);

        // Si c'est une création d'utilisateur, on envoie l'email de confirmation              
        if ($user instanceof User && $user->getId()) {
            $tokenEntity = $user->getActivationTokens()->first()?->setAccount($user);
            $token = $tokenEntity?->getPlainToken();

            if($token) {
                try {
                    $this->bus->dispatch(new SendConfirmationEmail(
                        $user->getEmail(),
                        $token,
                    $user->getFirstName() . ' ' . $user->getLastName(),
                    false
                    ));

                    // On nettoie le token en clair après l'envoi de l'email
                    $tokenEntity->setPlainToken(null);
                    $this->entityManager->flush();
                } 
                catch (\Throwable $e) {
                    $this->logger->error('Erreur lors de l’envoi de l’email de confirmation', [
                        'user' => $user->getEmail(),
                        'error' => $e->getMessage(),
                    ]);
                }
            } 
            else {
                $this->logger->warning('Token utilisateur absent pour l’envoi de l’email', [
                    'user' => $user->getEmail(),
                ]);
            }
        }

        return $user;
    }
}