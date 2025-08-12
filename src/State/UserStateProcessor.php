<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use App\Entity\User;
use App\Entity\ActivationToken;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserStateProcessor implements ProcessorInterface
{
    /**
     * Constructor for UserStateProcessor.
     * This processor is responsible for handling the user state processing logic.
     *
     * @param ProcessorInterface $processor The default processor to delegate to after processing.
     * @param EntityManagerInterface $entityManager The entity manager for database operations.
     * @param UserPasswordHasherInterface $passwordHasher The password hasher service.
     */
    public function __construct(
        private ProcessorInterface $processor,
        private EntityManagerInterface $entityManager,
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
        // Check if the data is an instance of User and has a plain password
        if ($data instanceof User && $data->getPlainPassword()) {

            $hashedPassword = $this->passwordHasher->hashPassword(
                $data,
                $data->getPlainPassword()
            );

            $data->setPassword($hashedPassword);
            $data->setPlainPassword(''); // Clear the plain password after hashing
            $data->setConfirmationPassword(''); // Clear the confirmation password
        
            $data->setIsActivated(false); // Set default activation status

            
            $user = $this->processor->process($data, $operation, $uriVariables, $context);

            // Generate a new plain token
            $plainToken = bin2hex(random_bytes(32));
                
            $activationToken = new ActivationToken();
            $activationToken->setAccount($data);
            $activationToken->setHashedToken(hash('sha256', $plainToken));
            $activationToken->setCreatedAt(new \DateTimeImmutable());
            $activationToken->setExpiredAt((new \DateTimeImmutable())->modify('+24 hours'));

            // Persist the activation token
            $this->entityManager->persist($activationToken);
            $this->entityManager->flush();

            // Return the user entity after processing
            return $user;
        }


        // Sinon, si ce n’est pas un User avec mot de passe à hasher,
        // on ne fait rien de spécial et on laisse le processor par défaut faire son job
        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
