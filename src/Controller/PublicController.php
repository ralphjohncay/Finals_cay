<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicController extends AbstractController
{
    #[Route('/about', name: 'app_about', methods: ['GET'])]
    public function about(): Response
    {
        if ($this->getUser()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard');
            }
            if ($this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('staff_dashboard');
            }
        }

        return $this->render('public/about.html.twig');
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET'])]
    public function contact(): Response
    {
        if ($this->getUser()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard');
            }
            if ($this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('staff_dashboard');
            }
        }

        return $this->render('public/contact.html.twig');
    }
}

