<?php

namespace App\Service;

use App\Entity\CustomerNotification;
use App\Entity\Orders;
use App\Entity\Products;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Creates in-app alerts for mobile customers when staff/admin changes orders or products.
 */
final class CustomerNotificationService
{
    private const STATUS_LABELS = [
        'pending_approval' => 'Pending approval',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'completed' => 'Completed',
        'canceled' => 'Canceled',
    ];

    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function notifyOrderApproved(Orders $order): void
    {
        $this->notifyOrder(
            $order,
            'approved',
            'Order approved',
            sprintf('Your order #%d has been approved.', $order->getId()),
            CustomerNotification::TYPE_SUCCESS,
        );
    }

    public function notifyOrderRejected(Orders $order): void
    {
        $this->notifyOrder(
            $order,
            'rejected',
            'Order declined',
            sprintf('Your order #%d was declined by the store.', $order->getId()),
            CustomerNotification::TYPE_DANGER,
        );
    }

    public function notifyOrderUpdated(Orders $order): void
    {
        $label = self::STATUS_LABELS[$order->getStatus() ?? ''] ?? ($order->getStatus() ?? 'updated');
        $this->notifyOrder(
            $order,
            'updated',
            'Order updated',
            sprintf(
                'Order #%d was updated by admin. Status: %s.',
                $order->getId(),
                $label,
            ),
            CustomerNotification::TYPE_WARNING,
        );
    }

    public function notifyOrderDeleted(Orders $order): void
    {
        $this->notifyOrder(
            $order,
            'deleted',
            'Order removed',
            sprintf('Order #%d was removed by admin.', $order->getId()),
            CustomerNotification::TYPE_WARNING,
        );
    }

    public function notifyProductCreated(Products $product): void
    {
        if ($product->getId() === null) {
            return;
        }

        $name = $product->getName() ?? 'Product';
        $this->notifyCatalog(
            'created',
            'New product',
            sprintf('"%s" is now available in the shop.', $name),
            CustomerNotification::TYPE_SUCCESS,
            'Product',
            (int) $product->getId(),
        );
    }

    public function notifyProductUpdated(Products $product, bool $deactivated = false): void
    {
        if ($product->getId() === null) {
            return;
        }

        $name = $product->getName() ?? 'Product';
        if ($deactivated) {
            $message = sprintf('"%s" is no longer available.', $name);
            $type = CustomerNotification::TYPE_WARNING;
        } else {
            $message = sprintf('"%s" was updated (price, stock, or details may have changed).', $name);
            $type = CustomerNotification::TYPE_INFO;
        }

        $this->notifyCatalog(
            $deactivated ? 'deactivated' : 'updated',
            $deactivated ? 'Product unavailable' : 'Product updated',
            $message,
            $type,
            'Product',
            (int) $product->getId(),
        );
    }

    public function notifyProductDeleted(int $productId, string $productName): void
    {
        $this->notifyCatalog(
            'deleted',
            'Product removed',
            sprintf('"%s" was removed from the shop.', $productName),
            CustomerNotification::TYPE_WARNING,
            'Product',
            $productId,
        );
    }

    private function notifyOrder(
        Orders $order,
        string $event,
        string $title,
        string $message,
        string $type,
    ): void {
        $customer = $order->getCustomer();
        if (!$customer instanceof Users) {
            return;
        }

        $this->persist(
            $customer,
            CustomerNotification::CATEGORY_ORDER,
            $event,
            $title,
            $message,
            $type,
            'Order',
            $order->getId(),
        );
    }

    private function notifyCatalog(
        string $event,
        string $title,
        string $message,
        string $type,
        string $entityType,
        int $entityId,
    ): void {
        $this->persist(
            null,
            CustomerNotification::CATEGORY_PRODUCT,
            $event,
            $title,
            $message,
            $type,
            $entityType,
            $entityId,
        );
    }

    private function persist(
        ?Users $user,
        string $category,
        string $event,
        string $title,
        string $message,
        string $type,
        ?string $entityType,
        ?int $entityId,
    ): void {
        $notification = new CustomerNotification();
        $notification->setUser($user);
        $notification->setCategory($category);
        $notification->setEvent($event);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setType($type);
        $notification->setEntityType($entityType);
        $notification->setEntityId($entityId);

        try {
            $this->em->persist($notification);
            $this->em->flush();
        } catch (\Throwable) {
            // Never block admin actions if alerts fail (e.g. migration pending).
        }
    }
}
