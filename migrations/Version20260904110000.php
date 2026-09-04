<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * J98 : réaligne les statistiques des cartes sur les fourchettes de puissance
 * officielles de leur rareté.
 *
 * Les passifs restent inchangés : leur valeur de puissance est déjà intégrée
 * au score et les effets de combat ne sont donc pas modifiés par ce réglage.
 */
final class Version20260904110000 extends AbstractMigration
{
    /**
     * @var array<string, array{pv: int, attaque: int, defense: int}>
     */
    private const STATISTIQUES = [
        'arbaletrier' => ['pv' => 90, 'attaque' => 30, 'defense' => 15],
        'arbaletrier2' => ['pv' => 90, 'attaque' => 29, 'defense' => 18],
        'archer' => ['pv' => 240, 'attaque' => 77, 'defense' => 45],
        'assasin' => ['pv' => 180, 'attaque' => 90, 'defense' => 24],
        'bagarreur' => ['pv' => 80, 'attaque' => 17, 'defense' => 13],
        'eclaireur' => ['pv' => 65, 'attaque' => 17, 'defense' => 16],
        'fantassin' => ['pv' => 60, 'attaque' => 16, 'defense' => 18],
        'frondeur' => ['pv' => 60, 'attaque' => 18, 'defense' => 15],
        'gardiennovice' => ['pv' => 120, 'attaque' => 15, 'defense' => 31],
        'guerrier' => ['pv' => 320, 'attaque' => 50, 'defense' => 68],
        'lancier' => ['pv' => 280, 'attaque' => 61, 'defense' => 58],
        'mage' => ['pv' => 240, 'attaque' => 42, 'defense' => 88],
        'piquier' => ['pv' => 78, 'attaque' => 16, 'defense' => 15],
        'portebouclier' => ['pv' => 95, 'attaque' => 10, 'defense' => 30],
        'tireur' => ['pv' => 80, 'attaque' => 34, 'defense' => 9],
        'tank' => ['pv' => 400, 'attaque' => 30, 'defense' => 80],
        'ultra-mage' => ['pv' => 250, 'attaque' => 140, 'defense' => 45],
    ];

    public function getDescription(): string
    {
        return 'Réaligne les puissances de cartes sur les fourchettes R1 à R5';
    }

    public function up(Schema $schema): void
    {
        foreach (self::STATISTIQUES as $slug => $statistiques) {
            $this->addSql(
                'UPDATE stickman SET pv = ?, attaque = ?, defense = ? WHERE slug = ?',
                [
                    $statistiques['pv'],
                    $statistiques['attaque'],
                    $statistiques['defense'],
                    $slug,
                ],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Les anciennes statistiques ne sont pas restaurées automatiquement :
        // elles ne respectaient pas les règles de rareté désormais publiques.
    }
}
