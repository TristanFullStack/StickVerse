<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use JsonException;

/**
 * J97 : les passifs deviennent des choix stratégiques visibles.
 *
 * Les valeurs sont centralisées dans la table passif : un prochain réglage
 * depuis l'administration sera propagé aux snapshots utilisés par les
 * combats. Les conditions restent interprétées par PassifCombatService.
 */
final class Version20260904100000 extends AbstractMigration
{
    /**
     * @var array<string, array{nom: string, description: string, valeur: int, puissance: int, round?: int}>
     */
    private const EFFETS = [
        'bonus_attaque_pct' => ['nom' => 'Bonus ATQ permanent', 'description' => '+18 % ATQ en permanence.', 'valeur' => 18, 'puissance' => 28],
        'bonus_defense_pct' => ['nom' => 'Bonus DEF permanent', 'description' => '+18 % DEF en permanence.', 'valeur' => 18, 'puissance' => 28],
        'rage' => ['nom' => 'Rage', 'description' => '+28 % ATQ lorsque la carte passe sous 40 % de PV.', 'valeur' => 28, 'puissance' => 34],
        'execution' => ['nom' => 'Exécution', 'description' => '+42 % ATQ contre une cible sous 30 % de PV.', 'valeur' => 42, 'puissance' => 48],
        'precision' => ['nom' => 'Précision', 'description' => 'Ignore 25 % de la défense adverse.', 'valeur' => 25, 'puissance' => 30],
        'protecteur' => ['nom' => 'Protecteur', 'description' => '+30 % DEF lorsque la carte protège un allié.', 'valeur' => 30, 'puissance' => 35],
        'rempart' => ['nom' => 'Rempart', 'description' => '+34 % DEF lors d’une double défense.', 'valeur' => 34, 'puissance' => 40],
        'assaut_coordonne' => ['nom' => 'Assaut coordonné', 'description' => '+28 % ATQ en attaque Focus.', 'valeur' => 28, 'puissance' => 34],
        'tir_disperse' => ['nom' => 'Tir dispersé', 'description' => '+26 % ATQ en attaque Split.', 'valeur' => 26, 'puissance' => 32],
        'bouclier_arcanique' => ['nom' => 'Bouclier arcanique', 'description' => '+30 % DEF lors de la première défense.', 'valeur' => 30, 'puissance' => 36],
        'duelliste' => ['nom' => 'Duelliste', 'description' => '+32 % DEF lorsqu’une seule équipe adverse attaque.', 'valeur' => 32, 'puissance' => 38],
        'commandement' => ['nom' => 'Commandement', 'description' => '+36 % ATQ après la chute du partenaire.', 'valeur' => 36, 'puissance' => 40],
        'formation' => ['nom' => 'Formation', 'description' => '+24 % ATQ pendant les deux premiers rounds si les deux alliés sont vivants.', 'valeur' => 24, 'puissance' => 28, 'round' => 1],
        'opportuniste' => ['nom' => 'Opportuniste', 'description' => '+40 % ATQ contre une cible sous 25 % de PV.', 'valeur' => 40, 'puissance' => 46],
        'protection' => ['nom' => 'Protection', 'description' => '+32 % DEF lorsque la carte protège un allié.', 'valeur' => 32, 'puissance' => 38],
        'duel' => ['nom' => 'Duel', 'description' => '+38 % DEF lorsqu’une seule équipe adverse attaque.', 'valeur' => 38, 'puissance' => 44],
        'mobilite' => ['nom' => 'Mobilité', 'description' => '+24 % ATQ en attaque Split.', 'valeur' => 24, 'puissance' => 30],
        'rempart_leger' => ['nom' => 'Rempart léger', 'description' => '+24 % DEF lors d’une double défense.', 'valeur' => 24, 'puissance' => 30],
        'perforation_i' => ['nom' => 'Perforation I', 'description' => 'Ignore 22 % de la défense adverse.', 'valeur' => 22, 'puissance' => 28],
        'sang_froid' => ['nom' => 'Sang-froid', 'description' => '+34 % DEF sous 40 % de PV.', 'valeur' => 34, 'puissance' => 40],
        'sentinelle' => ['nom' => 'Sentinelle', 'description' => '+28 % DEF lorsque la carte protège un allié.', 'valeur' => 28, 'puissance' => 34],
        'furie' => ['nom' => 'Furie', 'description' => '+40 % ATQ sous 40 % de PV.', 'valeur' => 40, 'puissance' => 48],
        'perforation_ii' => ['nom' => 'Perforation II', 'description' => 'Ignore 34 % de la défense adverse.', 'valeur' => 34, 'puissance' => 40],
        'barriere' => ['nom' => 'Barrière', 'description' => '+38 % DEF lors de la première défense.', 'valeur' => 38, 'puissance' => 44],
        'charge_groupee' => ['nom' => 'Charge groupée', 'description' => '+32 % ATQ en attaque Focus.', 'valeur' => 32, 'puissance' => 38],
        'chasseur' => ['nom' => 'Chasseur', 'description' => '+42 % ATQ contre une cible sous 30 % de PV.', 'valeur' => 42, 'puissance' => 48],
        'bastion' => ['nom' => 'Bastion', 'description' => '+40 % DEF lors d’une double défense.', 'valeur' => 40, 'puissance' => 46],
        'riposte' => ['nom' => 'Riposte', 'description' => '+36 % DEF lorsqu’une seule équipe adverse attaque.', 'valeur' => 36, 'puissance' => 42],
        'serment' => ['nom' => 'Serment', 'description' => '+34 % DEF lorsque la carte protège un allié.', 'valeur' => 34, 'puissance' => 40],
        'combustion' => ['nom' => 'Combustion', 'description' => '+38 % ATQ sous 40 % de PV.', 'valeur' => 38, 'puissance' => 45],
        'predateur' => ['nom' => 'Prédateur', 'description' => '+45 % ATQ contre une cible sous 30 % de PV.', 'valeur' => 45, 'puissance' => 50],
        'rune_defensive' => ['nom' => 'Rune défensive', 'description' => '+38 % DEF lors de la première défense.', 'valeur' => 38, 'puissance' => 44],
        'combat_singulier' => ['nom' => 'Combat singulier', 'description' => '+42 % DEF lorsqu’une seule équipe adverse attaque.', 'valeur' => 42, 'puissance' => 48],
        'resilience' => ['nom' => 'Résilience', 'description' => '+36 % DEF sous 35 % de PV.', 'valeur' => 36, 'puissance' => 42],
        'precision_spectrale' => ['nom' => 'Précision spectrale', 'description' => 'Ignore 38 % de la défense adverse.', 'valeur' => 38, 'puissance' => 44],
        'forteresse' => ['nom' => 'Forteresse', 'description' => '+35 % DEF lors d’une double défense.', 'valeur' => 35, 'puissance' => 42],
        'maitre_du_duel' => ['nom' => 'Maître du duel', 'description' => '+45 % DEF lorsqu’une seule équipe adverse attaque.', 'valeur' => 45, 'puissance' => 50],
        'tempete_divisee' => ['nom' => 'Tempête divisée', 'description' => '+34 % ATQ en attaque Split.', 'valeur' => 34, 'puissance' => 40],
        'egide' => ['nom' => 'Égide', 'description' => '+38 % DEF lorsque la carte protège un allié.', 'valeur' => 38, 'puissance' => 44],
        'autorite_imperiale' => ['nom' => 'Autorité impériale', 'description' => '+36 % ATQ après la chute du partenaire.', 'valeur' => 36, 'puissance' => 42],
        'moisson' => ['nom' => 'Moisson', 'description' => '+44 % ATQ contre une cible sous 25 % de PV.', 'valeur' => 44, 'puissance' => 48],
        'citadelle' => ['nom' => 'Citadelle', 'description' => '+44 % DEF lors d’une double défense.', 'valeur' => 44, 'puissance' => 50],
        'percee_aube' => ['nom' => 'Percée de l’aube', 'description' => '+28 % ATQ pendant les trois premiers rounds.', 'valeur' => 28, 'puissance' => 34, 'round' => 1],
        'mur_aube' => ['nom' => 'Mur de l’aube', 'description' => '+32 % DEF pendant les trois premiers rounds.', 'valeur' => 32, 'puissance' => 38, 'round' => 1],
        'blitz' => ['nom' => 'Blitz', 'description' => '+35 % ATQ au premier round uniquement.', 'valeur' => 35, 'puissance' => 38, 'round' => 1],
        'premier_sang' => ['nom' => 'Premier sang', 'description' => '+30 % ATQ au premier round uniquement.', 'valeur' => 30, 'puissance' => 32, 'round' => 1],
        'fenetre_tactique' => ['nom' => 'Fenêtre tactique', 'description' => '+42 % ATQ du round 4 au round 8.', 'valeur' => 42, 'puissance' => 48, 'round' => 4],
        'verrouillage' => ['nom' => 'Verrouillage', 'description' => '+40 % DEF du round 4 au round 8.', 'valeur' => 40, 'puissance' => 46, 'round' => 4],
        'surcharge' => ['nom' => 'Surcharge', 'description' => '+34 % ATQ du round 4 au round 8.', 'valeur' => 34, 'puissance' => 40, 'round' => 4],
        'fortification' => ['nom' => 'Fortification', 'description' => '+44 % DEF du round 4 au round 8.', 'valeur' => 44, 'puissance' => 48, 'round' => 4],
        'crepuscule' => ['nom' => 'Crépuscule', 'description' => '+46 % ATQ à partir du round 10.', 'valeur' => 46, 'puissance' => 50, 'round' => 10],
        'mur_crepuscule' => ['nom' => 'Mur crépusculaire', 'description' => '+46 % DEF à partir du round 10.', 'valeur' => 46, 'puissance' => 50, 'round' => 10],
        'apocalypse' => ['nom' => 'Apocalypse', 'description' => '+50 % ATQ à partir du round 10 si le partenaire est tombé.', 'valeur' => 50, 'puissance' => 60, 'round' => 10],
        'derniere_citadelle' => ['nom' => 'Dernière citadelle', 'description' => '+50 % DEF à partir du round 10 si le partenaire est tombé.', 'valeur' => 50, 'puissance' => 60, 'round' => 10],
        'cri_meute' => ['nom' => 'Cri de meute', 'description' => '+30 % ATQ tant qu’un allié est vivant.', 'valeur' => 30, 'puissance' => 36],
        'doctrine_defensive' => ['nom' => 'Doctrine défensive', 'description' => '+30 % DEF en permanence.', 'valeur' => 30, 'puissance' => 36],
        'acceleration_temporelle' => ['nom' => 'Accélération temporelle', 'description' => '+6 % ATQ par round, jusqu’à +32 %.', 'valeur' => 6, 'puissance' => 44],
        'egide_croissante' => ['nom' => 'Égide croissante', 'description' => '+6 % DEF par round, jusqu’à +32 %.', 'valeur' => 6, 'puissance' => 44],
        'lame_solaire' => ['nom' => 'Lame solaire', 'description' => '+34 % ATQ pendant les trois premiers rounds.', 'valeur' => 34, 'puissance' => 40, 'round' => 1],
        'finition' => ['nom' => 'Finition', 'description' => '+44 % ATQ contre une cible sous 30 % de PV.', 'valeur' => 44, 'puissance' => 48],
        'serment_initial' => ['nom' => 'Serment initial', 'description' => '+34 % DEF lors de la première défense.', 'valeur' => 34, 'puissance' => 40],
        'protection_juree' => ['nom' => 'Protection jurée', 'description' => '+40 % DEF si la carte protège un allié pendant une double défense.', 'valeur' => 40, 'puissance' => 46],
        'cadence_temporelle' => ['nom' => 'Cadence temporelle', 'description' => '+6 % ATQ par round à partir du round 4, jusqu’à +24 %.', 'valeur' => 6, 'puissance' => 44, 'round' => 4],
        'danse_divisee' => ['nom' => 'Danse divisée', 'description' => '+34 % ATQ en attaque Split.', 'valeur' => 34, 'puissance' => 40],
        'aura_fortifiee' => ['nom' => 'Aura fortifiée', 'description' => '+38 % DEF en permanence.', 'valeur' => 38, 'puissance' => 44],
        'citadelle_double' => ['nom' => 'Citadelle double', 'description' => '+48 % DEF lors d’une double défense.', 'valeur' => 48, 'puissance' => 55],
        'ordre_charge' => ['nom' => 'Ordre de charge', 'description' => '+30 % ATQ pendant les trois premiers rounds.', 'valeur' => 30, 'puissance' => 34, 'round' => 1],
        'heritage_general' => ['nom' => 'Héritage du général', 'description' => '+42 % ATQ après la chute du partenaire.', 'valeur' => 42, 'puissance' => 48],
        'fureur_crepusculaire' => ['nom' => 'Fureur crépusculaire', 'description' => '+50 % ATQ à partir du round 10 si le partenaire est tombé.', 'valeur' => 50, 'puissance' => 60, 'round' => 10],
        'moisson_finale' => ['nom' => 'Moisson finale', 'description' => '+50 % ATQ contre une cible sous 25 % de PV à partir du round 8.', 'valeur' => 50, 'puissance' => 60, 'round' => 8],
        'doctrine_focus' => ['nom' => 'Doctrine Focus', 'description' => '+34 % ATQ en attaque Focus.', 'valeur' => 34, 'puissance' => 40],
        'doctrine_split' => ['nom' => 'Doctrine Split', 'description' => '+34 % ATQ en attaque Split.', 'valeur' => 34, 'puissance' => 40],
        'duo_discipline' => ['nom' => 'Duo discipliné', 'description' => '+28 % ATQ tant que le partenaire est vivant.', 'valeur' => 28, 'puissance' => 34],
        'egide_aube' => ['nom' => 'Égide de l’aube', 'description' => '+38 % DEF pendant les trois premiers rounds.', 'valeur' => 38, 'puissance' => 44, 'round' => 1],
        'egide_zenith' => ['nom' => 'Égide du zénith', 'description' => '+46 % DEF du round 4 au round 8.', 'valeur' => 46, 'puissance' => 50, 'round' => 4],
        'egide_crepuscule' => ['nom' => 'Égide crépusculaire', 'description' => '+50 % DEF à partir du round 10 si le partenaire est tombé.', 'valeur' => 50, 'puissance' => 60, 'round' => 10],
        'dernier_survivant' => ['nom' => 'Dernier survivant', 'description' => '+50 % ATQ à partir du round 8 lorsque la carte est seule dans son groupe.', 'valeur' => 50, 'puissance' => 60, 'round' => 8],
        'ancrage' => ['nom' => 'Ancrage', 'description' => '+35 % DEF au premier round : tenir la ligne ou tomber.', 'valeur' => 35, 'puissance' => 40, 'round' => 1],
        'fragilite_aube' => ['nom' => 'Fragilité de l’aube', 'description' => '-32 % DEF pendant les trois premiers rounds, puis la carte retrouve sa pleine résistance.', 'valeur' => 32, 'puissance' => 30, 'round' => 1],
        'instabilite' => ['nom' => 'Instabilité', 'description' => '-30 % ATQ pendant les trois premiers rounds : une puissance qui se prépare.', 'valeur' => 30, 'puissance' => 30, 'round' => 1],
    ];

