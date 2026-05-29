<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductsRepository;
use App\Repository\ServiceRepository;
use App\Repository\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
            ->orderBy('o.orderDate', $sortOrder)
            ->addOrderBy('o.id', $sortOrder)
            ->getQuery()
            ->getResult();

        $response = $this->render('order/_index_rows.html.twig', [
            'orders' => $orders,
        ]);
        $response->headers->set('X-Admin-Live', 'orders-' . count($orders));

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
        $response->headers->set('X-Admin-Live', 'dashboard-' . $totalOrders . '-' . count($recentActivities));

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
        $response->headers->set('X-Admin-Live', 'activities-' . count($activities));

        return $response;
    }
}
