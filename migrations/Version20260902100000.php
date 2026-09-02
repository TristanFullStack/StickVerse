<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902100000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute les actualités publiables et rattachées à une saison'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE actualite (id INT AUTO_INCREMENT NOT NULL, saison_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, date_publication DATETIME DEFAULT NULL, statut_actif TINYINT(1) DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_54928197989D9B62 (slug), INDEX IDX_54928197F965414C (saison_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE actualite ADD CONSTRAINT FK_ACTUALITE_SAISON FOREIGN KEY (saison_id) REFERENCES collection_jeu (id) ON DELETE SET NULL');
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE actualite'); }
}
