<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\Users;

class ApiLoginController extends AbstractController
{
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