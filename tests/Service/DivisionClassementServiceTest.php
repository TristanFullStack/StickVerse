<?php

namespace App\Tests\Service;

use App\Service\DivisionClassementService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DivisionClassementServiceTest extends TestCase
{
    /**
     * @return iterable<string, array{int, string, int}>
     */
    public static function fournirDivisions(): iterable
    {
        yield 'eclaireur' => [500, 'Éclaireur', 1000];
        yield 'sentinelle' => [501, 'Sentinelle', 5000];
        yield 'stratege' => [1001, 'Stratège', 10000];
        yield 'champion' => [1501, 'Champion', 50000];
        yield 'legende' => [2001, 'Légende', 100000];
    }

    #[DataProvider('fournirDivisions')]
    public function testDetermineLaDivisionEtSaRecompense(
        int $elo,
        string $nom,
        int $recompense,
    ): void {
        $informations = (new DivisionClassementService())
            ->informationsPour($elo);

        self::assertSame($nom, $informations['nom']);
        self::assertSame($recompense, $informations['recompense']);
    }

    public function testIndiqueLaProgressionVersLePalierSuivant(): void
    {
        $informations = (new DivisionClassementService())
            ->informationsPour(1200);

        self::assertSame('Stratège', $informations['nom']);
        self::assertSame(39, $informations['progression']);
        self::assertSame(1501, $informations['prochainPalier']);
        self::assertSame(301, $informations['pointsRestants']);
    }

    public function testLePalier2500TermineLaProgressionDeLaLegende(): void
    {
        $informations = (new DivisionClassementService())
            ->informationsPour(2500);

        self::assertSame('Légende', $informations['nom']);
        self::assertSame(100, $informations['progression']);
        self::assertNull($informations['prochainPalier']);
        self::assertSame(0, $informations['pointsRestants']);
    }
}
