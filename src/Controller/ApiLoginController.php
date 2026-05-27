<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\Users;

class ApiLoginController extends AbstractController
{
    #[Route('/api/login', name: 'api_login_check', methods: ['POST'])]
    public function loginCheck(): JsonResponse
    {
        // This controller should normally never run because the `json_login`
        // authenticator intercepts POST /api/login and returns the JWT response.
        return $this->json([
            'success' => false,
            'message' => 'This endpoint is handled by API authentication.',
        ], 501);
    }

    // NOTE: Do not use /api/login here because POST /api/login is handled by json_login (JWT).
    #[Route('/api/login-info', name: 'api_login_info', methods: ['GET'])]
    public function loginInfo(): JsonResponse
    {
        $response = $this->json([
            'message' => 'API authentication',
            'usage' => 'POST to /api/login with JSON body: {"email": "your@email.com", "password": "your_password"}',
            'response' => 'Returns {"token": "..."} on success. Use the token in the Authorization header: Bearer <token>',
        ]);
        $response->setEncodingOptions(\JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);

        return $response;
    }

}