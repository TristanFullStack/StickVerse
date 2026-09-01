<?php

namespace App\Tests\Entity;

use App\Entity\CollectionJeu;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CollectionJeuTest extends TestCase
{
    public function testEstDisponiblePendantSaPeriode(): void
    {
        $collection = (new CollectionJeu())
            ->setStatutActif(true)
            ->setDateDebut(new DateTimeImmutable('2026-08-01 00:00:00'))
            ->setDateFin(new DateTimeImmutable('2026-09-01 00:00:00'));

        self::assertTrue($collection->estDisponibleA(new DateTimeImmutable('2026-08-15 12:00:00')));
        self::assertFalse($collection->estDisponibleA(new DateTimeImmutable('2026-07-31 23:59:59')));
        self::assertFalse($collection->estDisponibleA(new DateTimeImmutable('2026-09-01 00:00:01')));
    }

    public function testUneCollectionInactiveResteIndisponible(): void
    {
        $collection = (new CollectionJeu())->setStatutActif(false);

        self::assertFalse($collection->estDisponibleA(new DateTimeImmutable()));
    }

    public function testEstTermineeUniquementApresSaDateDeFin(): void
    {
        $collection = (new CollectionJeu())
            ->setDateFin(new DateTimeImmutable('2026-09-01 00:00:00'));

        self::assertFalse($collection->estTermineeA(
            new DateTimeImmutable('2026-09-01 00:00:00'),
        ));
        self::assertTrue($collection->estTermineeA(
            new DateTimeImmutable('2026-09-01 00:00:01'),
        ));
    }
}
