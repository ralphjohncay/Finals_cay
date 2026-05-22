<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();

        // Redirect admin users to admin dashboard
        if ($user && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        }

        // Redirect staff users to admin dashboard (limited access)
        if ($user && in_array('ROLE_STAFF', $user->getRoles(), true)) {
            return new RedirectResponse($this->urlGenerator->generate('staff_dashboard'));
        }

        // Default redirect for other users
        return new RedirectResponse($this->urlGenerator->generate('customer_homepage'));
    }
}

