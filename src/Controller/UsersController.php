<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\UsersType;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ActivityLogService;
use App\Entity\Users as UserEntity;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/users')]
#[IsGranted('ROLE_ADMIN')]
class UsersController extends AbstractController
{
    #[Route('/', name: 'user_index', methods: ['GET'])]
    public function index(UsersRepository $usersRepository): Response
    {
        $customers = $usersRepository->createQueryBuilder('u')
            ->where('u.roles NOT LIKE :admin AND u.roles NOT LIKE :staff')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->setParameter('staff', '%ROLE_STAFF%')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('users/index.html.twig', [
            'users' => $customers,
        ]);
    }

    #[Route('/new', name: 'user_new', methods: ['GET', 'POST'])]
    public function new(): Response
    {
        // Redirect to unified user creation page
        return $this->redirectToRoute('admin_user_new');
    }

    #[Route('/{id}/show', name: 'user_show', methods: ['GET'])]
    public function show(Users $user): Response
    {
        // Redirect to unified user show page
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function edit(Users $user): Response
    {
        // Redirect to unified user edit page
        return $this->redirectToRoute('admin_user_edit', ['id' => $user->getId()]);
    }

    #[Route('/{id}/delete', name: 'user_delete', methods: ['POST'])]
    public function delete(): Response
    {
        // Redirect to admin user management
        return $this->redirectToRoute('admin_user_index');
    }
}
