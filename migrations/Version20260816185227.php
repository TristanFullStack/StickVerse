<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260816185227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE combattant_combat (id INT AUTO_INCREMENT NOT NULL, slot VARCHAR(1) NOT NULL, stickman_id_original INT NOT NULL, nom_snapshot VARCHAR(255) NOT NULL, image_snapshot VARCHAR(255) NOT NULL, rarete_snapshot INT NOT NULL, pv_maximum INT NOT NULL, pv_actuels INT NOT NULL, attaque_snapshot INT NOT NULL, defense_snapshot INT NOT NULL, combat_id INT NOT NULL, joueur_id INT NOT NULL, INDEX IDX_5CFA82B7FC7EEDB8 (combat_id), INDEX IDX_5CFA82B7A9E2D76C (joueur_id), UNIQUE INDEX UNIQ_COMBAT_JOUEUR_SLOT (combat_id, joueur_id, slot), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE combattant_combat ADD CONSTRAINT FK_5CFA82B7FC7EEDB8 FOREIGN KEY (combat_id) REFERENCES combat (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE combattant_combat ADD CONSTRAINT FK_5CFA82B7A9E2D76C FOREIGN KEY (joueur_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE combattant_combat DROP FOREIGN KEY FK_5CFA82B7FC7EEDB8');
        $this->addSql('ALTER TABLE combattant_combat DROP FOREIGN KEY FK_5CFA82B7A9E2D76C');
        $this->addSql('DROP TABLE combattant_combat');
    }
}
