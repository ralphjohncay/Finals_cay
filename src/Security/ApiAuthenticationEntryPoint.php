<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class ApiAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse([
            'code' => 401,
            'message' => 'JWT Token not found',
            'help' => 'To authenticate: POST to /api/login with {"email": "your@email.com", "password": "your_password"}, then use the returned token in the Authorization header: Bearer <token>',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
