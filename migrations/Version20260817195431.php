<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817195431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des plans secrets persistants pour chaque joueur et chaque round.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE plan_round_combat (id INT AUTO_INCREMENT NOT NULL, numero_round INT NOT NULL, cible_attaque_x VARCHAR(1) NOT NULL, cible_attaque_y VARCHAR(1) NOT NULL, cible_defense_x VARCHAR(1) NOT NULL, cible_defense_y VARCHAR(1) NOT NULL, date_soumission DATETIME NOT NULL, combat_id INT NOT NULL, joueur_id INT NOT NULL, INDEX IDX_9372DB3CFC7EEDB8 (combat_id), INDEX IDX_9372DB3CA9E2D76C (joueur_id), UNIQUE INDEX UNIQ_PLAN_ROUND_COMBAT_JOUEUR_ROUND (combat_id, joueur_id, numero_round), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE plan_round_combat ADD CONSTRAINT FK_9372DB3CFC7EEDB8 FOREIGN KEY (combat_id) REFERENCES combat (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plan_round_combat ADD CONSTRAINT FK_9372DB3CA9E2D76C FOREIGN KEY (joueur_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE plan_round_combat DROP FOREIGN KEY FK_9372DB3CFC7EEDB8');
        $this->addSql('ALTER TABLE plan_round_combat DROP FOREIGN KEY FK_9372DB3CA9E2D76C');
        $this->addSql('DROP TABLE plan_round_combat');
    }
}
