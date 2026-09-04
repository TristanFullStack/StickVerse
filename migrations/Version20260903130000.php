<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Service\PassifCombatService;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use JsonException;

/**
 * Centralise le catalogue des passifs afin que leurs règles, valeurs et
 * contributions de puissance soient modifiables depuis l'administration.
 */
final class Version20260903130000 extends AbstractMigration
{
    /** @var array<string, array{nom: string, description: string, valeur: int, puissance: int, round?: int}> */
    private const PASSIFS_PERSONNALISES = [
        'bonus_attaque_pct' => ['nom' => 'Bonus ATQ permanent', 'description' => '+X % ATQ en permanence.', 'valeur' => 5, 'puissance' => 8],
        'bonus_defense_pct' => ['nom' => 'Bonus DEF permanent', 'description' => '+X % DEF en permanence.', 'valeur' => 5, 'puissance' => 8],
        'rage' => ['nom' => 'Rage', 'description' => '+10 % ATQ sous 40 % de PV.', 'valeur' => 10, 'puissance' => 12],
        'execution' => ['nom' => 'Exécution', 'description' => '+12 % ATQ contre une cible sous 30 % de PV.', 'valeur' => 12, 'puissance' => 14],
        'precision' => ['nom' => 'Précision', 'description' => 'Ignore 8 % de la défense adverse.', 'valeur' => 8, 'puissance' => 10],
        'perforation_i' => ['nom' => 'Perforation I', 'description' => 'Ignore 8 % de la défense adverse.', 'valeur' => 8, 'puissance' => 10],
        'perforation_ii' => ['nom' => 'Perforation II', 'description' => 'Ignore 12 % de la défense adverse.', 'valeur' => 12, 'puissance' => 16],
        'tir_disperse' => ['nom' => 'Tir dispersé', 'description' => '+8 % ATQ en attaque Split.', 'valeur' => 8, 'puissance' => 10],
        'protecteur' => ['nom' => 'Protecteur', 'description' => '+8 % DEF lorsqu’un allié est protégé.', 'valeur' => 8, 'puissance' => 10],
        'protection_juree' => ['nom' => 'Protection jurée', 'description' => '+10 % DEF lorsque la carte protège un allié pendant une double défense.', 'valeur' => 10, 'puissance' => 12],
        'rempart_leger' => ['nom' => 'Rempart léger', 'description' => '+8 % DEF lors d’une double défense.', 'valeur' => 8, 'puissance' => 10],
        'rempart' => ['nom' => 'Rempart', 'description' => '+12 % DEF lors d’une double défense.', 'valeur' => 12, 'puissance' => 14],
        'bastion' => ['nom' => 'Bastion', 'description' => '+14 % DEF lors d’une double défense.', 'valeur' => 14, 'puissance' => 16],
        'forteresse' => ['nom' => 'Forteresse', 'description' => '+10 % DEF lors d’une double défense.', 'valeur' => 10, 'puissance' => 12],
        'bouclier_arcanique' => ['nom' => 'Bouclier arcanique', 'description' => '+10 % DEF lors de la première défense.', 'valeur' => 10, 'puissance' => 12],
        'rune_defensive' => ['nom' => 'Rune défensive', 'description' => '+12 % DEF lors de la première défense.', 'valeur' => 12, 'puissance' => 14],
        'duelliste' => ['nom' => 'Duelliste', 'description' => '+10 % DEF lorsqu’une seule équipe adverse attaque.', 'valeur' => 10, 'puissance' => 12],
        'maitre_du_duel' => ['nom' => 'Maître du duel', 'description' => '+12 % DEF lorsqu’une seule équipe adverse attaque.', 'valeur' => 12, 'puissance' => 14],
        'assaut_coordonne' => ['nom' => 'Assaut coordonné', 'description' => '+8 % ATQ en attaque Focus.', 'valeur' => 8, 'puissance' => 10],
        'formation' => ['nom' => 'Formation', 'description' => '+6 % ATQ pendant les deux premiers rounds si les deux alliés sont vivants.', 'valeur' => 6, 'puissance' => 8, 'round' => 1],
        'commandement' => ['nom' => 'Commandement', 'description' => '+10 % ATQ pendant un round après la chute du partenaire.', 'valeur' => 10, 'puissance' => 12],
        'ordre_charge' => ['nom' => 'Ordre de charge', 'description' => '+6 % ATQ uniquement au début du combat, si les deux alliés sont vivants.', 'valeur' => 6, 'puissance' => 8],
        'heritage_general' => ['nom' => 'Héritage du général', 'description' => '+10 % ATQ après la chute du partenaire.', 'valeur' => 10, 'puissance' => 12],
        'furie' => ['nom' => 'Furie', 'description' => '+14 % ATQ sous 40 % de PV.', 'valeur' => 14, 'puissance' => 16],
        'surcharge' => ['nom' => 'Surcharge', 'description' => '+8 % ATQ du round 4 au round 8.', 'valeur' => 8, 'puissance' => 10, 'round' => 4],
        'tempete_divisee' => ['nom' => 'Tempête divisée', 'description' => '+10 % ATQ en attaque Split.', 'valeur' => 10, 'puissance' => 12],
        'apocalypse' => ['nom' => 'Apocalypse', 'description' => '+18 % ATQ à partir du round 10.', 'valeur' => 18, 'puissance' => 20, 'round' => 10],
        'autorite_imperiale' => ['nom' => 'Autorité impériale', 'description' => '+10 % ATQ après la chute du partenaire.', 'valeur' => 10, 'puissance' => 12],
    ];

