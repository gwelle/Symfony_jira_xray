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
     * @return string The generated activation token.
     */
    public function generateToken(User $user): string
    {
        // Generate a new plain token
        $plainToken = bin2hex(random_bytes(32));

        $activationToken = new ActivationToken();
        $activationToken->setAccount($user);
        $activationToken->setHashedToken(hash('sha256', $plainToken));
        $activationToken->setCreatedAt(new \DateTimeImmutable());


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


        if (!$token || $token->getExpiredAt() < new \DateTimeImmutable()) {
            $plainToken = $this->generateToken($user); // retourne le token en clair
            $hashedToken = hash('sha256', $plainToken); // hash pour chercher en base
            $token = $this->entityManager->getRepository(ActivationToken::class)
                ->findOneBy(['hashedToken' => $hashedToken]); // récupère l'objet ActivationToken
        }

        return $token;
    }

    /**
     * Activates a user account based on the provided token.
     *
     * @param string $token The activation token.
     * @return bool True if the account was successfully activated, false otherwise.
     */
    public function activateAccount(string $plainToken): bool
    {
        // Hash the provided token to match the stored format
        $hashedToken = hash('sha256', $plainToken);
        
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

    /**
    * Refreshes activation tokens for users who haven't activated their account after 1 hour.
    * Updates the existing token in place, setting a new creation date and hashed token.
    */
    public function refreshExpiredTokens(): void{
        // Récupérer tous les tokens sans date d'expiration et créés il y a plus d'1h
        $tokens = $this->entityManager
            ->getRepository(ActivationToken::class)
            ->createQueryBuilder('t')
            ->where('t.expiredAt IS NULL')
            ->andWhere('t.createdAt < :limit')
            ->setParameter('limit', new \DateTimeImmutable('-1 hour'))
            ->getQuery()
            ->getResult();

        foreach ($tokens as $token) {
            $account = $token->getAccount();

            // Si l'utilisateur n'est pas activé
            if (!$account->getIsActivated()) {

                // Marquer l'ancien token comme expiré
                $token->setExpiredAt(new \DateTimeImmutable());

                // Générer un nouveau token dans le même enregistrement
                $token->setHashedToken(hash('sha256', bin2hex(random_bytes(32))));
                $token->setCreatedAt(new \DateTimeImmutable());
                $token->setExpiredAt(null); // sera à nouveau mis à jour après 1h si non activé

                $this->entityManager->persist($token);
            }
        }
        $this->entityManager->flush();
    }
}
