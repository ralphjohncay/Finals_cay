<?php

namespace App\Controller;

use App\Entity\Users;
use App\Service\ApiOrderFactory;
use App\Service\ApiOrderSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Legacy checkout path — delegates to the same order factory as POST /api/orders.
 */
#[Route('/api')]
class ApiCheckoutController extends AbstractController
{
    public function __construct(
        private ApiOrderFactory $orderFactory,
        private ApiOrderSerializer $orderSerializer,
    ) {
    }

    #[Route('/orders/checkout', name: 'api_orders_checkout', methods: ['POST'], priority: 100)]
    public function checkout(Request $request, #[CurrentUser] ?Users $user): JsonResponse
    {
        if (null === $user) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['success' => false, 'message' => 'Invalid JSON body'], 400);
        }

        $result = $this->orderFactory->createFromPayload($user, $data);
        if (isset($result['error'])) {
            return $this->json(['success' => false, 'message' => $result['error']], $result['status']);
        }

        $order = $result['order'];

        return $this->json([
            'success' => true,
            'message' => 'Order placed successfully.',
            'id' => $order->getId(),
            'status' => $order->getStatus(),
            'totalPrice' => $order->getTotalPrice(),
            'orderDate' => $order->getOrderDate()?->format(\DateTimeInterface::ATOM),
            'order' => $this->orderSerializer->serializeOrder($order),
        ], 201);
    }
}
