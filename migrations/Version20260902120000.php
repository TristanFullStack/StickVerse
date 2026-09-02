<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute l’inventaire individuel des caisses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE caisse_possedee (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, caisse_id INT NOT NULL, date_acquisition DATETIME NOT NULL, INDEX IDX_CAISSE_POSSEDEE_UTILISATEUR (utilisateur_id), INDEX IDX_CAISSE_POSSEDEE_CAISSE (caisse_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caisse_possedee ADD CONSTRAINT FK_CAISSE_POSSEDEE_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE caisse_possedee ADD CONSTRAINT FK_CAISSE_POSSEDEE_CAISSE FOREIGN KEY (caisse_id) REFERENCES caisse (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE caisse_possedee');
    }
}
