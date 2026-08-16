<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260816180603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE combat (id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(30) NOT NULL, numero_round INT NOT NULL, date_creation DATETIME NOT NULL, date_mise_ajour DATETIME NOT NULL, joueur1_id INT NOT NULL, joueur2_id INT DEFAULT NULL, gagnant_id INT DEFAULT NULL, INDEX IDX_8D51E39892C1E237 (joueur1_id), INDEX IDX_8D51E39880744DD9 (joueur2_id), INDEX IDX_8D51E3982F942B8 (gagnant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE combat ADD CONSTRAINT FK_8D51E39892C1E237 FOREIGN KEY (joueur1_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE combat ADD CONSTRAINT FK_8D51E39880744DD9 FOREIGN KEY (joueur2_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE combat ADD CONSTRAINT FK_8D51E3982F942B8 FOREIGN KEY (gagnant_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE combat DROP FOREIGN KEY FK_8D51E39892C1E237');
        $this->addSql('ALTER TABLE combat DROP FOREIGN KEY FK_8D51E39880744DD9');
        $this->addSql('ALTER TABLE combat DROP FOREIGN KEY FK_8D51E3982F942B8');
        $this->addSql('DROP TABLE combat');
    }
}
