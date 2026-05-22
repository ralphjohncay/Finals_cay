<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\AdminUserType;
use App\Repository\UsersRepository;
use App\Repository\ActivityLogRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\FormError;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    #[Route('/', name: 'admin_user_index', methods: ['GET'])]
    public function index(UsersRepository $usersRepository, Request $request): Response
    {
        // Only show admin and staff users
        $qb = $usersRepository->createQueryBuilder('u');
        $qb->where('u.roles LIKE :admin OR u.roles LIKE :staff')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->setParameter('staff', '%ROLE_STAFF%');
        
        $users = $qb->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        ActivityLogService $logService
    ): Response {
        $user = new Users();
        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Roles are automatically transformed by the form transformer
            // Password is required for new users
            $plainPassword = $form->get('plainPassword')->getData();
            if (!$plainPassword) {
                $form->get('plainPassword')->addError(new FormError('Password is required for new users.'));
                return $this->render('admin/user/new.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
                ]);
            }
            
            // Hash password
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            $user->setIsVerified(true);

            try {
                $em->persist($user);
                $em->flush();

                $currentUser = $this->getUser();
                if ($currentUser instanceof Users) {
                    $logService->logCreate($currentUser, 'User', $user->getId(), "User: {$user->getEmail()} (ID: {$user->getId()})", [
                        'email' => $user->getEmail(),
                        'roles' => $user->getRoles(),
                    ]);
                }

                $this->addFlash('success', 'User account created successfully!');
                return $this->redirectToRoute('admin_user_index');
            } catch (UniqueConstraintViolationException $e) {
                $form->get('email')->addError(new FormError('This email is already registered.'));
                $this->addFlash('danger', 'Cannot create user: email already exists.');
            }
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'])]
    public function show(Users $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Users $user,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        ActivityLogService $logService
    ): Response {
        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Roles are automatically transformed by the form transformer
            // Password is now handled via separate change password page

            try {
                $em->flush();

                $currentUser = $this->getUser();
                if ($currentUser instanceof Users) {
                    $logService->logUpdate($currentUser, 'User', $user->getId(), "User: {$user->getEmail()} (ID: {$user->getId()})", [
                        'email' => $user->getEmail(),
                        'roles' => $user->getRoles(),
                        'is_active' => $user->isActive(),
                    ]);
                }

                $this->addFlash('success', 'User account updated successfully!');
                return $this->redirectToRoute('admin_user_index');
            } catch (UniqueConstraintViolationException $e) {
                $form->get('email')->addError(new FormError('This email is already registered.'));
                $this->addFlash('danger', 'Cannot update user: email already exists.');
            }
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/toggle-status', name: 'admin_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(
        Request $request,
        Users $user,
        EntityManagerInterface $em,
        ActivityLogService $logService
    ): Response {
        $currentUser = $this->getUser();
        
        if (!$currentUser instanceof Users) {
            $this->addFlash('danger', 'You must be logged in to perform this action.');
            return $this->redirectToRoute('admin_user_index');
        }
        
        // Prevent users from disabling their own account
        if ($currentUser->getId() === $user->getId()) {
            $this->addFlash('danger', 'You cannot disable your own account. Ask another admin to do it.');
            return $this->redirectToRoute('admin_user_index');
        }
        
        if ($this->isCsrfTokenValid('toggle_status' . $user->getId(), $request->request->get('_token'))) {
            $user->setIsActive(!$user->isActive());
            $em->flush();

            if ($currentUser instanceof Users) {
                $status = $user->isActive() ? 'enabled' : 'disabled';
                $logService->logUpdate($currentUser, 'User', $user->getId(), "User: {$user->getEmail()} (ID: {$user->getId()}) - {$status}");
            }

            $this->addFlash('success', "User account " . ($user->isActive() ? 'enabled' : 'disabled') . " successfully!");
        }

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}/change-password', name: 'admin_user_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        Users $user,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        ActivityLogService $logService
    ): Response {
        $currentUser = $this->getUser();
        
        if (!$currentUser instanceof Users) {
            $this->addFlash('danger', 'You must be logged in to perform this action.');
            return $this->redirectToRoute('admin_user_index');
        }

        $form = $this->createForm(\App\Form\AdminChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPasswordField = $form->get('newPassword');
            $newPassword = $newPasswordField->get('first')->getData();
            
            if ($newPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $em->flush();

                // Log the action
                if ($currentUser instanceof Users) {
                    $logService->logUpdate($currentUser, 'User', $user->getId(), "Password changed for user: {$user->getEmail()} (ID: {$user->getId()})");
                }

                $this->addFlash('success', "Password changed successfully for {$user->getEmail()}!");
                return $this->redirectToRoute('admin_user_edit', ['id' => $user->getId()]);
            }
        }

        return $this->render('admin/user/change_password.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/reset-password', name: 'admin_user_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        Users $user,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        ActivityLogService $logService
    ): Response {
        $currentUser = $this->getUser();
        
        if (!$currentUser instanceof Users) {
            $this->addFlash('danger', 'You must be logged in to perform this action.');
            return $this->redirectToRoute('admin_user_index');
        }
        
        if ($this->isCsrfTokenValid('reset_password' . $user->getId(), $request->request->get('_token'))) {
            // Generate a secure temporary password (12 characters: uppercase, lowercase, numbers)
            $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789'; // Excludes ambiguous characters
            $temporaryPassword = '';
            $length = 12;
            
            for ($i = 0; $i < $length; $i++) {
                $temporaryPassword .= $characters[random_int(0, strlen($characters) - 1)];
            }
            
            // Hash and set the new password
            $hashedPassword = $passwordHasher->hashPassword($user, $temporaryPassword);
            $user->setPassword($hashedPassword);
            $em->flush();

            // Log the action
            if ($currentUser instanceof Users) {
                $logService->logUpdate($currentUser, 'User', $user->getId(), "Password reset for user: {$user->getEmail()} (ID: {$user->getId()})");
            }

            // Store temporary password in session to display it
            $request->getSession()->set('temp_password_' . $user->getId(), $temporaryPassword);
            $request->getSession()->set('temp_password_user_' . $user->getId(), $user->getEmail());

            $this->addFlash('success', "Password reset successfully! Temporary password generated for {$user->getEmail()}.");
            $this->addFlash('info', "Temporary Password: <strong>{$temporaryPassword}</strong> - Please copy this password and share it securely with the user.");
        } else {
            $this->addFlash('danger', 'Invalid security token. Please try again.');
        }

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Users $user,
        EntityManagerInterface $em,
        ActivityLogService $logService,
        ActivityLogRepository $activityLogRepository
    ): Response {
        // Admin can delete any staff/admin account (except themselves)
        $currentUser = $this->getUser();
        
        if (!$currentUser instanceof Users) {
            $this->addFlash('danger', 'You must be logged in to perform this action.');
            return $this->redirectToRoute('admin_user_index');
        }
        
        // Prevent admin from deleting themselves
        if ($currentUser->getId() === $user->getId()) {
            $this->addFlash('danger', 'You cannot delete your own account. Ask another admin to do it.');
            return $this->redirectToRoute('admin_user_index');
        }
        
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $userId = $user->getId();
            $userEmail = $user->getEmail();

            // Delete all activity logs associated with this user to avoid foreign key constraint violation
            $em->createQuery('DELETE FROM App\Entity\ActivityLog a WHERE a.user = :user')
                ->setParameter('user', $user)
                ->execute();

            $em->remove($user);
            $em->flush();

            if ($currentUser instanceof Users) {
                $logService->logDelete($currentUser, 'User', $userId, "User: {$userEmail} (ID: {$userId})");
            }

            $this->addFlash('success', 'User account deleted successfully!');
        } else {
            $this->addFlash('danger', 'Invalid security token. Please try again.');
        }

        return $this->redirectToRoute('admin_user_index');
    }
}
