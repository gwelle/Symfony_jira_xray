<?php
namespace App\Service;
use App\Entity\ActivationToken;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\LastActivationToken;
use App\Repository\ActivationTokenRepository;
use App\Response\ActivateStatusResponse;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ActivationService
{
    private EntityManagerInterface $entityManager;
    private RateLimiterFactory $tokenExpiredLimiter;
    private UserService $userService;
    private ActivationTokenRepository $activationTokenRepository;

    /**
     * Constructor for ActivationService.
     * @param EntityManagerInterface $entityManager The entity manager for database operations.
     * @param RateLimiterFactory $tokenExpiredLimiter The rate limiter for expired token requests.
     * @param UserService $userService The user service for user-related operations.
     * @param ActivationTokenRepository $activationTokenRepository The activation token repository for token-related operations.
     */
    public function __construct(EntityManagerInterface $entityManager,
     #[Autowire(service: 'limiter.token_expired_limiter')]
     RateLimiterFactory $tokenExpiredLimiter, UserService $userService, 
     ActivationTokenRepository $activationTokenRepository) 
    {
        $this->entityManager = $entityManager;
        $this->tokenExpiredLimiter = $tokenExpiredLimiter;
        $this->userService = $userService;
        $this->activationTokenRepository = $activationTokenRepository;
    }

    /**
     * Generates a new activation token for the given user.
     *
     * @param User $user The user for whom the activation token is to be generated.
     * @return string The generated plain activation token.
     * @throws \Exception If there is an error during token generation.
     */
    public function generateToken(User $user): string
    {
        try{
            // Generate a new plain token
            $plainToken = bin2hex(random_bytes(32));

            $activationToken = new ActivationToken();
            $activationToken->setAccount($user);
            // store the plain token in the entity for email sending into UserEmailProcessor
            $activationToken->setPlainToken($plainToken);
            $activationToken->setHashedToken(hash('sha256', $plainToken));
            $activationToken->setCreatedAt(new \DateTimeImmutable());
            $activationToken->setExpiredAt(null);
            $user->addActivationToken($activationToken);

            // Return the plain token to be sent via email 
            return $plainToken; 
        } 
        catch (\Exception $e) {
            throw new \Exception('Error generating token: ' . $e->getMessage());
        }
    }

    /**
     * Régénère un token pour l’utilisateur.
     * Marque toujours l’ancien token comme expiré avant de créer le nouveau.
     * @param ActivationToken $oldActivationToken Le token d’activation à régénérer.
     * @return string Le nouveau token en clair (à envoyer si besoin)
     * @throws \Exception En cas d’erreur lors de la régénération du token.
     */
    public function regenerateToken(ActivationToken $oldActivationToken): string
    {
        try{
            $oldActivationToken->setExpiredAt(new \DateTimeImmutable());

            $newPlainToken = bin2hex(random_bytes(32));
            $newHashedToken = hash('sha256',  $newPlainToken);

            $newToken = new ActivationToken();
            $newToken->setAccount($oldActivationToken->getAccount());
            $newToken->setHashedToken($newHashedToken);
            $newToken->setCreatedAt(new \DateTimeImmutable());
            $newToken->setExpiredAt(null);
            $oldActivationToken->getAccount()->addActivationToken($newToken);

            $this->entityManager->persist($newToken);
            $this->entityManager->flush();

            return $newPlainToken;
        } 
        catch (\Exception $e) {
            throw new \Exception('Error regenerating token: ' . $e->getMessage());
        }
    }

    /**
     * Retrieves a valid activation token for the given user.
     * @param User $user The user for whom to retrieve the activation token.
     * @return string|null A valid activation token entity.
     */
    public function getValidTokenForUser(User $user): ?string
    {
       return $this->activationTokenRepository->currentActiveTokenCountForUser($user);
    }

    /**
     * Activates a user account based on the provided token.
     * Returns an array with status and the token entity if applicable.
     * @param string $hashedToken The activation token in hashed form.
     * @return ActivateStatusResponse An associative array with 'status' (success, expired, invalid)
     */
    public function activateAccount(string $hashedToken): ActivateStatusResponse
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
                    return new ActivateStatusResponse('already_activated');
                }
            }

            // Ni token actif, ni archive -> invalid
            return new ActivateStatusResponse('invalid');
        }

        // 3. Récupération de l'utilisateur lié
        $user = $activationToken->getAccount();

        // 4. Cas déjà activé → éviter double activation
        if ($user->isActivated()) {
            return new ActivateStatusResponse('already_activated');
        }

        // 5.  Vérifier si le token est expiré
        if ($activationToken->isExpired()) {

            // create a rate limiter for expired token requests
            $limiter = $this->tokenExpiredLimiter->create($user->getEmail());

            // tente de consommer une crédit
            $limit = $limiter->consume(1);

            // check if user has already requested 3 times a new token
            if (false === $limit->isAccepted()) {
                return new ActivateStatusResponse('blocked');
            }
            return new ActivateStatusResponse('expired', $activationToken);
        }

        // 6. Activer le compte utilisateur
        $this->userService->activate($user);

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

        return new ActivateStatusResponse('success');
    }

    /**
     * Refreshes expired activation tokens for users who haven't activated their accounts.
     * @param \DateInterval|null $interval The time interval to look back from now. Defaults to 1 hour if null.
     * @param int $limit The maximum number of tokens to process.
     * @return void
     */
    public function refreshExpiredTokens(?\DateInterval $interval = null, int $limit = 50): void{
        $tokens = $this->activationTokenRepository
            ->findRecentValidToken($interval, $limit);

        // Pour chaque token, vérifier si l'utilisateur est activé
        // Si non, régénérer le token dans le même enregistrement
        foreach ($tokens as $token) {
            $user = $token->getAccount();

            // Si l'utilisateur n'est pas activé
            if (!$user->isActivated()) {

                // Générer un nouveau token
                $this->regenerateToken(oldActivationToken: $token);
                
            }
        }
        $this->entityManager->flush();

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
