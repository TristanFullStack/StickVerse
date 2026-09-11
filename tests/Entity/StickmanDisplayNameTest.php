<?php

namespace App\Tests\Entity;

use App\Entity\Stickman;
use PHPUnit\Framework\TestCase;

final class StickmanDisplayNameTest extends TestCase
{
    public function testPresentationAliasesNeverChangeStoredIdentity(): void
    {
        foreach (['Assasin' => 'Assassin', 'Archer2' => 'Archer II', 'Arbalétrier2' => 'Arbalétrier II', 'Garde' => 'Garde'] as $raw => $label) {
            $card = (new Stickman())->setNom($raw)->setSlug('stable-slug');
            self::assertSame($label, $card->getNomAffiche());
            self::assertSame($raw, $card->getNom());
            self::assertSame('stable-slug', $card->getSlug());
        }
    }
}
