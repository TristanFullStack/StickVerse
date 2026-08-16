<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260816083208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE inventaire (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, utilisateur_id INT NOT NULL, stickman_id INT NOT NULL, INDEX IDX_338920E0FB88E14F (utilisateur_id), INDEX IDX_338920E08631F62F (stickman_id), UNIQUE INDEX UNIQ_UTILISATEUR_STICKMAN (utilisateur_id, stickman_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE inventaire ADD CONSTRAINT FK_338920E0FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE inventaire ADD CONSTRAINT FK_338920E08631F62F FOREIGN KEY (stickman_id) REFERENCES stickman (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE inventaire DROP FOREIGN KEY FK_338920E0FB88E14F');
        $this->addSql('ALTER TABLE inventaire DROP FOREIGN KEY FK_338920E08631F62F');
        $this->addSql('DROP TABLE inventaire');
    }
}
