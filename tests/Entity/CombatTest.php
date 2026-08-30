<?php

namespace App\Tests\Entity;

use App\Entity\Combat;
use App\Entity\User;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CombatTest extends KernelTestCase
{
    public function testValeursParDefautDuCombat(): void
    {
        $joueur1 = new User();

        $combat = new Combat($joueur1);

        self::assertSame(
            $joueur1,
            $combat->getJoueur1()
        );

        self::assertNull($combat->getJoueur2());
        self::assertNull($combat->getGagnant());
        self::assertFalse($combat->estPrive());
        self::assertFalse($combat->estEloAttribuee());

        self::assertSame(
            Combat::STATUT_EN_ATTENTE,
            $combat->getStatut()
        );

        self::assertSame(1, $combat->getNumeroRound());

        self::assertTrue($combat->estEnAttente());
        self::assertFalse($combat->estEnCours());
        self::assertFalse($combat->estTermine());

        self::assertNotNull($combat->getDateCreation());
        self::assertNotNull($combat->getDateMiseAJour());
    }

    public function testPeutRendreUnCombatPrive(): void
    {
        $combat = (new Combat(new User()))
            ->setPrive(true);

        self::assertTrue($combat->estPrive());
    }

    public function testLesDeuxJoueursConfirmentLeurPreparation(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();
        $combat = (new Combat($joueur1))
            ->setJoueur2($joueur2)
            ->setStatut(Combat::STATUT_EN_COURS)
            ->initialiserPreparation();

        self::assertTrue($combat->estEnPreparation());
        self::assertFalse($combat->estPretAJouer());
        self::assertFalse($combat->estPret($joueur1));
        self::assertFalse($combat->estPret($joueur2));

        $combat->confirmerPret($joueur1);

        self::assertTrue($combat->estPret($joueur1));
        self::assertFalse($combat->estPret($joueur2));
        self::assertTrue($combat->estEnPreparation());

        $combat->confirmerPret($joueur2);

        self::assertTrue($combat->estPretAJouer());
        self::assertFalse($combat->estEnPreparation());
    }

    public function testUneDoubleConfirmationNeRepoussePasLeDelai(): void
    {
        $joueur1 = new User();
        $combat = (new Combat($joueur1))
            ->setJoueur2(new User())
            ->setStatut(Combat::STATUT_EN_COURS)
            ->initialiserPreparation()
            ->confirmerPret($joueur1);
        $dateFixe = new \DateTimeImmutable('2026-08-30 12:00:00');
        $propriete = new \ReflectionProperty(
            Combat::class,
            'dateMiseAJour',
        );
        $propriete->setValue($combat, $dateFixe);

        $combat->confirmerPret($joueur1);

        self::assertSame($dateFixe, $combat->getDateMiseAJour());
        self::assertEquals(
            $dateFixe->modify('+5 minutes'),
            $combat->getDateExpirationPreparation(),
        );
    }

    public function testPasserAuRoundSuivant(): void
    {
        $combat = new Combat(new User());

        $combat->passerAuRoundSuivant();

        self::assertSame(2, $combat->getNumeroRound());
    }

    public function testRefuseUnStatutInvalide(): void
    {
        $combat = new Combat(new User());

        $this->expectException(
            InvalidArgumentException::class
        );

        $combat->setStatut('statut_triche');
    }

    public function testRefuseUnNumeroRoundInvalide(): void
    {
        $combat = new Combat(new User());

        $this->expectException(
            InvalidArgumentException::class
        );

        $combat->setNumeroRound(0);
    }

    public function testRefuseUnCombatContreSoiMeme(): void
    {
        self::bootKernel();

        $validator = static::getContainer()->get(
            ValidatorInterface::class
        );

        $joueur = new User();

        $combat = new Combat($joueur);
        $combat->setJoueur2($joueur);

        $violations = $validator->validate($combat);

        self::assertCount(1, $violations);

        self::assertSame(
            'joueur2',
            $violations[0]->getPropertyPath()
        );
    }

    public function testRefuseUnGagnantExterieurAuCombat(): void
    {
        self::bootKernel();

        $validator = static::getContainer()->get(
            ValidatorInterface::class
        );

        $joueur1 = new User();
        $joueur2 = new User();
        $intrus = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setGagnant($intrus);

        $violations = $validator->validate($combat);

        self::assertCount(1, $violations);

        self::assertSame(
            'gagnant',
            $violations[0]->getPropertyPath()
        );
    }
}
