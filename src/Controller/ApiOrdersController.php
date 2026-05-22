<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Entity\Users;
use App\Repository\OrderRepository;
use App\Service\ApiOrderFactory;
use App\Service\ApiOrderSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
class ApiOrdersController extends AbstractController
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ApiOrderFactory $orderFactory,
        private ApiOrderSerializer $orderSerializer,
    ) {
    }

    /** Orders for the logged-in user (mobile app). */
    #[Route('/my-orders', name: 'api_my_orders', methods: ['GET'], priority: 100)]
    #[Route('/orders/mine', name: 'api_orders_mine', methods: ['GET'], priority: 100)]
    public function mine(#[CurrentUser] ?Users $user): JsonResponse
    {
        if (null === $user) {
            return $this->apiError('Unauthorized', 401);
        }

        $orders = $this->orderRepository->findBy(
            ['customer' => $user],
            ['orderDate' => 'DESC'],
        );

        return $this->json([
            'success' => true,
            'orders' => array_map(
                fn (Orders $order) => $this->orderSerializer->serializeOrder($order),
                $orders,
            ),
        ]);
    }

    /** Orders for a user id (self or admin/staff only). */
    #[Route('/orders/user/{id}', name: 'api_orders_user', methods: ['GET'], requirements: ['id' => '\d+'], priority: 100)]
    public function forUser(int $id, #[CurrentUser] ?Users $user): JsonResponse
    {
        if (null === $user) {
            return $this->apiError('Unauthorized', 401);
        }

        if ($user->getId() !== $id && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            return $this->apiError('Forbidden', 403);
        }

        $target = $this->orderRepository->getEntityManager()->getRepository(Users::class)->find($id);
        if (!$target instanceof Users) {
            return $this->apiError('User not found', 404);
        }

        $orders = $this->orderRepository->findBy(
            ['customer' => $target],
            ['orderDate' => 'DESC'],
        );

        return $this->json([
            'success' => true,
            'orders' => array_map(
                fn (Orders $order) => $this->orderSerializer->serializeOrder($order),
                $orders,
            ),
        ]);
    }

    #[Route('/orders/{id}', name: 'api_orders_show', methods: ['GET'], requirements: ['id' => '\d+'], priority: 100)]
    public function show(int $id, #[CurrentUser] ?Users $user): JsonResponse
    {
        if (null === $user) {
            return $this->apiError('Unauthorized', 401);
        }

        $order = $this->orderRepository->find($id);
        if (!$order instanceof Orders) {
            return $this->apiError('Order not found', 404);
        }

        if (
            $order->getCustomer()?->getId() !== $user->getId()
            && !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_STAFF')
        ) {
            return $this->apiError('Forbidden', 403);
        }

        return $this->json([
            'success' => true,
            'order' => $this->orderSerializer->serializeOrder($order),
        ]);
    }

    /**
     * Mobile-friendly order creation (JSON body, no API Platform IRIs required).
     */
    #[Route('/orders', name: 'api_orders_create', methods: ['POST'], priority: 100)]
    public function create(Request $request, #[CurrentUser] ?Users $user): JsonResponse
    {
        if (null === $user) {
            return $this->apiError('Unauthorized', 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->apiError('Invalid JSON body', 400);
        }

        $result = $this->orderFactory->createFromPayload($user, $data);
        if (isset($result['error'])) {
            return $this->apiError($result['error'], $result['status']);
        }

        $order = $result['order'];

        return $this->json([
            'success' => true,
            'message' => 'Order placed successfully.',
            'order' => $this->orderSerializer->serializeOrder($order),
        ], 201);
    }

    private function apiError(string $message, int $status): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
