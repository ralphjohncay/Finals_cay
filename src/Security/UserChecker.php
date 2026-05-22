<?php

namespace App\Security;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Users) {
            return;
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException(
                'Your account has been disabled. Please contact an administrator.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof Users) {
            return;
        }

        try {
            $this->em->refresh($user);
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            throw new CustomUserMessageAccountStatusException(
                'Your account is no longer available.'
            );
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException(
                'Your account has been disabled. Please contact an administrator.'
            );
        }
    }
}
