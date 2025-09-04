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
    public function generateToken(User $user): ActivationToken
    {
        // Generate a new plain token
        $plainToken = bin2hex(random_bytes(32));

        $activationToken = new ActivationToken();
        $activationToken->setAccount($user);
        $activationToken->setHashedToken($plainToken);
        $activationToken->setCreatedAt(new \DateTimeImmutable());
        $activationToken->setExpiredAt((new \DateTimeImmutable())->modify('+24 hours'));

        $this->entityManager->persist($activationToken);
        $this->entityManager->flush();

        return $activationToken;
    }
}
