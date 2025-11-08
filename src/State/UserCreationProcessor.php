<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use App\Entity\User;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use App\Interfaces\GenerateTokenInterface;
use Psr\Log\LoggerInterface;
use App\Exception\MissingPasswordException;
use InvalidArgumentException;

/** 
 * UserCreationProcessor is responsible for processing user creation requests.
 * It hashes the user's password and generates an activation token before persisting the user.
 * @implements ProcessorInterface<User, User> $processor The next processor in the chain.
 */
class UserCreationProcessor implements ProcessorInterface
{
    /**
     * Constructor for UserStateProcessor.
     * @param ProcessorInterface $processor The next processor in the chain.
     * @param UserPasswordHasherInterface $passwordHasher The password hasher service.
     * @param PasswordUpgraderInterface $passwordUpgrader The password upgrader service.
     * @param GenerateTokenInterface $generate The token generation service.
     * @param LoggerInterface $logger The logger service.
     */
    public function __construct(
        private ProcessorInterface $processor,
        private UserPasswordHasherInterface $passwordHasher,
        private PasswordUpgraderInterface $passwordUpgrader,
        private GenerateTokenInterface $tokenGenerator,
        private LoggerInterface $logger
    ) {}

    /**
     * Processes the User entity before persisting it.
     * This method hashes the password and sets the creation date if not already set.
     * @param  $data The data to process, expected to be an instance of User.
     * @param Operation $operation The operation being executed.
     * @param array<string, string> $uriVariables The URI variables.
     * @param array<string, mixed> $context The context for the operation.
     * @return mixed The processed data, typically the User entity.
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Check if the data is an instance of User and has a plain password
        if (!$data instanceof User) {
            throw new InvalidArgumentException('Le Processor a reçu une donnée non conforme : User attendu.');
        }

        if (!$data->getPlainPassword()) {
            throw new MissingPasswordException('Mot de passe manquant, utilisateur non créé.');
        }

        try {
            $hashed = $this->passwordHasher->hashPassword($data, $data->getPlainPassword());

            $this->passwordUpgrader->upgradePassword($data, $hashed);

            $data->erasePlainAndConfirmationPassword();

            $this->tokenGenerator->generateToken($data);

            $this->logger->info('User created successfully');

            return $this->processor->process($data, $operation, $uriVariables, $context);
        } 
        catch (\Throwable $e) {
            $this->logger->error('Erreur lors du traitement de création utilisateur', [
                'exceptionClass' => get_class($e),
                'message' => $e->getMessage(),
                'userId' => $data->userId ?? null
            ]);
            
            throw $e; // ✅ on relance proprement
        }
    }

}
