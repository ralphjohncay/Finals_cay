<?php

namespace App\EventListener;

use App\Entity\Orders;
use App\Service\CustomerNotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
final class OrderNotificationListener
{
    public function __construct(
        private CustomerNotificationService $customerNotifications,
    ) {
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Orders) {
            return;
        }

        $em = $args->getObjectManager();
        $changeSet = $em->getUnitOfWork()->getEntityChangeSet($entity);
        if (!is_array($changeSet) || !isset($changeSet['status'])) {
            return;
        }

        [$previousStatus, $newStatus] = $changeSet['status'];
        $previousStatus = is_string($previousStatus) ? $previousStatus : null;
        $newStatus = is_string($newStatus) ? $newStatus : (string) $entity->getStatus();

        $this->customerNotifications->notifyOrderStatusChanged(
            $entity,
            $previousStatus,
            $newStatus,
        );
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Orders) {
            $this->customerNotifications->notifyOrderDeleted($entity);
        }
    }
}
