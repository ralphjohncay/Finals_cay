<?php

namespace App\Security;

use App\Entity\Users;
use App\Service\ActivityLogService;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler as LexikSuccessHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Extends Lexik JWT login response with user profile fields for the mobile app.
 */
final class JwtAuthenticationSuccessHandler
{
    public function __construct(
        private LexikSuccessHandler $lexikHandler,
        private ActivityLogService $logService,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        $response = $this->lexikHandler->onAuthenticationSuccess($request, $token);
        $payload = json_decode($response->getContent(), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $user = $token->getUser();
        if ($user instanceof Users) {
            $this->logService->logLogin($user);
            $payload['success'] = true;
            $payload['user'] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'roles' => $user->getRoles(),
            ];
        }

        return new JsonResponse($payload, $response->getStatusCode(), $response->headers->all());
    }
}