    public function getDescription(): string
    {
        return 'Buff global des passifs, phases de combat et contres stratégiques';
    }

    /** @throws JsonException */
    public function up(Schema $schema): void
    {
        foreach (self::EFFETS as $type => $effet) {
            $existant = $this->connection->fetchAssociative(
                'SELECT id FROM passif WHERE type = ? LIMIT 1',
                [$type],
            );
            $slug = str_replace('_', '-', $type);
            if ($existant === false) {
                $this->addSql(
                    'INSERT INTO passif (nom, slug, description, type, valeur, puissance, a_partir_round, statut_actif) VALUES (?, ?, ?, ?, ?, ?, ?, 1)',
                    [$effet['nom'], $slug, $effet['description'], $type, $effet['valeur'], $effet['puissance'], $effet['round'] ?? null],
                );
                continue;
            }

            $this->addSql(
                'UPDATE passif SET nom = ?, description = ?, valeur = ?, puissance = ?, a_partir_round = ?, statut_actif = 1 WHERE type = ?',
                [$effet['nom'], $effet['description'], $effet['valeur'], $effet['puissance'], $effet['round'] ?? null, $type],
            );
        }

        $centraux = $this->connection->fetchAllAssociative(
            'SELECT id, nom, description, type, valeur, puissance, a_partir_round, statut_actif FROM passif',
        );
        $parType = [];
        foreach ($centraux as $central) {
            $parType[(string) $central['type']] = $central;
        }

        foreach ($this->connection->fetchAllAssociative('SELECT id, slug, rarete, passifs FROM stickman') as $stickman) {
            $snapshots = json_decode((string) ($stickman['passifs'] ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
            $snapshots = is_array($snapshots) ? $snapshots : [];
            $normalises = [];
            foreach ($snapshots as $snapshot) {
                if (!is_array($snapshot) || !isset($snapshot['type']) || !isset($parType[$snapshot['type']])) {
                    if (is_array($snapshot)) {
                        $normalises[] = $snapshot;
                    }
                    continue;
                }
                $normalises[] = $this->snapshot($parType[$snapshot['type']]);
            }

            // Quelques R1 reçoivent un profil à haut risque, sans transformer
            // toutes les cartes débutant en cartes à passifs.
            $r1 = [
                'recrue' => 'premier_sang',
                'garde' => 'ancrage',
                'bagarreur' => 'dernier_survivant',
                'sorcier-novice' => 'fragilite_aube',
            ];
            if ((int) $stickman['rarete'] === 1 && isset($r1[$stickman['slug']])) {
                $type = $r1[$stickman['slug']];
                $normalises = array_values(array_filter(
                    $normalises,
                    static fn (array $snapshot): bool => ($snapshot['type'] ?? null) !== $type,
                ));
                if (isset($parType[$type])) {
                    $normalises[] = $this->snapshot($parType[$type]);
                }
            }

            $this->addSql(
                'UPDATE stickman SET passifs = ? WHERE id = ?',
                [json_encode(array_slice($normalises, 0, 6), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), (int) $stickman['id']],
            );
        }
    }

    /** @param array<string, mixed> $central */
    private function snapshot(array $central): array
    {
        $snapshot = [
            'id' => (int) $central['id'],
            'nom' => (string) $central['nom'],
            'description' => (string) $central['description'],
            'type' => (string) $central['type'],
            'valeur' => (int) $central['valeur'],
            'puissance' => (int) $central['puissance'],
            'actif' => (bool) $central['statut_actif'],
        ];
        if ($central['a_partir_round'] !== null && (int) $central['a_partir_round'] > 1) {
            $snapshot['a_partir_round'] = (int) $central['a_partir_round'];
        }

        return $snapshot;
    }

    public function down(Schema $schema): void
    {
        // Les réglages d'équilibrage sont volontairement conservés lors d'un
        // retour arrière : les snapshots peuvent déjà avoir été utilisés en
        // combat et ne doivent pas être silencieusement réécrits.
    }
}
