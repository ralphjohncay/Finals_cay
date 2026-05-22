<?php

namespace App\Controller;

use App\Entity\Users;
use App\Service\EmailVerificationService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class ApiRegisterController extends AbstractController
{
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        EmailVerificationService $emailVerificationService,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON body',
            ], 400);
        }

        $email = isset($data['email']) ? trim((string) $data['email']) : '';
        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        $plainPassword = isset($data['password']) ? (string) $data['password'] : '';

        if ($email === '' || $name === '' || $plainPassword === '') {
            return $this->json([
                'success' => false,
                'message' => 'email, name, and password are required',
            ], 400);
        }

        $user = new Users();
        $user->setEmail($email);
        $user->setName($name);
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);
        // App and API users can sign in immediately; email verification remains optional on the website.
        $user->setIsVerified(true);

        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

        $token = $emailVerificationService->generateVerificationToken();
        $user->setVerificationToken($token);

        $errors = $validator->validate($user);
        if (\count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }

            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $messages,
            ], 422);
        }

        try {
            $em->persist($user);
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            return $this->json([
                'success' => false,
                'message' => 'This email is already registered.',
            ], 409);
        }

        $verificationUrl = $urlGenerator->generate(
            'app_verify_email',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        try {
            $emailVerificationService->sendVerificationEmail($user, $verificationUrl);
        } catch (\Throwable) {
            // If email fails, registration still succeeded; user can use /api/resend-verification later.
        }

        $response = $this->json([
            'success' => true,
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'roles' => $user->getRoles(),
                'isVerified' => $user->isVerified(),
            ],
            'verification' => [
                'token' => $token,
                'url' => $verificationUrl,
            ],
        ], 201);
        $response->setEncodingOptions(\JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);

        return $response;
    }
}

