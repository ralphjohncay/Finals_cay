<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_root')]
    public function root(): Response
    {
        if ($this->getUser()) {
            // Redirect admin users to admin dashboard
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard');
            }
            // Redirect staff users to admin dashboard (limited access)
            if ($this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('staff_dashboard');
            }
            return $this->redirectToRoute('customer_homepage');
        }
        // Show public homepage for non-logged in users
        return $this->redirectToRoute('app_homepage');
    }

    #[Route('/homepage', name: 'app_homepage', methods: ['GET'])]
    public function homepage(): Response
    {
        // If user is logged in, redirect to appropriate dashboard
        if ($this->getUser()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard');
            }
            if ($this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('staff_dashboard');
            }
        }
        
        return $this->render('homepage/index.html.twig');
    }

    #[Route('/home', name: 'app_home')]
    #[IsGranted('ROLE_USER')]
    public function index(OrderRepository $orders): Response
    {
        // Redirect admin users to admin dashboard
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        // Fetch the 5 most recent orders by orderDate (fallback to id if needed)
        $recentOrders = $orders->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->addSelect('c')
            ->orderBy('o.orderDate', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Build recent activity entries from orders
        $recentActivity = [];
        foreach ($recentOrders as $o) {
            $recentActivity[] = [
                'text' => sprintf(
                    'Order #%d %s by %s • $%s',
                    $o->getId(),
                    strtolower((string)$o->getStatus() ?: 'pending'),
                    $o->getCustomer()?->getName() ?? 'Unknown',
                    $o->getTotalPrice() ?? '0.00'
                ),
                'date' => $o->getOrderDate(),
                'status' => strtolower((string)$o->getStatus() ?: 'pending'),
            ];
        }

        return $this->render('home/index.html.twig', [
            'recent_orders' => $recentOrders,
            'recent_activity' => $recentActivity,
        ]);
    }
}
