<?php
namespace App\Service;
use App\Entity\ActivationToken;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\LastActivationToken;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ActivationService
{
    private EntityManagerInterface $entityManager;
    private RateLimiterFactory $tokenExpiredLimiter;

    /**
     * Constructor for ActivationService.
     * @param EntityManagerInterface $entityManager The entity manager for database operations.
     * @param RateLimiterFactory $tokenExpiredLimiter The rate limiter for expired token requests.
     */
    public function __construct(EntityManagerInterface $entityManager,
     #[Autowire(service: 'limiter.token_expired_limiter')]
     RateLimiterFactory $tokenExpiredLimiter) 
    {
        $this->entityManager = $entityManager;
        $this->tokenExpiredLimiter = $tokenExpiredLimiter;
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
     * @param User $user The user for whom to retrieve the activation token.
     * @return ActivationToken A valid activation token entity.
     */
    public function getValidTokenForUser(User $user): ?string
    {
       $token = $this->entityManager->getRepository(ActivationToken::class)
        ->createQueryBuilder('t')
        ->select('t.hashedToken')
        ->where('t.account = :user')
        ->andWhere('t.expiredAt IS NULL')
        ->setParameter('user', $user)
        ->orderBy('t.createdAt', 'DESC')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();

        return $token ? $token['hashedToken'] : null;
    }

    /**
     * Activates a user account based on the provided token.
     * Returns an array with status and the token entity if applicable.
     * @param string $hashedToken The activation token in hashed form.
     * @return array An associative array with 'status' (success, expired, invalid)
     */
    public function activateAccount(string $hashedToken): array
    {
        // Trim any whitespace from the token
        $hashedToken = trim($hashedToken);

        // 1. Chercher le token **tel quel** dans la DB
        $activationToken = $this->entityManager->getRepository(ActivationToken::class)
            ->findOneBy(['hashedToken' => $hashedToken]);

        if (!$activationToken) {
            // 2. Token inexistant → check LastActivationToken (optionnel)
            $lastToken = $this->entityManager->getRepository(LastActivationToken::class)
                ->findOneBy(['hashedToken' => $hashedToken]);

            if ($lastToken) {
                $user = $lastToken->getAccount();
                if ($user->isActivated()) {
                    return ['status' => 'already_activated', 'token' => null];
                }
            }

            // Ni token actif, ni archive -> invalid
            return ['status' => 'invalid', 'token' => null];
        }

        // 3. Récupération de l'utilisateur lié
        $user = $activationToken->getAccount();

        // 4. Cas déjà activé → éviter double activation
        if ($user->isActivated()) {
            return ['status' => 'already_activated', 'token' => null];
        }

        // 5.  Vérifier si le token est expiré
        if ($activationToken->isExpired()) {

            // create a rate limiter for expired token requests
            $limiter = $this->tokenExpiredLimiter->create($user->getEmail());

            // tente de consommer une crédit
            $limit = $limiter->consume(1);

            // check if user has already requested 3 times a new token
            if (false === $limit->isAccepted()) {
                return [
                    'status' => 'blocked',
                    'token' => null
                ];
            }
            return ['status' => 'expired', 'token' => $activationToken];
        }

        // 6. Activer le compte utilisateur
        $user->setIsActivated(true);

        // 7. Récupérer tous les tokens de l'utilisateur lié et archiver le dernier token
        $tokens = $this->entityManager->getRepository(ActivationToken::class)
        ->findBy(['account' => $user]);

        // Rechercher le dernier token de l'utilisateur et l'archiver
        if (!empty($tokens)) {
            $lastToken = end($tokens); // dernier token généré
            $lastToken->setExpiredAt(new \DateTimeImmutable());
            $this->archiveLastToken($lastToken);
        }

       // 8. Supprimer tous les tokens actifs restants
        foreach ($tokens as $token) {
            $this->entityManager->remove($token);
        }
        $this->entityManager->flush();

        return ['status' => 'success', 'token' => $activationToken];
    }

    /**
    * Refreshes activation tokens for users who haven't activated their account after 24 hours.
    * Updates the existing token in place, setting a new creation date and hashed token.
    */
    public function refreshExpiredTokens(): array{
        // Récupérer tous les tokens sans date d'expiration et créés il y a plus de 24 heures
        $connection = $this->entityManager->getConnection();

        $sql = "
            SELECT id
            FROM activation_token
            WHERE expired_at IS NULL
            AND created_at < NOW() - INTERVAL '24 hours'
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

    /** 
     * Archives the last used or expired token into LastActivationToken entity.
     * @param ActivationToken $lastToken The activation token to archive.
     */
    private function archiveLastToken(ActivationToken $lastToken): void
    {
        $token = new LastActivationToken();
        $token->setAccount($lastToken->getAccount());
        $token->setHashedToken($lastToken->getHashedToken());
        $token->setCreatedAt($lastToken->getCreatedAt());
        $token->setExpiredAt($lastToken->getExpiredAt() ?? new \DateTimeImmutable());

        $this->entityManager->persist($token);
    }
}
