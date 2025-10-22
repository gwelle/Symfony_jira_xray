<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use App\Entity\User;
use ApiPlatform\State\ProcessorInterface;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Service\ActivationService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;

class UserCreationProcessor implements ProcessorInterface
{
    /**
     * Constructor for UserStateProcessor.
     * @param ProcessorInterface $processor The next processor in the chain.
     * @param UserPasswordHasherInterface $passwordHasher The password hasher service.
     * @param UserRepository $userRepository The user repository for database operations.
     * @param UserService $userService The user service for user-related operations.
     * @param ActivationService $activationService The activation service for generating tokens.
     * @param LoggerInterface $logger The logger service for logging errors.
     */
    public function __construct(
        private ProcessorInterface $processor,
        private UserPasswordHasherInterface $passwordHasher,
        private UserService $userService,
        private ActivationService $activationService,
        private LoggerInterface $logger
    ) {}

    /**
     * Processes the User entity before persisting it.
     * This method hashes the password and sets the creation date if not already set.
     * @param mixed $data The data to process, expected to be an instance of User.
     * @param Operation $operation The operation being executed.
     * @param array $uriVariables The URI variables.
     * @param array $context The context for the operation.
     * @return mixed The processed data, typically the User entity.
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Check if the data is an instance of User and has a plain password
        if (!$data instanceof User) {
            $this->logger->error('Processor reçu une donnée non conforme');
            return null;
        }

        if (!$data->getPlainPassword()) {
            $this->logger->error('Mot de passe manquant, utilisateur non créé.');
            return null;
        }

        try {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $data, 
                $data->getPlainPassword()
            );
            $this->userService->upgradeUserPassword($data, $hashedPassword);
            
            $this->activationService->generateToken($data);

            $this->logger->info('User created successfully');
            return $this->processor->process($data, $operation, $uriVariables, $context);
        } 
        catch (\Throwable $e) {
            $this->logger->error('Erreur lors du traitement de création utilisateur', [
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }
}
