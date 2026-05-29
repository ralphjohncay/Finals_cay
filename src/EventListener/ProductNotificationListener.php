<?php

namespace App\EventListener;

use App\Entity\Products;
use App\Service\CustomerNotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
final class ProductNotificationListener
{
    public function __construct(
        private CustomerNotificationService $customerNotifications,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Products) {
            $this->customerNotifications->notifyProductCreated($entity);
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Products) {
            return;
        }

        $changeSet = $this->getChangeSet($args, $entity);
        if ($changeSet === []) {
            return;
        }

        if (isset($changeSet['isActive'])) {
            [$wasActive, $isActive] = $changeSet['isActive'];
            if ($wasActive && !$isActive) {
                $this->customerNotifications->notifyProductRemoved($entity, deactivated: true);

                return;
            }
            if (!$wasActive && $isActive) {
                $this->customerNotifications->notifyProductCreated($entity);

                return;
            }
        }

        $this->customerNotifications->notifyProductUpdated($entity);
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Products && $entity->getId() !== null) {
            $this->customerNotifications->notifyProductDeleted(
                (int) $entity->getId(),
                (string) ($entity->getName() ?? 'Product'),
            );
        }
    }

    /**
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    private function getChangeSet(PostUpdateEventArgs $args, Products $product): array
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($product);

        return is_array($changeSet) ? $changeSet : [];
    }
}
