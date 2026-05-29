<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    /**
     * Find logs with filters
     */
    public function findWithFilters(?int $userId = null, ?string $action = null, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->addSelect('u')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($userId) {
            $qb->andWhere('a.user = :userId')
                ->setParameter('userId', $userId);
        }

        if ($action) {
            $qb->andWhere('a.action = :action')
                ->setParameter('action', $action);
        }

        if ($startDate) {
            $qb->andWhere('a.createdAt >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('a.createdAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<ActivityLog>
     */
    public function findSinceId(int $sinceId, int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->addSelect('u')
            ->where('a.id > :since')
            ->setParameter('since', $sinceId)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getMaxId(): int
    {
        $value = $this->createQueryBuilder('a')
            ->select('MAX(a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $value !== null ? (int) $value : 0;
    }
}

