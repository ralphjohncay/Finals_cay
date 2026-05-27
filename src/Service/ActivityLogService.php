<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLogService
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack
    ) {
    }

    public function log(
        Users $user,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $description = null,
        ?array $affectedData = null
    ): void {
        $log = new ActivityLog();
        $log->setUser($user);
        $log->setAction($action);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setDescription($description);

        if ($affectedData !== null) {
            $log->setAffectedData(json_encode($affectedData, JSON_PRETTY_PRINT));
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $log->setIpAddress($request->getClientIp());
        }

        try {
            $this->em->persist($log);
            $this->em->flush();
        } catch (\Throwable) {
            // Never block product/order saves if logging fails (e.g. missing table on Railway).
        }
    }

    public function logLogin(Users $user): void
    {
        $this->log($user, 'LOGIN', 'User', $user->getId(), "User: {$user->getEmail()} (ID: {$user->getId()})");
    }

    public function logLogout(Users $user): void
    {
        $this->log($user, 'LOGOUT', 'User', $user->getId(), "User: {$user->getEmail()} (ID: {$user->getId()})");
    }

    public function logCreate(Users $user, string $entityType, int $entityId, ?string $description = null, ?array $data = null): void
    {
        $targetData = $description ?? "Created {$entityType}";
        $this->log($user, 'CREATE', $entityType, $entityId, $targetData, $data);
    }

    public function logUpdate(Users $user, string $entityType, int $entityId, ?string $description = null, ?array $data = null): void
    {
        $targetData = $description ?? "Updated {$entityType}";
        $this->log($user, 'UPDATE', $entityType, $entityId, $targetData, $data);
    }

    public function logDelete(Users $user, string $entityType, int $entityId, ?string $description = null): void
    {
        $targetData = $description ?? "Deleted {$entityType}";
        $this->log($user, 'DELETE', $entityType, $entityId, $targetData);
    }
}

