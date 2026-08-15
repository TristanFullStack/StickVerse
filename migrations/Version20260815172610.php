<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260815172610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE caisse_stickman (id INT AUTO_INCREMENT NOT NULL, poids INT NOT NULL, caisse_id INT NOT NULL, stickman_id INT NOT NULL, INDEX IDX_340BB7D27B4FEBF (caisse_id), INDEX IDX_340BB7D8631F62F (stickman_id), UNIQUE INDEX UNIQ_CAISSE_STICKMAN (caisse_id, stickman_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE caisse_stickman ADD CONSTRAINT FK_340BB7D27B4FEBF FOREIGN KEY (caisse_id) REFERENCES caisse (id)');
        $this->addSql('ALTER TABLE caisse_stickman ADD CONSTRAINT FK_340BB7D8631F62F FOREIGN KEY (stickman_id) REFERENCES stickman (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE caisse_stickman DROP FOREIGN KEY FK_340BB7D27B4FEBF');
        $this->addSql('ALTER TABLE caisse_stickman DROP FOREIGN KEY FK_340BB7D8631F62F');
        $this->addSql('DROP TABLE caisse_stickman');
    }
}
