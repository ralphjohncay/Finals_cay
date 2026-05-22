<?php

namespace App\EventListener;

use App\Entity\Users;
use App\Service\ActivityLogService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class AuthenticationListener implements EventSubscriberInterface
{
    public function __construct(
        private ActivityLogService $logService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if ($user instanceof Users) {
            $this->logService->logLogin($user);
        }
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        if ($user instanceof Users) {
            $this->logService->logLogout($user);
        }
    }
}

