<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\ProductsRepository;
use App\Repository\CategoryRepository;
use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff')]
#[IsGranted('ROLE_STAFF')]
class StaffController extends AbstractController
{
    #[Route('/dashboard', name: 'staff_dashboard')]
    public function dashboard(
        OrderRepository $orderRepo,
        ProductsRepository $productsRepo,
        CategoryRepository $categoryRepo
    ): Response {
        $user = $this->getUser();
        
        if (!$user instanceof Users) {
            return $this->redirectToRoute('login');
        }

        // Staff's own orders
        $myOrders = $orderRepo->findBy(['customer' => $user], ['orderDate' => 'DESC']);
        $myTotalOrders = count($myOrders);
        
        // Orders by status for current staff
        $myPendingApprovalOrders = $orderRepo->count(['customer' => $user, 'status' => 'pending_approval']);

        return $this->render('staff/dashboard.html.twig', [
            'user' => $user,
            'my_total_orders' => $myTotalOrders,
            'my_pending_approval_orders' => $myPendingApprovalOrders,
        ]);
    }
}

