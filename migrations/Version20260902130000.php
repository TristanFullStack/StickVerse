<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convertit les caisses de départ historiques en lignes d’inventaire';
    }

    public function up(Schema $schema): void
    {
        $caisse = $this->connection->fetchAssociative(
            'SELECT id FROM caisse WHERE slug = ? LIMIT 1',
            ['caisse-saison-1-premiers-renforts'],
        );
        if ($caisse === false) {
            return;
        }

        $joueurs = $this->connection->fetchAllAssociative(
            'SELECT id, caisses_premiers_renforts FROM user WHERE caisses_premiers_renforts > 0',
        );
        foreach ($joueurs as $joueur) {
            $quantite = (int) $joueur['caisses_premiers_renforts'];
            for ($i = 0; $i < $quantite; ++$i) {
                $this->addSql(
                    'INSERT INTO caisse_possedee (utilisateur_id, caisse_id, date_acquisition) VALUES (?, ?, CURRENT_TIMESTAMP)',
                    [(int) $joueur['id'], (int) $caisse['id']],
                );
            }
            $this->addSql(
                'UPDATE user SET caisses_premiers_renforts = 0 WHERE id = ?',
                [(int) $joueur['id']],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Les lignes migrées sont des possessions légitimes : elles ne sont
        // pas supprimées automatiquement lors d’un retour arrière.
    }
}
