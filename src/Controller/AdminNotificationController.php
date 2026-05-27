<?php

namespace App\Controller;

use App\Entity\AppNotification;
use App\Form\AppNotificationType;
use App\Repository\AppNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/notifications')]
#[IsGranted('ROLE_ADMIN')]
class AdminNotificationController extends AbstractController
{
    #[Route('', name: 'admin_notifications_index', methods: ['GET'])]
    public function index(AppNotificationRepository $repository): Response
    {
        return $this->render('admin/notification/index.html.twig', [
            'notifications' => $repository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'admin_notifications_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $notification = new AppNotification();
        $form = $this->createForm(AppNotificationType::class, $notification);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $notification->touchUpdatedAt();
            $em->persist($notification);
            $em->flush();
            $this->addFlash('success', 'Notification published. It will appear in the mobile app after refresh.');

            return $this->redirectToRoute('admin_notifications_index');
        }

        return $this->render('admin/notification/form.html.twig', [
            'form' => $form,
            'title' => 'New app notification',
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_notifications_edit', methods: ['GET', 'POST'])]
    public function edit(
        AppNotification $notification,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $form = $this->createForm(AppNotificationType::class, $notification);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $notification->touchUpdatedAt();
            $em->flush();
            $this->addFlash('success', 'Notification updated.');

            return $this->redirectToRoute('admin_notifications_index');
        }

        return $this->render('admin/notification/form.html.twig', [
            'form' => $form,
            'title' => 'Edit app notification',
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_notifications_delete', methods: ['POST'])]
    public function delete(
        AppNotification $notification,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$notification->getId(), (string) $request->request->get('_token'))) {
            $em->remove($notification);
            $em->flush();
            $this->addFlash('success', 'Notification removed.');
        }

        return $this->redirectToRoute('admin_notifications_index');
    }
}
