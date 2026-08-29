<?php

namespace App\Tests\Service;

use App\Entity\Caisse;
use App\Entity\CaisseStickman;
use App\Entity\Stickman;
use App\Repository\CaisseRepository;
use App\Repository\CaisseStickmanRepository;
use App\Repository\StickmanRepository;
use App\Service\CatalogueJeuService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;

final class CatalogueJeuServiceTest extends TestCase
{
    public function testExporteUnCatalogueDeterministeSansIdentifiantsDeBase(): void
    {
        $archer = $this->creerStickman('archer', 'Archer', 4, 4, 1);
        $guerrier = $this->creerStickman('guerrier', 'Guerrier', 5, 2, 2);

        $caisse = (new Caisse())
            ->setSlug('caisse-origine')
            ->setNom('Caisse Origine')
            ->setDescription('La caisse de départ.')
            ->setImage('caisse-origine.png')
            ->setPrix(120)
            ->setStatutActif(true);

        $caisse->addContenu(
            (new CaisseStickman())
                ->setStickman($guerrier)
                ->setPoids(60),
        );
        $caisse->addContenu(
            (new CaisseStickman())
                ->setStickman($archer)
                ->setPoids(40),
        );

        $stickmanRepository = $this->createStub(StickmanRepository::class);
        $stickmanRepository->method('findBy')->willReturn([$archer, $guerrier]);

        $caisseRepository = $this->createStub(CaisseRepository::class);
        $caisseRepository->method('findBy')->willReturn([$caisse]);

        $catalogue = (new CatalogueJeuService(
            $stickmanRepository,
            $caisseRepository,
            $this->createStub(CaisseStickmanRepository::class),
            $this->createStub(EntityManagerInterface::class),
        ))->exporter();

        self::assertSame(1, $catalogue['version']);
        self::assertSame('archer', $catalogue['stickmans'][0]['slug']);
        self::assertSame('guerrier', $catalogue['stickmans'][1]['slug']);
        self::assertArrayNotHasKey('id', $catalogue['stickmans'][0]);
        self::assertSame(
            [
                ['stickman' => 'archer', 'poids' => 40],
                ['stickman' => 'guerrier', 'poids' => 60],
            ],
            $catalogue['caisses'][0]['contenus'],
        );
    }

    public function testRefuseLesSlugsStickmansEnDouble(): void
    {
        $stickmanRepository = $this->createStub(StickmanRepository::class);
        $stickmanRepository
            ->method('findBy')
            ->willReturn([
                $this->creerStickman('archer', 'Archer commun', 4, 4, 1),
                $this->creerStickman('archer', 'Archer rare', 6, 5, 2),
            ]);

        $caisseRepository = $this->createStub(CaisseRepository::class);
        $caisseRepository->method('findBy')->willReturn([]);

        $service = new CatalogueJeuService(
            $stickmanRepository,
            $caisseRepository,
            $this->createStub(CaisseStickmanRepository::class),
            $this->createStub(EntityManagerInterface::class),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Export impossible : les slugs Stickmans suivants sont en double : archer.',
        );

        $service->exporter();
    }

    public function testImporteUnNouveauCatalogueDansUneTransaction(): void
    {
        $fichier = tempnam(sys_get_temp_dir(), 'stickverse-catalogue-');
        self::assertNotFalse($fichier);

        file_put_contents(
            $fichier,
            json_encode(
                [
                    'version' => 1,
                    'stickmans' => [[
                        'slug' => 'recrue',
                        'nom' => 'Recrue',
                        'description' => 'Une jeune recrue.',
                        'image' => '11-Recrue.png',
                        'rarete' => 1,
                        'pv' => 60,
                        'attaque' => 12,
                        'defense' => 14,
                        'actif' => true,
                    ]],
                    'caisses' => [[
                        'slug' => 'caisse-origine',
                        'nom' => 'Caisse Origine',
                        'description' => 'La caisse de départ.',
                        'image' => 'caisse-origine.png',
                        'prix' => 120,
                        'actif' => true,
                        'contenus' => [[
                            'stickman' => 'recrue',
                            'poids' => 100,
                        ]],
                    ]],
                ],
                JSON_THROW_ON_ERROR,
            ),
        );

        $stickmanRepository = $this->createStub(StickmanRepository::class);
        $stickmanRepository->method('findBy')->willReturn([]);

        $caisseRepository = $this->createStub(CaisseRepository::class);
        $caisseRepository->method('findOneBy')->willReturn(null);

        $associationRepository = $this->createStub(CaisseStickmanRepository::class);
        $associationRepository->method('findOneBy')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::exactly(3))
            ->method('persist');
        $entityManager
            ->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(
                static fn (callable $transaction): mixed => $transaction(),
            );

