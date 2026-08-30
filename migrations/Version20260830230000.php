<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la planification temporelle des saisons StickVerse.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE collection_jeu ADD date_debut DATETIME DEFAULT NULL, ADD date_fin DATETIME DEFAULT NULL');
        $this->addSql("UPDATE collection_jeu SET date_debut = '2026-08-30 00:00:00' WHERE slug = 'saison-1-premiers-renforts'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE collection_jeu DROP date_debut, DROP date_fin');
    }
}
