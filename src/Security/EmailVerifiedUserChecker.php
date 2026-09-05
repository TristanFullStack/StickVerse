<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class EmailVerifiedUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (
            $user instanceof User
            && !$user->isEmailVerifie()
        ) {
            throw new CustomUserMessageAuthenticationException(
                'Confirme ton adresse e-mail avant de te connecter.',
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
