<?php

namespace App\Service;

use App\Entity\ActivationToken;
use App\Interfaces\RegenerateTokenInterface;
use Doctrine\ORM\EntityManagerInterface;

class RegenerateTokenService implements RegenerateTokenInterface
{
    /**
     * Constructor for RegenerateTokenService.
     * @param EntityManagerInterface $entityManager The entity manager for database operations.
     */
    public function __construct(private EntityManagerInterface $entityManager) {}

    /**
     * Regenerates an activation token based on an old activation token.
     *
     * @param ActivationToken $oldActivationToken The old activation token to be replaced.
     * @return string The newly generated plain activation token.
     * @throws \Exception If there is an error during token regeneration.
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
}