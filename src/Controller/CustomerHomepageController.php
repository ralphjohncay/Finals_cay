<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CustomerHomepageController extends AbstractController
{
    #[Route('/customer/homepage', name: 'customer_homepage', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var \App\Entity\Users|null $user */
        $user = $this->getUser();

        return $this->render('customer_homepage/index.html.twig', [
            'user' => $user,
        ]);
    }
}

