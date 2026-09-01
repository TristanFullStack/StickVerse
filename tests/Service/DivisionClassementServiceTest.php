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
        yield 'bronze' => [999, 'Bronze', 100];
        yield 'argent' => [1000, 'Argent', 200];
        yield 'or' => [1200, 'Or', 350];
        yield 'platine' => [1400, 'Platine', 550];
        yield 'diamant' => [1600, 'Diamant', 800];
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
            ->informationsPour(1150);

        self::assertSame('Argent', $informations['nom']);
        self::assertSame(75, $informations['progression']);
        self::assertSame(1200, $informations['prochainPalier']);
        self::assertSame(50, $informations['pointsRestants']);
    }
}
