<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use JsonException;

final class Version20260903110000 extends AbstractMigration
{
    /**
     * Passifs de lancement : aucun effet n'est attribué aux cartes R1.
     * Les valeurs restent volontairement modestes pour préserver l'équilibre
     * actuel tout en donnant une identité aux cartes R2 à R5.
     *
     * @var array<string, list<array{nom: string, description: string, type: string, valeur: int, a_partir_round?: int}>>
     */
    private const PASSIFS_PAR_SLUG = [
        'arbaletrier' => [
            [
                'nom' => 'Tir précis',
                'description' => '+8 % ATQ.',
                'type' => 'bonus_attaque_pct',
                'valeur' => 8,
            ],
        ],
        'arbaletrier2' => [
            [
                'nom' => 'Tir de couverture',
                'description' => '+7 % ATQ.',
                'type' => 'bonus_attaque_pct',
                'valeur' => 7,
            ],
        ],
        'tireur' => [
            [
                'nom' => 'Œil vif',
                'description' => '+10 % ATQ.',
                'type' => 'bonus_attaque_pct',
                'valeur' => 10,
            ],
        ],
        'portebouclier' => [
            [
                'nom' => 'Bouclier levé',
                'description' => '+10 % DEF.',
                'type' => 'bonus_defense_pct',
                'valeur' => 10,
            ],
        ],
        'gardiennovice' => [
            [
                'nom' => 'Garde novice',
                'description' => '+8 % DEF.',
                'type' => 'bonus_defense_pct',
                'valeur' => 8,
            ],
        ],
        'capitaine' => [
            [
                'nom' => 'Ordre offensif',
                'description' => '+5 % ATQ.',
                'type' => 'bonus_attaque_pct',
                'valeur' => 5,
            ],
            [
                'nom' => 'Ordre défensif',
                'description' => '+5 % DEF.',
                'type' => 'bonus_defense_pct',
                'valeur' => 5,
            ],
        ],
        'guerrier' => [
            [
                'nom' => 'Endurance',
                'description' => '+8 % DEF.',
                'type' => 'bonus_defense_pct',
                'valeur' => 8,
            ],
        ],
        'archer' => [
            [
                'nom' => 'Visée',
                'description' => '+10 % ATQ.',
                'type' => 'bonus_attaque_pct',
                'valeur' => 10,
            ],
        ],
        'lancier' => [
            [
                'nom' => 'Allonge',
                'description' => '+8 % ATQ.',
                'type' => 'bonus_attaque_pct',
                'valeur' => 8,
            ],
        ],
        'tank' => [
            [
                'nom' => 'Rempart',
                'description' => '+12 % DEF.',
                'type' => 'bonus_defense_pct',
                'valeur' => 12,
            ],
        ],
        'assasin' => [
            [
                'nom' => 'Frappe vitale',
                'description' => '+12 % ATQ.',
                'type' => 'bonus_attaque_pct',
                'valeur' => 12,
            ],
        ],
        'mage' => [
            [
                'nom' => 'Canalisation',
                'description' => '+10 % ATQ.',
                'type' => 'bonus_attaque_pct',
                'valeur' => 10,
            ],
        ],
        'berserker' => [
            [
                'nom' => 'Furie',
                'description' => '+14 % ATQ.',
                'type' => 'bonus_attaque_pct',
                'valeur' => 14,
            ],
            [
                'nom' => 'Rage montante',
                'description' => '+8 % ATQ à partir du round 4.',
                'type' => 'bonus_attaque_pct',
                'valeur' => 8,
                'a_partir_round' => 4,
            ],
        ],
        'double-lancier' => [
            [
                'nom' => 'Double pointe',
                'description' => '+10 % ATQ.',
                'type' => 'bonus_attaque_pct',
                'valeur' => 10,
            ],
            [
                'nom' => 'Position solide',
                'description' => '+8 % DEF.',
                'type' => 'bonus_defense_pct',
                'valeur' => 8,
            ],
        ],
        'ultra-mage' => [
            [
                'nom' => 'Arcane supérieur',
                'description' => '+15 % ATQ.',
                'type' => 'bonus_attaque_pct',
                'valeur' => 15,
            ],
            [
                'nom' => 'Barrière arcanique',
                'description' => '+10 % DEF.',
                'type' => 'bonus_defense_pct',
                'valeur' => 10,
            ],
        ],
        'roi-stick' => [
            [
                'nom' => 'Autorité royale',
                'description' => '+10 % ATQ.',
                'type' => 'bonus_attaque_pct',
                'valeur' => 10,
            ],
            [
                'nom' => 'Armure royale',
                'description' => '+10 % DEF.',
                'type' => 'bonus_defense_pct',
                'valeur' => 10,
            ],
        ],
    ];

    public function getDescription(): string
    {
        return 'Attribue les passifs de lancement aux Stickmans R2 à R5';
    }

    /**
     * @throws JsonException
     */
    public function up(Schema $schema): void
    {
        foreach (self::PASSIFS_PAR_SLUG as $slug => $passifs) {
            $this->addSql(
                'UPDATE stickman SET passifs = ? WHERE slug = ?',
                [json_encode($passifs, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), $slug],
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(self::PASSIFS_PAR_SLUG) as $slug) {
            $this->addSql(
                'UPDATE stickman SET passifs = NULL WHERE slug = ?',
                [$slug],
            );
        }
    }
}
