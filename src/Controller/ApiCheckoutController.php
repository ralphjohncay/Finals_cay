<?php

namespace App\Controller;

use App\Entity\OrderItem;
use App\Entity\Orders;
use App\Entity\Products;
use App\Entity\Services;
use App\Entity\Users;
use App\Repository\ProductsRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Creates orders with line items in one request (same DB as admin/website).
 * API Platform POST /api/orders does not accept nested orderItems in JSON-LD.
 */
#[Route('/api')]
class ApiCheckoutController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProductsRepository $productsRepository,
        private ServiceRepository $serviceRepository,
    ) {
    }

    #[Route('/orders/checkout', name: 'api_orders_checkout', methods: ['POST'])]
    public function checkout(Request $request, #[CurrentUser] ?Users $user): JsonResponse
    {
        if (null === $user) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['message' => 'Invalid JSON body'], 400);
        }

        $lines = $data['orderItems'] ?? [];
        if (!is_array($lines) || $lines === []) {
            return $this->json(['message' => 'orderItems are required'], 400);
        }

        $order = new Orders();
        $order->setCustomer($user);
        $order->setStatus(is_string($data['status'] ?? null) ? $data['status'] : 'pending_approval');

        foreach ($lines as $row) {
            if (!is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            $price = (string) ($row['price'] ?? '0');
            $quantity = (int) ($row['quantity'] ?? 1);
            $type = (string) ($row['type'] ?? 'product');

            if ($name === '' || $quantity < 1) {
                return $this->json(['message' => 'Each order item needs name and quantity'], 400);
            }

            $item = new OrderItem();
            $item->setName($name);
            $item->setPrice($price);
            $item->setQuantity($quantity);
            $item->setType($type);

            if ($type === 'service') {
                $service = $this->resolveService($row['service'] ?? null);
                if (!$service) {
                    return $this->json(['message' => 'Invalid service reference'], 400);
                }
                $item->setService($service);
                $item->setName($service->getName() ?? $name);
                $item->setPrice($service->getPrice() ?? $price);
            } else {
                $product = $this->resolveProduct($row['product'] ?? null);
                if (!$product) {
                    return $this->json(['message' => 'Invalid product reference'], 400);
                }
                if (!$product->isActive()) {
                    return $this->json(['message' => sprintf('Product "%s" is no longer available.', $product->getName())], 400);
                }
                $item->setProduct($product);
                $item->setName($product->getName() ?? $name);
                $item->setPrice($product->getPrice() ?? $price);
                $item->setType('product');
            }

            $order->addOrderItem($item);
        }

        $order->recalculateTotal();
        $this->em->persist($order);
        $this->em->flush();

        return $this->json([
            'id' => $order->getId(),
            'status' => $order->getStatus(),
            'totalPrice' => $order->getTotalPrice(),
            'orderDate' => $order->getOrderDate()?->format(\DateTimeInterface::ATOM),
            'customer' => '/api/users/' . $user->getId(),
            'message' => 'Order placed successfully.',
        ], 201);
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

        return null;
    }
}
