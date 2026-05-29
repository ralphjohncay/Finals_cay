<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\Users;
use App\Repository\ActivityLogRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Maps activity_logs rows to admin toast alerts (customer orders, sign-in, registration).
 */
final class AdminAlertService
{
    public function __construct(
        private ActivityLogRepository $logRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getLatestLogId(): int
    {
        return $this->logRepository->getMaxId();
    }

    /**
     * @return list<array{id: int, kind: string, title: string, message: string, url: string|null, at: string}>
     */
    public function getAlertsSince(int $sinceId): array
    {
        $logs = $this->logRepository->findSinceId($sinceId);
        $alerts = [];

        foreach ($logs as $log) {
            $mapped = $this->mapLogToAlert($log);
            if ($mapped !== null) {
                $alerts[] = $mapped;
            }
        }

        return $alerts;
    }

    /**
     * @return array{id: int, kind: string, title: string, message: string, url: string|null, at: string}|null
     */
    private function mapLogToAlert(ActivityLog $log): ?array
    {
        $user = $log->getUser();
        if (!$user instanceof Users) {
            return null;
        }

        $action = $log->getAction();
        $entityType = $log->getEntityType();
        $createdAt = $log->getCreatedAt()?->format(\DateTimeInterface::ATOM) ?? '';

        $base = [
            'id' => (int) $log->getId(),
            'at' => $createdAt,
        ];

        if ($action === 'REGISTER' && $entityType === 'User' && $this->isCustomer($user)) {
            return $base + [
                'kind' => 'register',
                'title' => 'New customer',
                'message' => sprintf('%s signed up (%s)', $user->getName() ?? 'Customer', $user->getEmail()),
                'url' => $this->urlGenerator->generate('admin_user_show', ['id' => $user->getId()]),
            ];
        }

        if ($action === 'LOGIN' && $entityType === 'User' && $this->isCustomer($user)) {
            return $base + [
                'kind' => 'login',
                'title' => 'Customer signed in',
                'message' => sprintf('%s logged in', $user->getEmail()),
                'url' => $this->urlGenerator->generate('admin_user_show', ['id' => $user->getId()]),
            ];
        }

        if ($action === 'CREATE' && $entityType === 'Order' && $this->isCustomer($user)) {
            $orderId = $log->getEntityId();
            $message = $log->getDescription() ?? ($orderId ? sprintf('Order #%d placed', $orderId) : 'New customer order');

            return $base + [
                'kind' => 'order',
                'title' => 'New order',
                'message' => $message,
                'url' => $orderId
                    ? $this->urlGenerator->generate('app_order_show', ['id' => $orderId])
                    : $this->urlGenerator->generate('app_order_index'),
            ];
        }

        return null;
    }

    private function isCustomer(Users $user): bool
    {
        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_STAFF', $roles, true)) {
            return false;
        }

        return in_array('ROLE_USER', $roles, true);
    }
}