    /** @return array<string, array{nom: string, description: string, type: string, valeur: int, puissance: int, round?: int}> */
    private function catalogue(): array
    {
        $catalogue = [];
        foreach ([PassifCombatService::TYPE_BONUS_ATTAQUE_POURCENTAGE, PassifCombatService::TYPE_BONUS_DEFENSE_POURCENTAGE, ...PassifCombatService::TYPES_CONTEXTUELS] as $type) {
            $catalogue[$type] = [
                'nom' => ucfirst(str_replace('_', ' ', $type)),
                'description' => 'Effet contextuel configurable depuis le CRUD des passifs.',
                'type' => $type,
                'valeur' => 0,
                'puissance' => 0,
            ];
        }
        foreach (self::PASSIFS_PERSONNALISES as $type => $donnees) {
            $catalogue[$type] = [
                'nom' => $donnees['nom'],
                'description' => $donnees['description'],
                'type' => $type,
                'valeur' => $donnees['valeur'],
                'puissance' => $donnees['puissance'],
                ...isset($donnees['round']) ? ['round' => $donnees['round']] : [],
            ];
        }

        return $catalogue;
    }

    public function getDescription(): string
    {
        return 'Crée le catalogue central des passifs et leur puissance manuelle';
    }

    /** @throws JsonException */
    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE passif ('
            .'id INT AUTO_INCREMENT NOT NULL, '
            .'nom VARCHAR(120) NOT NULL, '
            .'slug VARCHAR(120) NOT NULL, '
            .'description LONGTEXT NOT NULL, '
            .'type VARCHAR(80) NOT NULL, '
            .'valeur INT NOT NULL, '
            .'puissance INT DEFAULT 0 NOT NULL, '
            .'a_partir_round INT DEFAULT NULL, '
            .'statut_actif TINYINT(1) DEFAULT 1 NOT NULL, '
            .'UNIQUE INDEX UNIQ_PASSIF_SLUG (slug), '
            .'PRIMARY KEY(id)'
            .') DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB',
        );

        $catalogue = $this->catalogue();
        $idsParType = [];
        $prochainId = 1;
        foreach ($catalogue as $type => $passif) {
            $slug = str_replace('_', '-', $type);
            $idsParType[$type] = $prochainId++;
            $this->addSql(
                'INSERT INTO passif (nom, slug, description, type, valeur, puissance, a_partir_round, statut_actif) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $passif['nom'],
                    $slug,
                    $passif['description'],
                    $passif['type'],
                    $passif['valeur'],
                    $passif['puissance'],
                    $passif['round'] ?? null,
                    1,
                ],
            );
        }

        $stickmen = $this->connection->fetchAllAssociative('SELECT id, passifs FROM stickman WHERE passifs IS NOT NULL');
        foreach ($stickmen as $stickman) {
            $passifs = json_decode((string) $stickman['passifs'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($passifs)) {
                continue;
            }
            $normalises = [];
            foreach ($passifs as $snapshot) {
                if (!is_array($snapshot) || !isset($snapshot['type']) || !isset($catalogue[$snapshot['type']])) {
                    // Conserve un ancien snapshot personnalisé : le moteur
                    // l'ignorera tant qu'aucun type correspondant n'existe.
                    $normalises[] = $snapshot;
                    continue;
                }
                $type = (string) $snapshot['type'];
                $donnees = $catalogue[$type];
                $normalises[] = [
                    'id' => $idsParType[$type],
                    'nom' => $donnees['nom'],
                    'description' => $donnees['description'],
                    'type' => $type,
                    'valeur' => $donnees['valeur'],
                    'puissance' => $donnees['puissance'],
                    'actif' => true,
                    ...isset($donnees['round']) ? ['a_partir_round' => $donnees['round']] : [],
                ];
            }
            $this->addSql(
                'UPDATE stickman SET passifs = ? WHERE id = ?',
                [json_encode($normalises, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), (int) $stickman['id']],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE passif');
    }
}
