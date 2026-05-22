<?php

namespace App\Doctrine;

use App\Entity\Orders;
use App\Entity\Users;
use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Customers only see their own orders in the API; staff/admin see all (same DB as the admin panel).
 */
final class CurrentUserOrdersExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private Security $security)
    {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if ($resourceClass !== Orders::class) {
            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_STAFF')) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof Users) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere(sprintf('%s.customer = :currentCustomer', $rootAlias))
            ->setParameter('currentCustomer', $user);
    }
}
