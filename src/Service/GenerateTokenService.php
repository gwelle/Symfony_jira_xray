<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\ActivationToken;
use App\Interfaces\GenerateTokenInterface;

class GenerateTokenService implements GenerateTokenInterface
{
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

            return $activationToken->getHashedToken();
        } 
        catch (\Exception $e) {
            throw new \Exception('Error generating token: ' . $e->getMessage());
        }
    }
}