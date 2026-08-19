<?php

namespace App\Tests\Security\Voter;

use App\Entity\Combat;
use App\Entity\User;
use App\Security\Voter\CombatVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class CombatVoterTest extends TestCase
{
    public function testAutoriseLeJoueur1AConsulterLeCombat(): void
    {
        $joueur1 = new User();
        $combat = new Combat($joueur1);

        $resultat = (new CombatVoter())->vote(
            $this->creerToken($joueur1),
            $combat,
            [CombatVoter::CONSULTER],
        );

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $resultat,
        );
    }

    public function testAutoriseLeJoueur2AJouerLeCombat(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);

        $resultat = (new CombatVoter())->vote(
            $this->creerToken($joueur2),
            $combat,
            [CombatVoter::JOUER],
        );

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $resultat,
        );
    }

    public function testRefuseUnJoueurExterieurAuCombat(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();
        $intrus = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);

        $voter = new CombatVoter();
        $token = $this->creerToken($intrus);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                $combat,
                [CombatVoter::CONSULTER],
            ),
        );

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                $combat,
                [CombatVoter::JOUER],
            ),
        );
    }

    public function testRefuseUnUtilisateurAnonyme(): void
    {
        $combat = new Combat(new User());

        $resultat = (new CombatVoter())->vote(
            $this->creerToken(null),
            $combat,
            [CombatVoter::CONSULTER],
        );

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $resultat,
        );
    }

    public function testSabstientPourUnAttributInconnu(): void
    {
        $joueur = new User();
        $combat = new Combat($joueur);

        $resultat = (new CombatVoter())->vote(
            $this->creerToken($joueur),
            $combat,
            ['COMBAT_SUPPRIMER'],
        );

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $resultat,
        );
    }

    public function testSabstientPourUnMauvaisSujet(): void
    {
        $joueur = new User();

        $resultat = (new CombatVoter())->vote(
            $this->creerToken($joueur),
            $joueur,
            [CombatVoter::CONSULTER],
        );

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $resultat,
        );
    }

    private function creerToken(
        ?UserInterface $utilisateur,
    ): TokenInterface {
        $token = $this->createStub(TokenInterface::class);

        $token
            ->method('getUser')
            ->willReturn($utilisateur);

        return $token;
    }
}