<?php

namespace App\Repository;

use App\Entity\CustomerNotification;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerNotification>
 */
class CustomerNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerNotification::class);
    }

    /**
     * @return list<CustomerNotification>
     */
    public function findForUserSince(Users $user, int $sinceId, int $limit = 50): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.id > :since')
            ->andWhere('n.user IS NULL OR n.user = :user')
            ->setParameter('since', $sinceId)
            ->setParameter('user', $user)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getMaxIdForUser(Users $user): int
    {
        $value = $this->createQueryBuilder('n')
            ->select('MAX(n.id)')
            ->where('n.user IS NULL OR n.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $value !== null ? (int) $value : 0;
    }
}
