<?php

namespace App\Service;

use App\Entity\OrderItem;
use App\Entity\Orders;

final class ApiOrderSerializer
{
    public function serializeOrder(Orders $order): array
    {
        $items = [];
        foreach ($order->getOrderItems() as $item) {
            $items[] = $this->serializeItem($item);
        }

        return [
            'id' => $order->getId(),
            'status' => $order->getStatus(),
            'total' => (float) $order->getTotalPrice(),
            'totalPrice' => $order->getTotalPrice(),
            'createdAt' => $order->getOrderDate()?->format(\DateTimeInterface::ATOM),
            'orderDate' => $order->getOrderDate()?->format(\DateTimeInterface::ATOM),
            'customerId' => $order->getCustomer()?->getId(),
            'items' => $items,
            'orderItems' => $items,
        ];
    }

    public function serializeItem(OrderItem $item): array
    {
        return [
            'id' => $item->getId(),
            'productId' => $item->getProduct()?->getId(),
            'productName' => $item->getName(),
            'name' => $item->getName(),
            'quantity' => $item->getQuantity(),
            'price' => (float) $item->getPrice(),
            'type' => $item->getType(),
        ];
    }
}
