<?php

namespace App\Repository;

use App\Entity\ActivationToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Interfaces\ExpiredActivationTokenInterface;
use App\Interfaces\ActivationTokenProviderInterface;
use App\Interfaces\ActiveActivationTokenInterface;

/**
 * @extends ServiceEntityRepository<ActivationToken>
 */
class ActivationTokenRepository extends ServiceEntityRepository implements ActivationTokenProviderInterface, ExpiredActivationTokenInterface, ActiveActivationTokenInterface
{
    /**
     * Constructor for ActivationTokenRepository.
     * @param ManagerRegistry $registry The manager registry for database operations.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivationToken::class);
    }

    /**
     * Finds an activation token entity by its hashed token value.
     * @param string $hashedToken The hashed token to search for.
     * @return ActivationToken|null The ActivationToken entity if found, null otherwise.
     */
    public function findByToken(string $hashedToken): ?ActivationToken
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.hashedToken = :token')
            ->setParameter('token', $hashedToken)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds all activation tokens associated with a given user.
     * @param User $user The user whose tokens are to be retrieved.
     * @return ActivationToken[] An array of ActivationToken entities.
     */
    public function finfdAllTokensForUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.account = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Deletes all activation tokens associated with a given user.
     * @param User $user The user whose tokens are to be deleted.
     * @return void
     */
    public function deleteTokensForUser(User $user): void
    {
        $tokens = $this->findBy(['account' => $user]);

        foreach ($tokens as $token) {
            $this->getEntityManager()->remove($token);
        }
    }

    /**
     * Finds recent valid activation tokens created within the specified interval.
     * @param \DateInterval|null $interval The time interval to look back from now. Defaults to 1 hour if null.
     * @param int $limit The maximum number of tokens to retrieve.
     * @return ActivationToken[] An array of recent valid ActivationToken entities.
     */
    public function findExpiredTokens(?\DateInterval $interval = null, int $limit = 50): array
    {
        try{
            $interval ??= new \DateInterval('PT1H'); // défaut : 1 heure
            $since = (new \DateTimeImmutable())->sub($interval);

            $qb = $this->createQueryBuilder('t')
                ->join('t.account', 'u')      // join pour éviter N+1
                ->addSelect( 'u')
                ->andWhere('t.expiredAt IS NULL')
                ->andWhere('t.createdAt >= :since')
                ->setParameter('since', $since)
                ->orderBy('t.createdAt', 'DESC')
                ->setMaxResults($limit);

            return $qb->getQuery()->getResult();
        }
        catch (\Exception $e) {
            throw new \RuntimeException('Impossible de récupérer les tokens récents', 0, $e);
        }
    }

    /**
     * Retrieves the current active (non-expired) activation token for a given user.
     * @param User $user The user whose active token is to be retrieved.
     * @return string|null The hashed token if found, null otherwise.
     */
    public function currentActiveTokenForUser(User $user): ?string
    {
        $qb = $this->createQueryBuilder('t')
        ->select('t.hashedToken')
        ->where('t.account = :user')
        ->andWhere('t.expiredAt IS NULL')
        ->setParameter('user', $user)
        ->orderBy('t.createdAt', 'DESC')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();

        return $qb ? $qb['hashedToken'] : null;
    }

}