        try {
            $totaux = (new CatalogueJeuService(
                $stickmanRepository,
                $caisseRepository,
                $associationRepository,
                $entityManager,
            ))->importerDepuisFichier($fichier);
        } finally {
            unlink($fichier);
        }

        self::assertSame(
            ['stickmans' => 1, 'caisses' => 1, 'associations' => 1],
            $totaux,
        );
    }

    public function testRefuseUneReferenceVersUnStickmanAbsentDuCatalogue(): void
    {
        $fichier = tempnam(sys_get_temp_dir(), 'stickverse-catalogue-');
        self::assertNotFalse($fichier);

        file_put_contents(
            $fichier,
            <<<'JSON'
{
    "version": 1,
    "stickmans": [],
    "caisses": [
        {
            "slug": "caisse-origine",
            "nom": "Caisse Origine",
            "description": "La caisse de départ.",
            "image": "caisse-origine.png",
            "prix": 120,
            "actif": true,
            "contenus": [{"stickman": "inconnu", "poids": 100}]
        }
    ]
}
JSON,
        );

        $service = new CatalogueJeuService(
            $this->createStub(StickmanRepository::class),
            $this->createStub(CaisseRepository::class),
            $this->createStub(CaisseStickmanRepository::class),
            $this->createStub(EntityManagerInterface::class),
        );

        try {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage(
                'référence le Stickman inconnu "inconnu"',
            );
            $service->importerDepuisFichier($fichier);
        } finally {
            unlink($fichier);
        }
    }

    public function testVerifieUneInstallationVideCorrespondantAuCatalogue(): void
    {
        $fichier = tempnam(sys_get_temp_dir(), 'stickverse-catalogue-');
        self::assertNotFalse($fichier);
        file_put_contents(
            $fichier,
            '{"version":1,"stickmans":[],"caisses":[]}',
        );

        $stickmanRepository = $this->createStub(StickmanRepository::class);
        $stickmanRepository->method('findBy')->willReturn([]);

        $caisseRepository = $this->createStub(CaisseRepository::class);
        $caisseRepository->method('findBy')->willReturn([]);

        try {
            $totaux = (new CatalogueJeuService(
                $stickmanRepository,
                $caisseRepository,
                $this->createStub(CaisseStickmanRepository::class),
                $this->createStub(EntityManagerInterface::class),
            ))->verifierInstallationDepuisFichier(
                $fichier,
                sys_get_temp_dir(),
            );
        } finally {
            unlink($fichier);
        }

        self::assertSame(
            ['stickmans' => 0, 'caisses' => 0, 'associations' => 0, 'images' => 0],
            $totaux,
        );
    }

    public function testRefuseUneInstallationDontUneImageEstAbsente(): void
    {
        $fichier = tempnam(sys_get_temp_dir(), 'stickverse-catalogue-');
        self::assertNotFalse($fichier);

        $stickman = $this->creerStickman('recrue', 'Recrue', 60, 12, 14);

        file_put_contents(
            $fichier,
            json_encode(
                [
                    'version' => 1,
                    'stickmans' => [[
                        'slug' => 'recrue',
                        'nom' => 'Recrue',
                        'description' => 'Description de Recrue.',
                        'image' => 'recrue.png',
                        'rarete' => 1,
                        'pv' => 60,
                        'attaque' => 12,
                        'defense' => 14,
                        'actif' => true,
                    ]],
                    'caisses' => [],
                ],
                JSON_THROW_ON_ERROR,
            ),
        );

        $stickmanRepository = $this->createStub(StickmanRepository::class);
        $stickmanRepository->method('findBy')->willReturn([$stickman]);

        $caisseRepository = $this->createStub(CaisseRepository::class);
        $caisseRepository->method('findBy')->willReturn([]);

        $service = new CatalogueJeuService(
            $stickmanRepository,
            $caisseRepository,
            $this->createStub(CaisseStickmanRepository::class),
            $this->createStub(EntityManagerInterface::class),
        );

        try {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage(
                'L’image du Stickman "recrue" est introuvable',
            );
            $service->verifierInstallationDepuisFichier(
                $fichier,
                sys_get_temp_dir(),
            );
        } finally {
            unlink($fichier);
        }
    }

    private function creerStickman(
        string $slug,
        string $nom,
        int $pv,
        int $attaque,
        int $defense,
    ): Stickman {
        return (new Stickman())
            ->setSlug($slug)
            ->setNom($nom)
            ->setDescription(sprintf('Description de %s.', $nom))
            ->setImage(sprintf('%s.png', $slug))
            ->setRarete(1)
            ->setPv($pv)
            ->setAttaque($attaque)
            ->setDefense($defense)
            ->setStatutActif(true);
    }
}
