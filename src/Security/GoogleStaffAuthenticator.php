<?php

namespace App\Security;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class GoogleStaffAuthenticator extends OAuth2Authenticator
{
    use TargetPathTrait;

    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private RouterInterface $router
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $client = $this->clientRegistry->getClient('google');

        try {
            $accessToken = $this->fetchAccessToken($client);
            $googleUser = $client->fetchUserFromToken($accessToken);
        } catch (IdentityProviderException $e) {
            throw new AuthenticationException('Google authentication failed.');
        }

        $googleData = $googleUser->toArray();
        $email = (string) ($googleData['email'] ?? '');
        $displayName = (string) ($googleData['name'] ?? '');

        if ($email === '') {
            throw new AuthenticationException('Google did not return an email address.');
        }

        return new SelfValidatingPassport(
            new UserBadge($email, function () use ($email, $displayName) {
                /** @var Users|null $user */
                $user = $this->em->getRepository(Users::class)->findOneBy(['email' => $email]);

                if (!$user) {
                    $user = new Users();
                    $user->setEmail($email);

                    $user->setName($displayName ?: $email);

                    // Password is required by the entity; generate a random one (not used for OAuth login).
                    $random = bin2hex(random_bytes(24));
                    $user->setPassword($this->passwordHasher->hashPassword($user, $random));

                    $this->em->persist($user);
                }

                // This login is intended for staff. Ensure staff role (don't downgrade admins).
                $roles = $user->getRoles();
                if (!in_array('ROLE_ADMIN', $roles, true)) {
                    // Force staff-only for Google logins (also upgrades existing ROLE_USER-only accounts).
                    $roles = ['ROLE_STAFF'];
                }
                $user->setRoles(array_values(array_unique($roles)));

                // No email verification required for this OAuth path.
                $user->setIsVerified(true);
                $user->setVerificationToken(null);

                if (!$user->isActive()) {
                    // Respect your existing "active" checks; Google login should not reactivate disabled accounts.
                    // (Leaving as-is keeps disabled accounts blocked by UserChecker.)
                }

                $this->em->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
        if ($targetPath) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('staff_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $session = $request->getSession();
        if ($session instanceof \Symfony\Component\HttpFoundation\Session\Session) {
            /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
            $session->getFlashBag()->add('danger', 'Google sign-in failed. Please try again.');
        }
        return new RedirectResponse($this->router->generate('login'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate('login');
    }
}

