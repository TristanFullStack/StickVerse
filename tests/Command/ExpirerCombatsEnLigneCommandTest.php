<?php

namespace App\Tests\Command;

use App\Entity\Combat;
use App\Entity\PlanRoundCombat;
use App\Entity\User;
use App\Model\PlanCombat;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ExpirerCombatsEnLigneCommandTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = static::getContainer()->get(
            EntityManagerInterface::class
        );

        self::assertInstanceOf(
            EntityManagerInterface::class,
            $entityManager,
        );

        $this->entityManager = $entityManager;
        $connexion = $this->entityManager->getConnection();
        $nomBase = $connexion->fetchOne('SELECT DATABASE()');

        if (
            !is_string($nomBase)
            || !str_ends_with($nomBase, '_test')
        ) {
            throw new LogicException(
                'Le test de commande doit utiliser une base terminant par "_test".'
            );
        }

        $connexion->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $connexion = $this->entityManager->getConnection();

            if ($connexion->isTransactionActive()) {
                $connexion->rollBack();
            }

            $this->entityManager->clear();
        }

        parent::tearDown();
    }

    public function testExpireTousLesCombatsAbandonnes(): void
    {
        $joueur1 = $this->creerJoueur('joueur1');
        $joueur2 = $this->creerJoueur('joueur2');

        $attenteExpiree = new Combat($joueur1);
        $this->modifierDate(
            $attenteExpiree,
            'dateCreation',
            new DateTimeImmutable('-6 minutes'),
        );

        $attenteValide = new Combat($joueur2);

        $preparationExpiree = (new Combat($joueur1))
            ->setJoueur2($joueur2)
            ->setStatut(Combat::STATUT_EN_COURS)
            ->initialiserPreparation();
        $this->modifierDate(
            $preparationExpiree,
            'dateMiseAJour',
            new DateTimeImmutable('-6 minutes'),
        );

        $preparationAvecJoueurPret = (new Combat($joueur1))
            ->setJoueur2($joueur2)
            ->setStatut(Combat::STATUT_EN_COURS)
            ->initialiserPreparation()
            ->confirmerPret($joueur1);
        $this->modifierDate(
            $preparationAvecJoueurPret,
            'dateMiseAJour',
            new DateTimeImmutable('-6 minutes'),
        );

        $planExpire = (new Combat($joueur1))
            ->setJoueur2($joueur2)
            ->setStatut(Combat::STATUT_EN_COURS);
        $plan = new PlanRoundCombat(
            $planExpire,
            $joueur1,
            new PlanCombat('A', 'B', 'C', 'D'),
        );
        $this->modifierDate(
            $plan,
            'dateSoumission',
            new DateTimeImmutable('-6 minutes'),
        );

        foreach (
            [
                $attenteExpiree,
                $attenteValide,
                $preparationExpiree,
                $preparationAvecJoueurPret,
                $planExpire,
            ] as $combat
        ) {
            $this->entityManager->persist($combat);
        }

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        $application = new Application(self::$kernel);
        $commande = $application->find('app:combats:expirer');
        $testeur = new CommandTester($commande);

        self::assertSame(
            Command::SUCCESS,
            $testeur->execute([]),
        );

        $sortie = $testeur->getDisplay();

        self::assertStringContainsString(
            'Combats examinés : 5',
            $sortie,
        );
        self::assertStringContainsString(
            'Attentes annulées : 1',
            $sortie,
        );
        self::assertStringContainsString(
            'Préparations annulées : 1',
            $sortie,
        );
        self::assertStringContainsString(
            'Forfaits de préparation : 1',
            $sortie,
        );
        self::assertStringContainsString(
            'Forfaits de plan : 1',
            $sortie,
        );

        self::assertTrue($attenteExpiree->estAnnule());
        self::assertTrue($attenteValide->estEnAttente());
        self::assertTrue($preparationExpiree->estAnnule());
        self::assertTrue($preparationAvecJoueurPret->estForfait());
        self::assertSame(
            $joueur1,
            $preparationAvecJoueurPret->getGagnant(),
        );
        self::assertTrue($planExpire->estForfait());
        self::assertSame($joueur1, $planExpire->getGagnant());
    }

    private function creerJoueur(string $prefixe): User
    {
        $joueur = (new User())
            ->setEmail(
                $prefixe
                .'-j62-'
                .bin2hex(random_bytes(6))
                .'@example.com'
            )
            ->setPassword('mot-de-passe-test');

        $this->entityManager->persist($joueur);

        return $joueur;
    }

    private function modifierDate(
        object $objet,
        string $propriete,
        DateTimeImmutable $date,
    ): void {
        $reflection = new \ReflectionProperty(
            $objet,
            $propriete,
        );
        $reflection->setValue($objet, $date);
    }
}
