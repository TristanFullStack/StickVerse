<?php

namespace App\Security\Voter;

use App\Entity\Combat;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class CombatVoter extends Voter
{
    public const CONSULTER = 'COMBAT_CONSULTER';
    public const JOUER = 'COMBAT_JOUER';

    protected function supports(
        string $attribute,
        mixed $subject,
    ): bool {
        return in_array(
            $attribute,
            [
                self::CONSULTER,
                self::JOUER,
            ],
            true,
        ) && $subject instanceof Combat;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        $utilisateur = $token->getUser();

        if (!$utilisateur instanceof User) {
            $vote?->addReason(
                'L’utilisateur doit être connecté.'
            );

            return false;
        }

        if (!$subject instanceof Combat) {
            $vote?->addReason(
                'La ressource demandée n’est pas un combat.'
            );

            return false;
        }

        if (!$subject->estParticipant($utilisateur)) {
            $vote?->addReason(
                'L’utilisateur ne participe pas à ce combat.'
            );

            return false;
        }

        return match ($attribute) {
            self::CONSULTER,
            self::JOUER => true,
            default => false,
        };
    }
}