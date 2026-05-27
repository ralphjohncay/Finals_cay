<?php

namespace App\Controller;

use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
class ApiMeController extends AbstractController
{
    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?Users $user): JsonResponse
    {
        if (null === $user) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return $this->json([
            'success' => true,
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'isActive' => $user->isActive(),
        ]);
    }
}
