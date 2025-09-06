<?php
namespace App\Service;
use App\Entity\ActivationToken;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class ActivationService
{
    private EntityManagerInterface $entityManager;

    /**
     * Constructor for ActivationService.
     *
     * @param EntityManagerInterface $entityManager The entity manager for database operations.
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Generates a new activation token for the given user.
     *
     * @param User $user The user for whom the activation token is to be generated.
     * @return ActivationToken The generated activation token entity.
     */
    public function generateToken(User $user): string
    {
        // Generate a new plain token
        $plainToken = bin2hex(random_bytes(32));

        $activationToken = new ActivationToken();
        $activationToken->setAccount($user);
        $activationToken->setHashedToken(hash('sha256', $plainToken));
        $activationToken->setCreatedAt(new \DateTimeImmutable());
        $activationToken->setExpiredAt((new \DateTimeImmutable())->modify('+24 hours'));

        // Persist the token entity
        $this->entityManager->persist($activationToken);
        $this->entityManager->flush();

        // Return the plain token to be sent via email
        return $plainToken;
    }

    /**
     * Retrieves a valid activation token for the given user.
     * If no valid token exists, a new one is generated.
     *
     * @param User $user The user for whom to retrieve the activation token.
     * @return ActivationToken A valid activation token entity.
     */
    public function getValidTokenForUser(User $user): ActivationToken
    {
        // Chercher le dernier token pour cet utilisateur
        $token = $this->entityManager->getRepository(ActivationToken::class)
            ->findOneBy(
                ['account' => $user],
                ['createdAt' => 'DESC']
        );

        // Si aucun token ou token expiré, en créer un nouveau
        if (!$token || $token->getExpiredAt() < new \DateTimeImmutable()) {
            $token = $this->generateToken($user);
        }

        return $token;
    }

    /**
     * Activates a user account based on the provided token.
     *
     * @param string $token The activation token.
     * @return bool True if the account was successfully activated, false otherwise.
     */
    public function activateAccount(string $hashedToken): bool
    {
        $activationToken = $this->entityManager->getRepository(ActivationToken::class)
            ->findOneBy(['hashedToken' => $hashedToken]);

        // Si le token n'existe pas ou est expiré, retourner false
        if (!$activationToken || $activationToken->getExpiredAt() < new \DateTimeImmutable()) {
            return false;
        }

        // Activer le compte utilisateur
        $user = $activationToken->getAccount();
        $user->setIsActivated(true);

        // Remove all tokens associated with the user
        $tokens = $this->entityManager->getRepository(ActivationToken::class)
        ->findBy(['account' => $user]);

        foreach ($tokens as $token) {
            $this->entityManager->remove($token);
        }

        $this->entityManager->flush();

        return true;
    }

}
