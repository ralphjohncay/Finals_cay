<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductsRepository;
use App\Repository\ServiceRepository;
use App\Repository\UsersRepository;
use App\Service\AdminAlertService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * HTML fragments for admin/staff pages — polled by admin-live.js (no full page reload).
 */
final class AdminLiveController extends AbstractController
{
    #[Route('/order/live/rows', name: 'app_order_live_rows', methods: ['GET'])]
    #[IsGranted('ROLE_STAFF')]
    public function orderRows(OrderRepository $orderRepository, Request $request): Response
    {
        $sortOrder = $request->query->get('sort', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'desc';
        }

        $orders = $orderRepository->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->addSelect('c')
            ->orderBy('o.orderDate', $sortOrder)
            ->addOrderBy('o.id', $sortOrder)
            ->getQuery()
            ->getResult();

        $response = $this->render('order/_index_rows.html.twig', [
            'orders' => $orders,
        ]);
        $response->headers->set('X-Admin-Live', $this->ordersFingerprint($orders));
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $response;
    }

    #[Route('/admin/live/dashboard', name: 'admin_live_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function dashboard(
        UsersRepository $usersRepo,
        OrderRepository $orderRepo,
        ProductsRepository $productsRepo,
        ServiceRepository $servicesRepo,
        ActivityLogRepository $logRepo,
    ): Response {
        $totalUsers = (int) $usersRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        $totalStaff = (int) $usersRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :staff')
            ->andWhere('u.roles NOT LIKE :admin')
            ->andWhere('u.isActive = :active')
            ->setParameter('staff', '%ROLE_STAFF%')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        $totalOrders = $orderRepo->count([]);
        $totalProducts = $productsRepo->count([]);
        $totalServices = $servicesRepo->count([]);
        $recentActivities = $logRepo->findWithFilters(null, null, null, null, 10);

        $response = $this->render('admin/_dashboard_live.html.twig', [
            'total_users' => $totalUsers,
            'total_staff' => $totalStaff,
            'total_orders' => $totalOrders,
            'total_products' => $totalProducts,
            'total_services' => $totalServices,
            'recent_activities' => $recentActivities,
        ]);
        $response->headers->set('X-Admin-Live', sprintf(
            'dashboard-%d-%d-%d',
            $totalOrders,
            count($recentActivities),
            $recentActivities[0]?->getId() ?? 0,
        ));
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $response;
    }

    #[Route('/admin/live/alerts', name: 'admin_live_alerts', methods: ['GET'])]
    #[IsGranted('ROLE_STAFF')]
    public function alerts(Request $request, AdminAlertService $alertService): JsonResponse
    {
        if (!$request->query->has('since')) {
            return $this->json([
                'cursor' => $alertService->getLatestLogId(),
                'alerts' => [],
            ]);
        }

        $since = max(0, (int) $request->query->get('since'));
        $alerts = $alertService->getAlertsSince($since);
        $cursor = $since;
        foreach ($alerts as $alert) {
            $cursor = max($cursor, $alert['id']);
        }
        $cursor = max($cursor, $alertService->getLatestLogId());

        $response = $this->json([
            'cursor' => $cursor,
            'alerts' => $alerts,
        ]);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $response;
    }

    #[Route('/admin/live/activities', name: 'admin_live_activities', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function activities(ActivityLogRepository $logRepo, Request $request): Response
    {
        $userParam = $request->query->get('user');
        $userId = !empty($userParam) && is_numeric($userParam) ? (int) $userParam : null;
        $action = $request->query->get('action');
        $startDate = $request->query->get('start_date') ? new \DateTime($request->query->get('start_date')) : null;
        $endDate = $request->query->get('end_date') ? new \DateTime($request->query->get('end_date')) : null;

        $activities = $logRepo->findWithFilters($userId, $action ?: null, $startDate, $endDate, 200);

        $response = $this->render('admin/_activities_rows.html.twig', [
            'recent_activities' => $activities,
        ]);
        $response->headers->set('X-Admin-Live', 'activities-' . count($activities) . '-' . ($activities[0]?->getId() ?? 0));
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $response;
    }

    /**
     * @param list<\App\Entity\Orders> $orders
     */
    private function ordersFingerprint(array $orders): string
    {
        if ($orders === []) {
            return 'orders-0';
        }

        $parts = [];
        foreach ($orders as $order) {
            $parts[] = sprintf(
                '%d:%s',
                $order->getId(),
                $order->getStatus() ?? '',
            );
        }

        return 'orders-' . count($orders) . '-' . md5(implode('|', $parts));
    }
}
