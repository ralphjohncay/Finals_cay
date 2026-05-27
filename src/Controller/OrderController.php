<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Entity\OrderItem;
use App\Entity\Products;
use App\Form\OrdersType;
use App\Repository\OrderRepository;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ActivityLogService;
use App\Entity\Users;

#[Route('/order')]
final class OrderController extends AbstractController
{
    #[Route('/', name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository, Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_USER')) {
            throw $this->createAccessDeniedException();
        }

        $sortOrder = $request->query->get('sort', 'desc'); // Default to descending (newest first)
        
        // Validate sort order
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $qb = $orderRepository->createQueryBuilder('o');

        // Users only see their own orders; staff/admin can see all
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            $user = $this->getUser();
            if (!$user instanceof Users) {
                throw $this->createAccessDeniedException();
            }

            $qb->andWhere('o.customer = :customer')
               ->setParameter('customer', $user);
        }

        $orders = $qb->orderBy('o.orderDate', $sortOrder)
            ->addOrderBy('o.id', $sortOrder)
            ->getQuery()
            ->getResult();
        
        return $this->render('order/index.html.twig', [
            'orders' => $orders,
            'current_sort' => $sortOrder,
        ]);
    }

    /**
     * Normalize and clean up order items before saving
     */
    private function normalizeOrderItems(Orders $order, bool $allowAutoAdjust = true): void
    {
        $existingItems = iterator_to_array($order->getOrderItems());

        foreach ($existingItems as $item) {
            $product = $item->getProduct();

            // Skip empty lines
            if (!$product) {
                $order->removeOrderItem($item);
                continue;
            }

            // Set product details
            $item->setName($product->getName());
            $item->setPrice($product->getPrice());
            $item->setType('product');

            // Validate and limit quantity based on stock
            $quantity = $item->getQuantity();
            if ($quantity <= 0) {
                $item->setQuantity(1);
            } elseif ($allowAutoAdjust) {
                $stock = $product->getStock();
                // Only auto-adjust if explicitly allowed
                // Otherwise, let validation catch the error
                if ($quantity > $stock) {
                    $item->setQuantity($stock);
                }
            }
            // If allowAutoAdjust is false, keep the original quantity for validation to catch
        }

        // Update total
        if (method_exists($order, 'recalculateTotal')) {
            $order->recalculateTotal();
        }
    }

    /**
     * Deduct stock from products when order is confirmed
     */
    private function deductStock(Orders $order, EntityManagerInterface $em): void
    {
        foreach ($order->getOrderItems() as $item) {
            $product = $item->getProduct();
            if ($product) {
                $quantity = $item->getQuantity();
                $currentStock = $product->getStock();
                $newStock = max(0, $currentStock - $quantity);
                $product->setStock($newStock);
                $em->persist($product);
            }
        }
    }

    /**
     * Restore stock when order is canceled/rejected
     */
    private function restoreStock(Orders $order, EntityManagerInterface $em): void
    {
        foreach ($order->getOrderItems() as $item) {
            $product = $item->getProduct();
            if ($product) {
                $quantity = $item->getQuantity();
                $currentStock = $product->getStock();
                $product->setStock($currentStock + $quantity);
                $em->persist($product);
            }
        }
    }

    /**
     * Restore stock for a specific product and quantity
     */
    private function restoreProductStock(?Products $product, int $quantity, EntityManagerInterface $em): void
    {
        if ($product && $quantity > 0) {
            $currentStock = $product->getStock();
            $product->setStock($currentStock + $quantity);
            $em->persist($product);
        }
    }

    /**
     * Handle stock updates when order items are edited
     * Compares old items with new items and adjusts stock accordingly
     */
    private function handleOrderItemsStockUpdate(Orders $order, array $originalItems, EntityManagerInterface $em): void
    {
        // Only process if order is approved/pending/completed (orders that have affected stock)
        if (!in_array($order->getStatus(), ['approved', 'pending', 'completed'])) {
            return;
        }

        $newItems = [];
        foreach ($order->getOrderItems() as $item) {
            $product = $item->getProduct();
            if ($product) {
                $productId = $product->getId();
                if (!isset($newItems[$productId])) {
                    $newItems[$productId] = 0;
                }
                $newItems[$productId] += $item->getQuantity();
            }
        }

        // Compare old vs new and adjust stock
        foreach ($originalItems as $productId => $oldQuantity) {
            $newQuantity = $newItems[$productId] ?? 0;
            $difference = $oldQuantity - $newQuantity;

            if ($difference > 0) {
                // Quantity decreased or item removed - restore stock
                $product = $em->getRepository(Products::class)->find($productId);
                if ($product) {
                    $this->restoreProductStock($product, $difference, $em);
                }
            } elseif ($difference < 0) {
                // Quantity increased or item added - deduct stock
                $product = $em->getRepository(Products::class)->find($productId);
                if ($product) {
                    $quantityToDeduct = abs($difference);
                    $currentStock = $product->getStock();
                    $newStock = max(0, $currentStock - $quantityToDeduct);
                    $product->setStock($newStock);
                    $em->persist($product);
                }
            }
        }

        // Handle newly added products
        foreach ($newItems as $productId => $newQuantity) {
            if (!isset($originalItems[$productId])) {
                // New product added - deduct stock
                $product = $em->getRepository(Products::class)->find($productId);
                if ($product) {
                    $currentStock = $product->getStock();
                    $newStock = max(0, $currentStock - $newQuantity);
                    $product->setStock($newStock);
                    $em->persist($product);
                }
            }
        }
    }

    /**
     * Handle stock updates when order status changes
     */
    private function handleStockUpdate(Orders $order, ?string $oldStatus, EntityManagerInterface $em): void
    {
        $newStatus = $order->getStatus();
        
        // If order was previously confirmed (approved/pending) and now canceled, restore stock
        if (in_array($oldStatus, ['approved', 'pending', 'completed']) && $newStatus === 'canceled') {
            $this->restoreStock($order, $em);
        }
        
        // If order is now confirmed (approved/pending/completed), deduct stock
        // But only if it wasn't already confirmed before
        if (in_array($newStatus, ['approved', 'pending', 'completed']) && !in_array($oldStatus, ['approved', 'pending', 'completed'])) {
            $this->deductStock($order, $em);
        }
    }

    /**
     * Persist order with items
     */
    private function saveOrder(Orders $order, EntityManagerInterface $em, ?string $oldStatus = null): void
    {
        $this->normalizeOrderItems($order);

        if ($order->getId() === null) {
            $em->persist($order);
        }

        // Make sure each item references its order
        foreach ($order->getOrderItems() as $item) {
            $item->setOrder($order);
        }

        $em->flush();
        
        // Handle stock updates if status changed
        if ($oldStatus !== null) {
            $this->handleStockUpdate($order, $oldStatus, $em);
            $em->flush();
        }
    }

    private function getFormErrors($form): string
    {
        $messages = [];
        foreach ($form->getErrors(true, true) as $error) {
            $messages[] = $error->getMessage();
        }
        return implode('; ', array_unique($messages));
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ActivityLogService $logService): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_USER')) {
            throw $this->createAccessDeniedException();
        }

        $order = new Orders();
        $user = $this->getUser();
        
        // Automatically set customer to current user
        if ($user instanceof Users) {
            $order->setCustomer($user);
        }
        
        $form = $this->createForm(OrdersType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Validate quantities against stock BEFORE normalizing
            // This ensures we catch the error before auto-adjusting
            $hasStockError = false;
            foreach ($order->getOrderItems() as $item) {
                $product = $item->getProduct();
                if ($product) {
                    $quantity = $item->getQuantity();
                    $stock = $product->getStock();
                    if ($quantity > $stock) {
                        $hasStockError = true;
                        $form->get('orderItems')->addError(new FormError(
                            sprintf('⚠️ Quantity Exceeded! You ordered %d units of "%s", but only %d are available in stock. Please reduce the quantity to %d or less.', 
                                $quantity,
                                $product->getName(), 
                                $stock,
                                $stock
                            )
                        ));
                    }
                }
            }

            // Only normalize if no stock errors (to preserve the original invalid quantity for display)
            if (!$hasStockError) {
                $this->normalizeOrderItems($order);
            } else {
                // Still normalize for other fields (name, price, type) but don't adjust quantity
                $this->normalizeOrderItems($order, false);
            }

            // Ensure customer is set (fallback)
            if ($order->getCustomer() === null && $user instanceof Users) {
                $order->setCustomer($user);
            }

            // Validate required fields
            if ($order->getOrderItems()->count() === 0) {
                $form->get('orderItems')->addError(new FormError('Add at least one item (product or service).'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Validate order date and time is not in the past
            $orderDate = $order->getOrderDate();
            $now = new \DateTime();
            
            if ($orderDate && $orderDate < $now) {
                $form->get('orderDate')->addError(new FormError('Order date and time cannot be in the past. Please select the current time or a future date/time.'));
                return $this->render('order/new.html.twig', [
                    'order' => $order,
                    'form' => $form->createView(),
                ]);
            }
            
            // Ensure customer is set before saving
            if ($order->getCustomer() === null && $user instanceof Users) {
                $order->setCustomer($user);
            }
            
            // Always set status to pending_approval for new orders
            // This ensures all orders require admin approval
            $order->setStatus('pending_approval');
            $oldStatus = null;
            
            $this->saveOrder($order, $em, $oldStatus);
            
            if ($user instanceof Users) {
                $logService->logCreate($user, 'Order', $order->getId(), "Created order #{$order->getId()}");
            }
            
            $this->addFlash('success', 'Order created successfully! Waiting for admin approval.');
            return $this->redirectToRoute('app_order_index');
        }

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Orders $order): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            $user = $this->getUser();
            if (!$user instanceof Users || $order->getCustomer() === null || $order->getCustomer()->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException();
            }
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Orders $order, EntityManagerInterface $em, ActivityLogService $logService): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            $user = $this->getUser();
            if (!$user instanceof Users || $order->getCustomer() === null || $order->getCustomer()->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException();
            }
        }

        // Prevent editing approved, completed, or canceled (rejected) orders
        if (in_array($order->getStatus(), ['approved', 'completed', 'canceled'])) {
            $this->addFlash('error', 'Cannot edit an approved, completed, or rejected order.');
            return $this->redirectToRoute('app_order_index');
        }

        // Store original items before form submission for stock comparison
        $originalItems = [];
        if ($order->getId()) {
            // Refresh order to get current state
            $em->refresh($order);
            foreach ($order->getOrderItems() as $item) {
                $product = $item->getProduct();
                if ($product) {
                    $productId = $product->getId();
                    if (!isset($originalItems[$productId])) {
                        $originalItems[$productId] = 0;
                    }
                    $originalItems[$productId] += $item->getQuantity();
                }
            }
        }

        $form = $this->createForm(OrdersType::class, $order, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Validate quantities against stock BEFORE normalizing
            // This ensures we catch the error before auto-adjusting
            $hasStockError = false;
            foreach ($order->getOrderItems() as $item) {
                $product = $item->getProduct();
                if ($product) {
                    $quantity = $item->getQuantity();
                    $stock = $product->getStock();
                    if ($quantity > $stock) {
                        $hasStockError = true;
                        $form->get('orderItems')->addError(new FormError(
                            sprintf('⚠️ Quantity Exceeded! You ordered %d units of "%s", but only %d are available in stock. Please reduce the quantity to %d or less.', 
                                $quantity,
                                $product->getName(), 
                                $stock,
                                $stock
                            )
                        ));
                    }
                }
            }

            // Only normalize if no stock errors (to preserve the original invalid quantity for display)
            if (!$hasStockError) {
                $this->normalizeOrderItems($order);
            } else {
                // Still normalize for other fields (name, price, type) but don't adjust quantity
                $this->normalizeOrderItems($order, false);
            }

            // Validate required fields
            if ($order->getOrderItems()->count() === 0) {
                $form->get('orderItems')->addError(new FormError('Add at least one item (product or service).'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Validate order date and time is not in the past
            $orderDate = $order->getOrderDate();
            $now = new \DateTime();
            
            if ($orderDate && $orderDate < $now) {
                $form->get('orderDate')->addError(new FormError('Order date and time cannot be in the past. Please select the current time or a future date/time.'));
                return $this->render('order/edit.html.twig', [
                    'order' => $order,
                    'form' => $form->createView(),
                ]);
            }
            
            $oldStatus = $order->getStatus();
            
            // Always set status to pending_approval when editing
            // This ensures any edits require re-approval
            if (in_array($oldStatus, ['approved', 'pending', 'completed'])) {
                // Restore stock since order is going back to pending_approval
                $this->restoreStock($order, $em);
                $em->flush();
            }
            
            // Set status to pending_approval
            $order->setStatus('pending_approval');
            
            $this->saveOrder($order, $em, $oldStatus);
            
            $user = $this->getUser();
            if ($user instanceof Users) {
                $logService->logUpdate($user, 'Order', $order->getId(), "Updated order #{$order->getId()} - Status reset to pending approval");
            }
            $this->addFlash('success', 'Order updated successfully! Status has been reset to pending approval and requires admin approval.');
            return $this->redirectToRoute('app_order_index');
        }
        // Form errors are displayed via form_errors() in the template, no need for flash message

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/approve', name: 'app_order_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approve(Request $request, Orders $order, EntityManagerInterface $em, ActivityLogService $logService): Response
    {
        if ($this->isCsrfTokenValid('approve' . $order->getId(), $request->request->get('_token'))) {
            // Only approve orders that are pending approval
            if ($order->getStatus() !== 'pending_approval') {
                $this->addFlash('warning', 'This order cannot be approved. It is not in pending approval status.');
                return $this->redirectToRoute('app_order_index');
            }
            
            // Ensure the order is managed
            $order->setStatus('approved');
            $em->persist($order);
            $em->flush();
            
            // Deduct stock when order is approved
            $this->deductStock($order, $em);
            $em->flush();
            
            $user = $this->getUser();
            if ($user instanceof Users) {
                $logService->logUpdate($user, 'Order', $order->getId(), "Approved order #{$order->getId()}");
            }
            
            $this->addFlash('success', 'Order approved successfully! Stock has been deducted.');
        } else {
            $this->addFlash('error', 'Invalid security token. Please try again.');
        }

        return $this->redirectToRoute('app_order_index');
    }

    #[Route('/{id}/reject', name: 'app_order_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reject(Request $request, Orders $order, EntityManagerInterface $em, ActivityLogService $logService): Response
    {
        // Prevent rejecting approved or completed orders
        if (in_array($order->getStatus(), ['approved', 'completed'])) {
            $this->addFlash('error', 'Cannot reject an approved or completed order.');
            return $this->redirectToRoute('app_order_index');
        }

        if ($this->isCsrfTokenValid('reject' . $order->getId(), $request->request->get('_token'))) {
            $oldStatus = $order->getStatus();
            $order->setStatus('canceled');
            $em->flush();
            
            // Restore stock if order was previously approved/pending
            if (in_array($oldStatus, ['approved', 'pending', 'completed'])) {
                $this->restoreStock($order, $em);
                $em->flush();
            }
            
            $user = $this->getUser();
            if ($user instanceof Users) {
                $logService->logUpdate($user, 'Order', $order->getId(), "Rejected order #{$order->getId()}");
            }
            
            $this->addFlash('success', 'Order rejected successfully!');
        }

        return $this->redirectToRoute('app_order_index');
    }

    #[Route('/{id}/delete', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Orders $order, EntityManagerInterface $em, ActivityLogService $logService): Response
    {
        // Only admins can delete orders
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $order->getId(), $request->request->get('_token'))) {
            $orderId = $order->getId();
            $orderStatus = $order->getStatus();
            
            // Restore stock if order was approved, pending, or completed
            if (in_array($orderStatus, ['approved', 'pending', 'completed'])) {
                $this->restoreStock($order, $em);
                $em->flush();
            }
            
            $em->remove($order);
            $em->flush();
            $user = $this->getUser();
            if ($user instanceof Users) {
                $logService->logDelete($user, 'Order', $orderId, "Deleted order #{$orderId}");
            }
            $this->addFlash('success', 'Order deleted successfully!');
        }

        return $this->redirectToRoute('app_order_index');
    }
}
