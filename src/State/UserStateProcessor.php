<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use App\Entity\User;

use ApiPlatform\State\ProcessorInterface;
use App\Service\ActivationService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\MailerService;

class UserStateProcessor implements ProcessorInterface
{
    /**
     * Constructor for UserStateProcessor.
     * @param ProcessorInterface $processor The next processor in the chain.
     * @param UserPasswordHasherInterface $passwordHasher The password hasher service.
     * @param ActivationService $activationService The activation service for generating tokens.
     */
    public function __construct(
        private ProcessorInterface $processor,
        private UserPasswordHasherInterface $passwordHasher,
        private ActivationService $activationService,
        private MailerService $mailerService
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

            // Generate and persist the activation token
            $token = $this->activationService->generateToken($user);

            // Send the confirmation email with the token
            $this->mailerService->sendConfirmationEmail(
                $user->getEmail(),
                $token,
                $user->getFirstName().' '.$user->getLastName()
            );

            return $user ;
        }


        // Sinon, si ce n’est pas un User avec mot de passe à hasher,
        // on ne fait rien de spécial et on laisse le processor par défaut faire son job
        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
