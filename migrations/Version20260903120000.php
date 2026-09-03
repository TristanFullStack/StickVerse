<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use JsonException;

/**
 * Remplace les bonus génériques de lancement par des passifs lisibles et
 * contextuels. Les cartes R1 restent volontairement sans passif.
 */
final class Version20260903120000 extends AbstractMigration
{
    /**
     * @var array<string, list<array{nom: string, description: string, type: string, valeur: int, a_partir_round?: int}>>
     */
    private const PASSIFS_PAR_SLUG = [
        'arbaletrier' => [[
            'nom' => 'Précision',
            'description' => 'Ignore 6 % de la défense adverse.',
            'type' => 'precision',
            'valeur' => 6,
        ]],
        'arbaletrier2' => [[
            'nom' => 'Perforation I',
            'description' => 'Ignore 8 % de la défense adverse.',
            'type' => 'perforation_i',
            'valeur' => 8,
        ]],
        'tireur' => [[
            'nom' => 'Tir dispersé',
            'description' => '+8 % ATQ lorsque les deux groupes visent des cibles différentes.',
            'type' => 'tir_disperse',
            'valeur' => 8,
        ]],
        'portebouclier' => [[
            'nom' => 'Protecteur',
            'description' => '+10 % DEF lorsqu’un allié est protégé.',
            'type' => 'protecteur',
            'valeur' => 10,
        ]],
        'gardiennovice' => [[
            'nom' => 'Rempart léger',
            'description' => '+8 % DEF lors d’une double défense.',
            'type' => 'rempart_leger',
            'valeur' => 8,
        ]],
        'capitaine' => [
            [
                'nom' => 'Commandement',
                'description' => '+6 % ATQ lorsque le partenaire est K.O.',
                'type' => 'commandement',
                'valeur' => 6,
            ],
            [
                'nom' => 'Formation',
                'description' => '+6 % ATQ tant que le partenaire est vivant.',
                'type' => 'formation',
                'valeur' => 6,
            ],
        ],
        'guerrier' => [[
            'nom' => 'Rage',
            'description' => '+10 % ATQ sous 40 % de PV.',
            'type' => 'rage',
            'valeur' => 10,
        ]],
        'archer' => [[
            'nom' => 'Précision',
            'description' => 'Ignore 8 % de la défense adverse.',
            'type' => 'precision',
            'valeur' => 8,
        ]],
        'lancier' => [[
            'nom' => 'Assaut coordonné',
            'description' => '+8 % ATQ en attaque Focus.',
            'type' => 'assaut_coordonne',
            'valeur' => 8,
        ]],
        'tank' => [[
            'nom' => 'Rempart',
            'description' => '+12 % DEF lors d’une double défense.',
            'type' => 'rempart',
            'valeur' => 12,
        ]],
        'assasin' => [[
            'nom' => 'Exécution',
            'description' => '+12 % ATQ contre une cible sous 30 % de PV.',
            'type' => 'execution',
            'valeur' => 12,
        ]],
        'mage' => [[
            'nom' => 'Bouclier arcanique',
            'description' => '+10 % DEF lors de la première défense.',
            'type' => 'bouclier_arcanique',
            'valeur' => 10,
        ]],
        'berserker' => [
            [
                'nom' => 'Furie',
                'description' => '+14 % ATQ sous 40 % de PV.',
                'type' => 'furie',
                'valeur' => 14,
            ],
            [
                'nom' => 'Surcharge',
                'description' => '+8 % ATQ du round 4 au round 8.',
                'type' => 'surcharge',
                'valeur' => 8,
                'a_partir_round' => 4,
            ],
        ],
        'double-lancier' => [
            [
                'nom' => 'Tempête divisée',
                'description' => '+10 % ATQ en attaque Split.',
                'type' => 'tempete_divisee',
                'valeur' => 10,
            ],
            [
                'nom' => 'Forteresse',
                'description' => '+10 % DEF lors d’une double défense.',
                'type' => 'forteresse',
                'valeur' => 10,
            ],
        ],
        'ultra-mage' => [
            [
                'nom' => 'Apocalypse',
                'description' => '+18 % ATQ à partir du round 10.',
                'type' => 'apocalypse',
                'valeur' => 18,
                'a_partir_round' => 10,
            ],
            [
                'nom' => 'Rune défensive',
                'description' => '+12 % DEF lors de la première défense.',
                'type' => 'rune_defensive',
                'valeur' => 12,
            ],
        ],
        'roi-stick' => [
            [
                'nom' => 'Autorité impériale',
                'description' => '+10 % ATQ lorsque le partenaire est K.O.',
                'type' => 'autorite_imperiale',
                'valeur' => 10,
            ],
            [
                'nom' => 'Maître du duel',
                'description' => '+12 % DEF lorsqu’une seule équipe adverse attaque la cible.',
                'type' => 'maitre_du_duel',
                'valeur' => 12,
            ],
        ],
    ];

    public function getDescription(): string
    {
        return 'Attribue des passifs contextuels aux Stickmans R2 à R5';
    }

    /** @throws JsonException */
    public function up(Schema $schema): void
    {
        foreach (self::PASSIFS_PAR_SLUG as $slug => $passifs) {
            $this->addSql(
                'UPDATE stickman SET passifs = ? WHERE slug = ? AND rarete > 1',
                [json_encode($passifs, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), $slug],
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(self::PASSIFS_PAR_SLUG) as $slug) {
            $this->addSql(
                'UPDATE stickman SET passifs = NULL WHERE slug = ? AND rarete > 1',
                [$slug],
            );
        }
    }
}
