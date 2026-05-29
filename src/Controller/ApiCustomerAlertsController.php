<?php

namespace App\Controller;

use App\Entity\CustomerNotification;
use App\Entity\Users;
use App\Repository\CustomerNotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class ApiCustomerAlertsController extends AbstractController
{
    #[Route('/customer-alerts', name: 'api_customer_alerts', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(
        Request $request,
        CustomerNotificationRepository $repository,
        #[CurrentUser] Users $user,
    ): JsonResponse {
        if (!$request->query->has('since')) {
            return $this->json([
                'success' => true,
                'cursor' => $repository->getMaxIdForUser($user),
                'alerts' => [],
            ]);
        }

        $since = max(0, (int) $request->query->get('since'));
        $alerts = $repository->findForUserSince($user, $since);
        $cursor = $since;
        foreach ($alerts as $alert) {
            $cursor = max($cursor, (int) $alert->getId());
        }
        $cursor = max($cursor, $repository->getMaxIdForUser($user));

        $response = $this->json([
            'success' => true,
            'cursor' => $cursor,
            'alerts' => array_map(
                static fn (CustomerNotification $n) => [
                    'id' => $n->getId(),
                    'category' => $n->getCategory(),
                    'event' => $n->getEvent(),
                    'title' => $n->getTitle(),
                    'message' => $n->getMessage(),
                    'type' => $n->getType(),
                    'entityType' => $n->getEntityType(),
                    'entityId' => $n->getEntityId(),
                    'createdAt' => $n->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ],
                $alerts,
            ),
        ]);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $response;
    }
}
