<?php

namespace App\Tests\Service;

use App\Entity\Caisse;
use App\Entity\CaisseStickman;
use App\Entity\Stickman;
use App\Exception\OuvertureCaisseImpossibleException;
use App\Service\TirageCaisseService;
use PHPUnit\Framework\TestCase;

final class TirageCaisseServiceTest extends TestCase
{
    public function testNeTireQueDansLeContenuActifEtPondereDeLaCaisse(): void
    {
        $actif = $this->creerStickman('actif', true);
        $inactif = $this->creerStickman('inactif', false);
        $caisse = (new Caisse())
            ->addContenu((new CaisseStickman())->setStickman($actif)->setPoids(1))
            ->addContenu((new CaisseStickman())->setStickman($inactif)->setPoids(100));
        $service = new TirageCaisseService();

        self::assertCount(1, $service->contenusEligibles($caisse));
        self::assertSame($actif, $service->tirer($caisse));
    }

    public function testRefuseUneCaisseSansContenuEligible(): void
    {
        $this->expectException(OuvertureCaisseImpossibleException::class);

        (new TirageCaisseService())->tirer(new Caisse());
    }

    private function creerStickman(string $suffixe, bool $actif): Stickman
    {
        return (new Stickman())
            ->setNom('Stickman '.$suffixe)
            ->setSlug('stickman-'.$suffixe)
            ->setDescription('Stickman de test.')
            ->setImage('test.png')
            ->setRarete(1)
            ->setPv(10)
            ->setAttaque(2)
            ->setDefense(2)
            ->setStatutActif($actif);
    }
}
