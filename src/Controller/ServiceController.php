<?php

namespace App\Controller;

use App\Entity\Services;
use App\Form\ServicesType;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ActivityLogService;
use App\Entity\Users;

#[Route('/services')]
#[IsGranted('ROLE_STAFF')]
class ServiceController extends AbstractController
{
    #[Route('/', name: 'app_service_index', methods: ['GET'])]
    public function index(ServiceRepository $servicesRepository): Response
    {
        return $this->render('service/index.html.twig', [
            'services' => $servicesRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_service_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ActivityLogService $logService): Response
    {
        $service = new Services();
        $form = $this->createForm(ServicesType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($service);
            $em->flush();
            $user = $this->getUser();
            if ($user instanceof Users) {
                $logService->logCreate($user, 'Service', $service->getId(), "Created service: {$service->getName()}");
            }
            $this->addFlash('success', 'Service created successfully!');
            return $this->redirectToRoute('app_service_index');
        }

        return $this->render('service/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_service_edit', methods: ['GET', 'POST'])]
    public function edit(Services $service, Request $request, EntityManagerInterface $em, ActivityLogService $logService): Response
    {
        $form = $this->createForm(ServicesType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $user = $this->getUser();
            if ($user instanceof Users) {
                $logService->logUpdate($user, 'Service', $service->getId(), "Updated service: {$service->getName()}");
            }
            $this->addFlash('success', 'Service updated successfully!');
            return $this->redirectToRoute('app_service_index');
        }

        return $this->render('service/edit.html.twig', [
            'form' => $form->createView(),
            'service' => $service,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_service_delete', methods: ['POST'])]
    public function delete(Services $service, Request $request, EntityManagerInterface $em, ActivityLogService $logService): Response
    {
        if ($this->isCsrfTokenValid('delete'.$service->getId(), $request->request->get('_token'))) {
            $serviceId = $service->getId();
            $serviceName = $service->getName();
            $em->remove($service);
            $em->flush();
            $user = $this->getUser();
            if ($user instanceof Users) {
                $logService->logDelete($user, 'Service', $serviceId, "Deleted service: {$serviceName}");
            }
            $this->addFlash('success', 'Service deleted successfully!');
        }

        return $this->redirectToRoute('app_service_index');
    }

    #[Route('/{id}', name: 'app_service_view', methods: ['GET'])]
    public function view(Services $service): Response
    {
        return $this->render('service/view.html.twig', [
            'service' => $service,
        ]);
    }
}
