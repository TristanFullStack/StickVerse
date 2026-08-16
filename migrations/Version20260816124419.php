<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260816124419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE equipe (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, utilisateur_id INT NOT NULL, stickman_a_id INT NOT NULL, stickman_b_id INT NOT NULL, stickman_c_id INT NOT NULL, stickman_d_id INT NOT NULL, INDEX IDX_2449BA15FB88E14F (utilisateur_id), INDEX IDX_2449BA151471ACBB (stickman_a_id), INDEX IDX_2449BA156C40355 (stickman_b_id), INDEX IDX_2449BA15BE786430 (stickman_c_id), INDEX IDX_2449BA1523AF5C89 (stickman_d_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT FK_2449BA15FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT FK_2449BA151471ACBB FOREIGN KEY (stickman_a_id) REFERENCES stickman (id)');
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT FK_2449BA156C40355 FOREIGN KEY (stickman_b_id) REFERENCES stickman (id)');
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT FK_2449BA15BE786430 FOREIGN KEY (stickman_c_id) REFERENCES stickman (id)');
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT FK_2449BA1523AF5C89 FOREIGN KEY (stickman_d_id) REFERENCES stickman (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA15FB88E14F');
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA151471ACBB');
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA156C40355');
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA15BE786430');
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA1523AF5C89');
        $this->addSql('DROP TABLE equipe');
    }
}
