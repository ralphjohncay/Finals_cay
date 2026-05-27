<?php

namespace App\Repository;

use App\Entity\AppNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppNotification>
 */
class AppNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppNotification::class);
    }

    /**
     * @return AppNotification[]
     */
    public function findActiveForAudiences(array $audiences, \DateTimeImmutable $now): array
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.isActive = :active')
            ->andWhere('n.startsAt <= :now')
            ->andWhere('n.audience IN (:audiences)')
            ->andWhere('n.expiresAt IS NULL OR n.expiresAt > :now')
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->setParameter('audiences', $audiences)
            ->orderBy('n.priority', 'DESC')
            ->addOrderBy('n.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
