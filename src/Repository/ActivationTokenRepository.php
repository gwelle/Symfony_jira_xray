<?php

namespace App\Repository;

use App\Entity\ActivationToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivationToken>
 */
class ActivationTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivationToken::class);
    }

    /**
     * Retrieves the current active (non-expired) activation token for a given user.
     * @param User $user The user whose active token is to be retrieved.
     * @return string|null The hashed token if found, null otherwise.
     */
    public function currentActiveTokenCountForUser(User $user): ?string
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

    /**
     * Finds recent valid activation tokens created within the specified interval.
     * @param \DateInterval|null $interval The time interval to look back from now. Defaults to 1 hour if null.
     * @param int $limit The maximum number of tokens to retrieve.
     * @return ActivationToken[] An array of recent valid ActivationToken entities.
     */
    public function findRecentValidToken(?\DateInterval $interval = null, int $limit = 50): array
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

    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ActivationToken
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
