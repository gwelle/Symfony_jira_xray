<?php

namespace App\Service;


use App\Entity\User;
use App\Entity\ActivationToken;

use Doctrine\ORM\EntityManagerInterface;

use App\Interfaces\AccountActivationInterface;
use App\Interfaces\ActivationTokenProviderInterface;
use App\Interfaces\LastActivationTokenProviderInterface;
use App\Interfaces\RateLimiterInterface;

use App\Dto\ActivationResult;

use App\Enums\ActivationStatus;

use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AccountActivationService implements AccountActivationInterface
{
    private EntityManagerInterface $entityManager;
    private RateLimiterFactory $tokenExpiredLimiter;
    private RateLimiterInterface $rateLimiterServivce;
    private ActivationTokenProviderInterface $activationTokenProvider;
    private LastActivationTokenProviderInterface $lastActivationTokenProvider;

    /**
     * Constructor for ActivationService.
     * @param EntityManagerInterface $entityManager The entity manager for database operations.
     * @param RateLimiterFactory $tokenExpiredLimiter The rate limiter for expired token requests.
     * @param ActivationTokenProviderInterface $activationTokenProvider The activation token provider for token-related operations.
     * @param LastActivationTokenProviderInterface $lastActivationTokenProvider The last activation token provider
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        #[Autowire(service: 'limiter.token_expired_limiter')]
        RateLimiterFactory $tokenExpiredLimiter, 
        RateLimiterInterface $rateLimiterService,
        ActivationTokenProviderInterface $activationTokenProvider,
        LastActivationTokenProviderInterface $lastActivationTokenProvider,
        ) 
    {
        $this->entityManager = $entityManager;
        $this->tokenExpiredLimiter = $tokenExpiredLimiter;
        $this->activationTokenProvider = $activationTokenProvider;
        $this->lastActivationTokenProvider = $lastActivationTokenProvider;
        $this->rateLimiterServivce = $rateLimiterService;
    }

    /**
     * Activates a user account based on the provided token.
     * Returns an array with status and the token entity if applicable.
     * @param string $hashedToken The activation token in hashed form.
     * @return ActivationResult An associative array with 'status' (success, expired, invalid)
     */
    public function activatedAccount(string $hashedToken): ActivationResult
    {
        // Trim any whitespace from the token
        $hashedToken = trim($hashedToken);

       /*dump([
            'hashedToken_received' => $hashedToken,
            'db_token' => $this->activationTokenProvider->findRawByToken($hashedToken)
        ]);
        dd("STOP BEFORE ERROR");*/

        // 1. Chercher le token **tel quel** dans la DB
        $activationToken = $this->activationTokenProvider->findByToken($hashedToken);
        //dump('Activation Token from DB:', $activationToken);
        if (!$activationToken) {
            // 2. Token inexistant → check LastActivationToken (optionnel)
            $lastToken = $this->lastActivationTokenProvider
                ->findLastArchivedToken($hashedToken);

            if ($lastToken && $lastToken->getAccount()->isActivated()) {
                    return new ActivationResult(ActivationStatus::ALREADY_ACTIVATED);
            }

            // Ni token actif, ni archive -> invalid
            return new ActivationResult(ActivationStatus::INVALID);
        }

        // 3. Récupération de l'utilisateur lié
        $user = $activationToken->getAccount();

        // 4. Cas déjà activé → éviter double activation
        if ($user->isActivated()) {
            return new ActivationResult(ActivationStatus::ALREADY_ACTIVATED);
        }

        // 5.  Vérifier si le token est expiré
        if ($activationToken->isExpired()) {

            $rateLimit = $this->rateLimiterServivce
                ->check($this->tokenExpiredLimiter,$user->getEmail());
    
            if ($rateLimit->blocked) {
                return new ActivationResult(
                    ActivationStatus::BLOCKED,
                    $rateLimit->retryAfter
                );
            }

            return new ActivationResult(ActivationStatus::EXPIRED, $activationToken);
        }

        // 6. Activer le compte utilisateur
        $this->markUserAsActivated($user);

        // 7. Récupérer tous les tokens de l'utilisateur lié et archiver le dernier token
        $tokens = $this->activationTokenProvider->finfdAllTokensForUser($user);

        // 8. Rechercher le dernier token de l'utilisateur et l'archiver
        if (!empty($tokens)) {
            $lastToken = end($tokens); // dernier token généré
            $lastToken->setExpiredAt(new \DateTimeImmutable());
            $this->lastActivationTokenProvider->saveLastToken($lastToken);
        }

       // 9. Supprimer tous les tokens actifs restants
       $this->activationTokenProvider->deleteTokensForUser($user);
        
        $this->entityManager->flush();

        return new ActivationResult(ActivationStatus::ACTIVATED);
    }

    /**
     * Archives the last activation token.
     * @param User $user The user to be activated.
     * @return bool True if activation was successful, false otherwise.
     */
    public function markUserAsActivated(User $user): bool{
        if(!$user->isActivated()){
            $user->setIsActivated(true);
            return true;
        }
        return false;
    }

    

}