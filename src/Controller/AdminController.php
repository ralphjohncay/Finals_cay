<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductsRepository;
use App\Repository\ServiceRepository;
use App\Repository\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(
        UsersRepository $usersRepo,
        OrderRepository $orderRepo,
        ProductsRepository $productsRepo,
        ServiceRepository $servicesRepo,
        ActivityLogRepository $logRepo
    ): Response {
        // Statistics
        // Count only active users
        $totalUsers = $usersRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
        // Count only staff (users with ROLE_STAFF but NOT ROLE_ADMIN) and are active
        $totalStaff = $usersRepo->createQueryBuilder('u')
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

        // Recent activities (last 10)
        $recentActivities = $logRepo->findWithFilters(null, null, null, null, 10);

        return $this->render('admin/dashboard.html.twig', [
            'total_users' => $totalUsers,
            'total_staff' => $totalStaff,
            'total_orders' => $totalOrders,
            'total_products' => $totalProducts,
            'total_services' => $totalServices,
            'recent_activities' => $recentActivities,
        ]);
    }

    #[Route('/analytics', name: 'admin_analytics')]
    public function analytics(
        OrderRepository $orderRepo,
        ProductsRepository $productsRepo,
        ServiceRepository $servicesRepo
    ): Response {
        // Financial Statistics
        $allOrders = $orderRepo->findAll();
        
        // Total Revenue (all completed orders)
        $totalRevenue = $orderRepo->createQueryBuilder('o')
            ->select('SUM(o.totalPrice)')
            ->where('o.status = :status')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        // Pending Revenue (pending + pending_approval orders)
        $pendingRevenue = $orderRepo->createQueryBuilder('o')
            ->select('SUM(o.totalPrice)')
            ->where('o.status IN (:statuses)')
            ->setParameter('statuses', ['pending', 'pending_approval'])
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        // This Month Revenue
        $startOfMonth = new \DateTime('first day of this month');
        $startOfMonth->setTime(0, 0, 0);
        $thisMonthRevenue = $orderRepo->createQueryBuilder('o')
            ->select('SUM(o.totalPrice)')
            ->where('o.status = :status')
            ->andWhere('o.orderDate >= :startDate')
            ->setParameter('status', 'completed')
            ->setParameter('startDate', $startOfMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        // Last Month Revenue
        $startOfLastMonth = new \DateTime('first day of last month');
        $startOfLastMonth->setTime(0, 0, 0);
        $endOfLastMonth = new \DateTime('last day of last month');
        $endOfLastMonth->setTime(23, 59, 59);
        $lastMonthRevenue = $orderRepo->createQueryBuilder('o')
            ->select('SUM(o.totalPrice)')
            ->where('o.status = :status')
            ->andWhere('o.orderDate >= :startDate')
            ->andWhere('o.orderDate <= :endDate')
            ->setParameter('status', 'completed')
            ->setParameter('startDate', $startOfLastMonth)
            ->setParameter('endDate', $endOfLastMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        // Total Orders
        $totalOrders = $orderRepo->count([]);
        $completedOrders = $orderRepo->count(['status' => 'completed']);
        $pendingOrders = $orderRepo->count(['status' => 'pending']);
        $pendingApprovalOrders = $orderRepo->count(['status' => 'pending_approval']);
        
        // Average Order Value
        $averageOrderValue = $completedOrders > 0 ? (float)$totalRevenue / $completedOrders : 0;
        
        // Today's Revenue
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $todayRevenue = $orderRepo->createQueryBuilder('o')
            ->select('SUM(o.totalPrice)')
            ->where('o.status = :status')
            ->andWhere('o.orderDate >= :today')
            ->setParameter('status', 'completed')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        // This Week Revenue
        $startOfWeek = new \DateTime('monday this week');
        $startOfWeek->setTime(0, 0, 0);
        $thisWeekRevenue = $orderRepo->createQueryBuilder('o')
            ->select('SUM(o.totalPrice)')
            ->where('o.status = :status')
            ->andWhere('o.orderDate >= :startDate')
            ->setParameter('status', 'completed')
            ->setParameter('startDate', $startOfWeek)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        // Revenue Growth (this month vs last month)
        $revenueGrowth = $lastMonthRevenue > 0 
            ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 
            : 0;

        return $this->render('admin/analytics.html.twig', [
            'total_revenue' => $totalRevenue,
            'pending_revenue' => $pendingRevenue,
            'this_month_revenue' => $thisMonthRevenue,
            'last_month_revenue' => $lastMonthRevenue,
            'today_revenue' => $todayRevenue,
            'this_week_revenue' => $thisWeekRevenue,
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'pending_orders' => $pendingOrders,
            'pending_approval_orders' => $pendingApprovalOrders,
            'average_order_value' => $averageOrderValue,
            'revenue_growth' => $revenueGrowth,
        ]);
    }
}

