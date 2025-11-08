<?php

namespace App\Service;

use App\Interfaces\ExpiredActivationTokenInterface;
use App\Interfaces\ExpiredTokenRefreshInterface;
use App\Interfaces\RegenerateTokenInterface;
use Doctrine\ORM\EntityManagerInterface;


class ExpiredTokenRefreshService implements ExpiredTokenRefreshInterface
{

    /**
     * Constructor for RefreshTokenService.
     * @param RegenerateTokenInterface $regenerateTokenGenerator The service to regenerate tokens.
     * @param ExpiredActivationTokenInterface $expiredActivationToken The repository to find expired activation tokens.
     * @param EntityManagerInterface $entityManager The entity manager for database operations.
     */
    public function __construct(
        private RegenerateTokenInterface $regenerateTokenGenerator,
        private ExpiredActivationTokenInterface $expiredActivationToken,
        private EntityManagerInterface $entityManager
    ) {}
    /**
     * Refreshes expired activation tokens for users who haven't activated their accounts.
     * @param \DateInterval|null $interval The time interval to look back from now. Defaults to 1 hour if null.
     * @param int $limit The maximum number of tokens to process.
     * @return void
     */
    public function refreshExpiredTokens(?\DateInterval $interval = null, int $limit = 50): void{
        $tokens = $this->expiredActivationToken->findExpiredTokens($interval, $limit);

        // Pour chaque token, vérifier si l'utilisateur est activé
        // Si non, régénérer le token dans le même enregistrement
        foreach ($tokens as $token) {
            $user = $token->getAccount();

            // Si l'utilisateur n'est pas activé
            if (!$user->isActivated()) {

                // Générer un nouveau token
                $this->regenerateTokenGenerator->regenerateToken(oldActivationToken: $token);
                
            }
        }
        $this->entityManager->flush();
    }
}