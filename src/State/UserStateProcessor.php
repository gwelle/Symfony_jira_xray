<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use App\Entity\User;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserStateProcessor implements ProcessorInterface
{

    public function __construct(
        private ProcessorInterface $processor,
        private UserPasswordHasherInterface $passwordHasher
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
        // Handle the state
        if ($data instanceof User && $data->getPlainPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $data,
                $data->getPlainPassword()
            );
            $data->setPassword($hashedPassword);
            $data->setPlainPassword(''); // Clear the plain password after hashing
            $data->setConfirmationPassword(''); // Clear the confirmation password

        }

        // Set the creation date if not already set
        if (!$data->getCreatedAt()) {
            $data->setCreatedAt(new \DateTimeImmutable());
        }


        // Appelle le Processor par défaut pour continuer la persistance
        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}