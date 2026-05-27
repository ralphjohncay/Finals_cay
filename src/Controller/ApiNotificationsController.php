<?php

namespace App\Controller;

use App\Entity\AppNotification;
use App\Entity\Users;
use App\Repository\AppNotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
class ApiNotificationsController extends AbstractController
{
    #[Route('/notifications', name: 'api_notifications', methods: ['GET'])]
    public function list(
        AppNotificationRepository $repository,
        #[CurrentUser] ?Users $user = null,
    ): JsonResponse {
        $audiences = [AppNotification::AUDIENCE_ALL];
        if ($user instanceof Users) {
            $audiences[] = AppNotification::AUDIENCE_CUSTOMERS;
        }

        $now = new \DateTimeImmutable();
        $items = $repository->findActiveForAudiences($audiences, $now);

        return $this->json([
            'success' => true,
            'notifications' => array_map(
                static fn (AppNotification $n) => [
                    'id' => $n->getId(),
                    'title' => $n->getTitle(),
                    'message' => $n->getMessage(),
                    'type' => $n->getType(),
                    'audience' => $n->getAudience(),
                    'priority' => $n->getPriority(),
                    'startsAt' => $n->getStartsAt()->format(\DateTimeInterface::ATOM),
                    'expiresAt' => $n->getExpiresAt()?->format(\DateTimeInterface::ATOM),
                    'createdAt' => $n->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ],
                $items,
            ),
        ]);
    }
}
