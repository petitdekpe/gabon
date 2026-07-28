<?php

namespace App\Security;

use App\Entity\AdminUser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AdminUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof AdminUser && !$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Ce compte a été désactivé.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
