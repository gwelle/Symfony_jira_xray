<?php

namespace App\Repository;

use App\Entity\LastActivationToken;
use App\Entity\ActivationToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Interfaces\LastActivationTokenProviderInterface;

/**
 * @extends ServiceEntityRepository<LastActivationToken>
 */
class LastActivationTokenRepository extends ServiceEntityRepository implements LastActivationTokenProviderInterface
{
    /**
     * Constructor for LastActivationTokenRepository.
     *
     * @param ManagerRegistry $registry The manager registry for database operations.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LastActivationToken::class);
    }

    /**
     * Saves the given activation token as the last archived token.
     *
     * @param ActivationToken $oldActivationToken The activation token to be archived.
     * @return void
     */
    public function saveLastToken(ActivationToken $oldActivationToken): void
    {
        $archived = new LastActivationToken();
        $archived->setAccount($oldActivationToken->getAccount());
        $archived->setHashedToken($oldActivationToken->getHashedToken());
        $archived->setCreatedAt($oldActivationToken->getCreatedAt());
        $archived->setExpiredAt($oldActivationToken->getExpiredAt() ?? new \DateTimeImmutable());

        $this->getEntityManager()->persist($archived);
    }

    /**
     * Finds the last archived activation token for the given user.
     *
     * @param string $hashedToken The hashed token to search for.
     * @return LastActivationToken|null The last archived activation token or null if none found.
     */
    public function findLastArchivedToken(string $hashedToken): ?LastActivationToken
    {
        return $this->createQueryBuilder('t')
        ->andWhere('t.hashedToken = :token')
        ->setParameter('token', $hashedToken)
        ->getQuery()
        ->getOneOrNullResult();
    }

    
}
