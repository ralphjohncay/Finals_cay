<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Return JSON (not HTML) for API route errors.
 */
final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $status = 500;
        $message = 'An unexpected error occurred.';

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $message = $exception->getMessage() ?: $message;
        }

        $event->setResponse(new JsonResponse([
            'success' => false,
            'message' => $message,
        ], $status));
    }
}
