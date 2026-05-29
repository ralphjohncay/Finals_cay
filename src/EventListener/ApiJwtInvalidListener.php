<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Return JSON the mobile app understands when a JWT is missing, expired, or invalid.
 */
final class ApiJwtInvalidListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_INVALID => 'onJwtInvalid',
            Events::JWT_EXPIRED => 'onJwtExpired',
            Events::JWT_NOT_FOUND => 'onJwtNotFound',
        ];
    }

    public function onJwtInvalid(JWTInvalidEvent $event): void
    {
        $event->setResponse($this->response());
    }

    public function onJwtExpired(JWTExpiredEvent $event): void
    {
        $event->setResponse($this->response());
    }

    public function onJwtNotFound(JWTNotFoundEvent $event): void
    {
        $event->setResponse($this->response('JWT Token not found'));
    }

    private function response(string $message = 'Session expired. Please sign in again.'): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
        ], 401);
    }
}
