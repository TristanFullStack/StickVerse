<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905131000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corrige le nom historique de la date de mise à jour du classement.';
    }

    public function up(Schema $schema): void
    {
        $colonnes = $this->connection->fetchFirstColumn(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS "
            ."WHERE TABLE_SCHEMA = DATABASE() "
            ."AND TABLE_NAME = 'classement_saison_joueur'",
        );

        if (in_array('date_mise_a_jour', $colonnes, true)) {
            $this->addSql(
                'ALTER TABLE classement_saison_joueur '
                .'CHANGE date_mise_a_jour date_mise_ajour DATETIME NOT NULL'
            );
        }
    }

    public function down(Schema $schema): void
    {
        $colonnes = $this->connection->fetchFirstColumn(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS "
            ."WHERE TABLE_SCHEMA = DATABASE() "
            ."AND TABLE_NAME = 'classement_saison_joueur'",
        );

        if (in_array('date_mise_ajour', $colonnes, true)) {
            $this->addSql(
                'ALTER TABLE classement_saison_joueur '
                .'CHANGE date_mise_ajour date_mise_a_jour DATETIME NOT NULL'
            );
        }
    }
}
