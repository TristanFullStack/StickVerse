<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le suivi des récompenses de classement saisonnier.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE classement_saison_joueur ADD recompense_reclamee TINYINT(1) DEFAULT 0 NOT NULL, ADD date_recompense_reclamee DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE classement_saison_joueur DROP recompense_reclamee, DROP date_recompense_reclamee');
    }
}
