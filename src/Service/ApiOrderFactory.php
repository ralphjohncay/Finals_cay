<?php

namespace App\Service;

use App\Entity\OrderItem;
use App\Entity\Orders;
use App\Entity\Products;
use App\Entity\Services;
use App\Entity\Users;
use App\Repository\ProductsRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Creates orders from mobile-friendly JSON (same orders/order_items tables as the website).
 */
final class ApiOrderFactory
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProductsRepository $productsRepository,
        private ServiceRepository $serviceRepository,
        private ActivityLogService $activityLogService,
    ) {
    }

    /**
     * @return array{order: Orders}|array{error: string, status: int}
     */
    public function createFromPayload(Users $customer, array $data): array
    {
        $lines = $this->normalizeLines($data);
        if ($lines === []) {
            return ['error' => 'At least one item is required (items or orderItems).', 'status' => 400];
        }

        $order = new Orders();
        $order->setCustomer($customer);
        $order->setStatus(is_string($data['status'] ?? null) ? $data['status'] : 'pending_approval');

        foreach ($lines as $row) {
            if (!is_array($row)) {
                continue;
            }

            $result = $this->addLine($order, $row);
            if (isset($result['error'])) {
                return $result;
            }
        }

        if ($order->getOrderItems()->isEmpty()) {
            return ['error' => 'No valid order items.', 'status' => 400];
        }

        $order->recalculateTotal();
        $this->em->persist($order);
        $this->em->flush();

        $this->activityLogService->logCreate(
            $customer,
            'Order',
            (int) $order->getId(),
            sprintf('Mobile app order #%d — ₱%s', $order->getId(), $order->getTotalPrice() ?? '0.00'),
        );

        return ['order' => $order];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeLines(array $data): array
    {
        if (isset($data['items']) && is_array($data['items'])) {
            $normalized = [];
            foreach ($data['items'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $productId = $row['productId'] ?? $row['product_id'] ?? null;
                if ($productId !== null) {
                    $normalized[] = [
                        'type' => 'product',
                        'product' => (int) $productId,
                        'quantity' => (int) ($row['quantity'] ?? 1),
                    ];
                    continue;
                }
                $serviceId = $row['serviceId'] ?? $row['service_id'] ?? null;
                if ($serviceId !== null) {
                    $normalized[] = [
                        'type' => 'service',
                        'service' => (int) $serviceId,
                        'quantity' => (int) ($row['quantity'] ?? 1),
                    ];
                }
            }

            return $normalized;
        }

        if (isset($data['orderItems']) && is_array($data['orderItems'])) {
            return $data['orderItems'];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $row
     * @return array{error: string, status: int}|null
     */
    private function addLine(Orders $order, array $row): ?array
    {
        $quantity = (int) ($row['quantity'] ?? 1);
        if ($quantity < 1) {
            return ['error' => 'Each item needs quantity >= 1.', 'status' => 400];
        }

        $type = (string) ($row['type'] ?? 'product');
        $item = new OrderItem();
        $item->setQuantity($quantity);

        if ($type === 'service') {
            $service = $this->resolveService($row['service'] ?? $row['serviceId'] ?? null);
            if (!$service) {
                return ['error' => 'Service not found.', 'status' => 400];
            }
            $item->setService($service);
            $item->setName($service->getName() ?? 'Service');
            $item->setPrice($service->getPrice() ?? '0');
            $item->setType('service');
        } else {
            $product = $this->resolveProduct($row['product'] ?? $row['productId'] ?? null);
            if (!$product) {
                return ['error' => 'Product not found.', 'status' => 400];
            }
            if (!$product->isActive()) {
                return ['error' => sprintf('Product "%s" is no longer available.', $product->getName()), 'status' => 400];
            }
            $item->setProduct($product);
            $item->setName($product->getName() ?? 'Product');
            $item->setPrice($product->getPrice() ?? '0');
            $item->setType('product');
        }

        if (isset($row['name']) && is_string($row['name']) && trim($row['name']) !== '') {
            $item->setName(trim($row['name']));
        }
        if (isset($row['price'])) {
            $item->setPrice((string) $row['price']);
        }

        $order->addOrderItem($item);

        return null;
    }

    private function resolveProduct(mixed $reference): ?Products
    {
        $id = $this->extractId($reference);

        return $id ? $this->productsRepository->find($id) : null;
    }

    private function resolveService(mixed $reference): ?Services
    {
        $id = $this->extractId($reference);

        return $id ? $this->serviceRepository->find($id) : null;
    }

    private function extractId(mixed $reference): ?int
    {
        if (is_int($reference)) {
            return $reference;
        }
        if (is_string($reference) && preg_match('#/(\d+)$#', $reference, $m)) {
            return (int) $m[1];
        }
        if (is_string($reference) && ctype_digit($reference)) {
            return (int) $reference;
        }

        return null;
    }
}
