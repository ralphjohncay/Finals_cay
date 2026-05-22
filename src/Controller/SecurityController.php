<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Form\RegistrationType;
use App\Service\ActivityLogService;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Form\FormError;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            // Redirect admin users to admin dashboard
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard');
            }
            // Redirect staff users to admin dashboard (limited access)
            if ($this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('staff_dashboard');
            }
            return $this->redirectToRoute('customer_homepage');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/profile', name: 'app_profile')]
    public function profile(Request $request, EntityManagerInterface $em, ActivityLogService $logService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Users) {
            return $this->redirectToRoute('login');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $logService->logUpdate($user, 'User', $user->getId(), 'Updated own profile');
            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('security/profile.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/change-password', name: 'app_change_password')]
    public function changePassword(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, ActivityLogService $logService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Users) {
            return $this->redirectToRoute('login');
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Get form data
            $currentPasswordField = $form->get('currentPassword');
            $currentPassword = $currentPasswordField->getData();
            
            // Check current password
            $currentPasswordValid = false;
            $currentPasswordError = null;
            
            if (!$currentPassword || trim($currentPassword) === '') {
                $currentPasswordError = 'Please enter your current password.';
            } elseif (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $currentPasswordError = 'Current password is incorrect.';
            } else {
                $currentPasswordValid = true;
            }
            
            // Get newPassword field to check its validity separately
            $newPasswordField = $form->get('newPassword');
            $newPasswordFirst = $newPasswordField->get('first')->getData();
            $newPasswordSecond = $newPasswordField->get('second')->getData();
            
            // Check if newPassword fields are valid (match, length, etc.)
            $newPasswordValid = false;
            if ($newPasswordFirst && $newPasswordSecond) {
                if ($newPasswordFirst !== $newPasswordSecond) {
                    $newPasswordField->addError(new FormError('The password fields must match.'));
                } elseif (strlen($newPasswordFirst) < 6) {
                    $newPasswordField->get('first')->addError(new FormError('Your password should be at least 6 characters'));
                } else {
                    $newPasswordValid = true;
                }
            }
            
            // Add current password error if needed
            if ($currentPasswordError) {
                $currentPasswordField->addError(new FormError($currentPasswordError));
                if ($currentPasswordError === 'Current password is incorrect.') {
                    $this->addFlash('danger', 'Current password is incorrect. Please try again.');
                }
            }
            
            // If both are valid, proceed with password change
            if ($currentPasswordValid && $newPasswordValid) {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPasswordFirst);
                $user->setPassword($hashedPassword);
                $em->flush();

                $logService->logUpdate($user, 'User', $user->getId(), 'Changed password');
                $this->addFlash('success', 'Password changed successfully! Your password has been updated.');
                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('security/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        EmailVerificationService $emailVerificationService,
        UrlGeneratorInterface $urlGenerator
    ): Response
    {
        // If user is already logged in, redirect to home
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new Users();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash password
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            
            // Set default role for new signups
            $user->setRoles(['ROLE_USER']);
            $user->setIsActive(true);
            $user->setIsVerified(false);

            $token = $emailVerificationService->generateVerificationToken();
            $user->setVerificationToken($token);

            try {
                $em->persist($user);
                $em->flush();

                $verificationUrl = $urlGenerator->generate('app_verify_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                $emailVerificationService->sendVerificationEmail($user, $verificationUrl);

                $this->addFlash('success', 'Registration successful! Please check your email to verify your account before logging in.');
                return $this->redirectToRoute('login');
            } catch (UniqueConstraintViolationException $e) {
                $form->get('email')->addError(new FormError('This email is already registered.'));
                $this->addFlash('danger', 'Cannot register: email already exists.');
            } catch (\Throwable $e) {
                $this->addFlash('danger', 'Registration succeeded but we could not send the verification email. Please contact support.');
            }
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

