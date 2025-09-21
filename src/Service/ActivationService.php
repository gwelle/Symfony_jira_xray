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
        $activationToken->setExpiredAt(null);

        // Persist the token entity
        $this->entityManager->persist($activationToken);
        $this->entityManager->flush();

        // Return the plain token to be sent via email
        return $plainToken;
    }

    /**
     * Régénère un token pour l’utilisateur.
     * Marque toujours l’ancien token comme expiré avant de créer le nouveau.
     * @param ActivationToken $activationToken
     * @return string Le nouveau token en clair (à envoyer si besoin)
     * @throws \Exception
     */
    public function regenerateToken(ActivationToken $oldActivationToken): string
    {
        $oldActivationToken->setExpiredAt(new \DateTimeImmutable());

        $newPlainToken = bin2hex(random_bytes(32));
        $newHashedToken = hash('sha256', data: $newPlainToken);

        $newToken = new ActivationToken();
        $newToken->setAccount($oldActivationToken->getAccount());
        $newToken->setHashedToken($newHashedToken);
        $newToken->setCreatedAt(new \DateTimeImmutable());
        $newToken->setExpiredAt(null);

        $this->entityManager->persist($newToken);
        $this->entityManager->flush();

        return $newPlainToken;
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


        if (!$token || !$token->isValid()) {
            $plainToken = $this->generateToken($user); // retourne le token en clair
            $hashedToken = hash('sha256', $plainToken); // hash pour chercher en base
            $token = $this->entityManager->getRepository(ActivationToken::class)
                ->findOneBy(['hashedToken' => $hashedToken]); // récupère l'objet ActivationToken
        }

        return $token;
    }

    /**
     * Activates a user account based on the provided token.
     * Returns an array with status and the token entity if applicable.
     * @param string $plainToken The activation token in plain text.
     * @return array An associative array with 'status' (success, expired, invalid)
     */
    public function activateAccount(string $plainToken): array
    {
        $plainToken = trim($plainToken);

        // Hash the provided token to match the stored format
        $hashedToken = hash('sha256', $plainToken);
        
        $activationToken = $this->entityManager->getRepository(ActivationToken::class)
            ->findOneBy(['hashedToken' => $hashedToken]);
            
        if (!$activationToken) {
            return ['status' => 'invalid', 'token' => null];
        }

        if ($activationToken->isExpired()) {
            return ['status' => 'expired', 'token' => $activationToken];
        }

        $user = $activationToken->getAccount();
        
        // Activer le compte utilisateur
        $user->setIsActivated(true);

        // Remove all tokens associated with the user
        $tokens = $this->entityManager->getRepository(ActivationToken::class)
        ->findBy(['account' => $user]);

        foreach ($tokens as $token) {
            $this->entityManager->remove($token);
        }

        $this->entityManager->flush();
        return ['status' => 'success', 'token' => $activationToken];
    }

    /**
    * Refreshes activation tokens for users who haven't activated their account after 1 hour.
    * Updates the existing token in place, setting a new creation date and hashed token.
    */
    public function refreshExpiredTokens(): array{
        // Récupérer tous les tokens sans date d'expiration et créés il y a plus d'1h
        $connection = $this->entityManager->getConnection();

        $sql = "
            SELECT id
            FROM activation_token
            WHERE expired_at IS NULL
            AND created_at < NOW() - INTERVAL '5 minutes'
            ORDER BY created_at DESC
            LIMIT 50
        ";

        $ids = $connection->executeQuery($sql)->fetchFirstColumn(); // récupère juste la colonne `id`
        $tokens = !empty($ids) ? $this->entityManager
            ->getRepository(ActivationToken::class)
            ->findBy(['id' => $ids]) : [];

        $updatedIds = [];

        // Pour chaque token, vérifier si l'utilisateur est activé
        // Si non, régénérer le token dans le même enregistrement
        foreach ($tokens as $token) {
            $user = $token->getAccount();

            // Si l'utilisateur n'est pas activé
            if (!$user->isActivated()) {

                // Générer un nouveau token dans le même enregistrement
                $this->regenerateToken(oldActivationToken: $token);

               $updatedIds[] = $token->getId();
            }
        }
        $this->entityManager->flush();
        return $updatedIds;
    }
}
