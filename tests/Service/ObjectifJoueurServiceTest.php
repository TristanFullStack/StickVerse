<?php

namespace App\Tests\Service;

use App\Entity\MouvementPieces;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Repository\MouvementPiecesRepository;
use App\Repository\UserRepository;
use App\Service\MouvementPiecesService;
use App\Service\ObjectifJoueurService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ObjectifJoueurServiceTest extends TestCase
{
    public function testConstruitLaProgressionDesObjectifs(): void
    {
        $joueur = new User();
        $combatRepository = $this->createStub(CombatRepository::class);
        $combatRepository
            ->method('calculerStatistiquesPourJoueur')
            ->willReturn([
                'total' => 2,
                'victoires' => 1,
                'defaites' => 1,
                'matchsNuls' => 0,
            ]);
        $mouvementRepository = $this->createStub(MouvementPiecesRepository::class);
        $mouvementRepository
            ->method('compterPourJoueurEtType')
            ->willReturn(1);

        $objectifs = $this->creerService(
            $joueur,
            $combatRepository,
            $mouvementRepository,
        )->construire($joueur);

        self::assertSame(3, count($objectifs));
        self::assertSame(1, $objectifs[0]['progression']);
        self::assertTrue($objectifs[0]['disponible']);
        self::assertSame(2, $objectifs[1]['progression']);
        self::assertSame(1, $objectifs[2]['progression']);
    }

    public function testReclameUnObjectifEtEnregistreLeMouvement(): void
    {
        $joueur = $this->joueurAvecId(72);
        $combatRepository = $this->createStub(CombatRepository::class);
        $combatRepository
            ->method('calculerStatistiquesPourJoueur')
            ->willReturn([
                'total' => 1,
                'victoires' => 1,
                'defaites' => 0,
                'matchsNuls' => 0,
            ]);
        $mouvementRepository = $this->createStub(MouvementPiecesRepository::class);
        $mouvementRepository
            ->method('compterPourJoueurEtType')
            ->willReturn(0);
        $mouvementEntityManager = $this->createMock(EntityManagerInterface::class);
        $mouvementEntityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(MouvementPieces::class));
        $mouvementService = new MouvementPiecesService($mouvementEntityManager);

        $service = $this->creerService(
            $joueur,
            $combatRepository,
            $mouvementRepository,
            $mouvementService,
        );

        self::assertSame(50, $service->reclamer(
            $joueur,
            ObjectifJoueurService::OBJECTIF_PREMIER_COMBAT,
        ));
        self::assertSame(1050, $joueur->getPieces());
        self::assertTrue($joueur->aReclameObjectif(
            ObjectifJoueurService::OBJECTIF_PREMIER_COMBAT,
        ));
    }

    public function testRefuseUnObjectifNonDisponible(): void
    {
        $joueur = $this->joueurAvecId(73);
        $combatRepository = $this->createStub(CombatRepository::class);
        $combatRepository
            ->method('calculerStatistiquesPourJoueur')
            ->willReturn([
                'total' => 0,
                'victoires' => 0,
                'defaites' => 0,
                'matchsNuls' => 0,
            ]);
        $mouvementRepository = $this->createStub(MouvementPiecesRepository::class);

        self::assertSame(0, $this->creerService(
            $joueur,
            $combatRepository,
            $mouvementRepository,
        )->reclamer(
            $joueur,
            ObjectifJoueurService::OBJECTIF_PREMIER_COMBAT,
        ));
        self::assertSame(1000, $joueur->getPieces());
    }

    private function creerService(
        User $joueur,
        CombatRepository $combatRepository,
        MouvementPiecesRepository $mouvementRepository,
        ?MouvementPiecesService $mouvementService = null,
    ): ObjectifJoueurService {
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository
            ->method('trouverAvecVerrouEcriture')
            ->willReturn($joueur);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager
            ->method('wrapInTransaction')
            ->willReturnCallback(
                static fn (callable $operation): mixed => $operation()
            );

        return new ObjectifJoueurService(
            $combatRepository,
            $mouvementRepository,
            $userRepository,
            $entityManager,
            $mouvementService,
        );
    }

    private function joueurAvecId(int $id): User
    {
        $joueur = new User();
        $reflection = new \ReflectionProperty(User::class, 'id');
        $reflection->setValue($joueur, $id);

        return $joueur;
    }
}
