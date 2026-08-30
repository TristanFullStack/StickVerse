<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les demandes temporaires de réinitialisation du mot de passe.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE reinitialisation_mot_de_passe ('
            .'id INT AUTO_INCREMENT NOT NULL, '
            .'jeton_hash VARCHAR(64) NOT NULL, '
            .'date_creation DATETIME NOT NULL, '
            .'date_expiration DATETIME NOT NULL, '
            .'date_utilisation DATETIME DEFAULT NULL, '
            .'utilisateur_id INT NOT NULL, '
            .'UNIQUE INDEX UNIQ_3F6947511E1E10D6 (jeton_hash), '
            .'INDEX IDX_3F694751FB88E14F (utilisateur_id), '
            .'PRIMARY KEY (id)'
            .') DEFAULT CHARACTER SET utf8mb4'
        );
        $this->addSql(
            'ALTER TABLE reinitialisation_mot_de_passe '
            .'ADD CONSTRAINT FK_3F694751FB88E14F '
            .'FOREIGN KEY (utilisateur_id) REFERENCES user (id) '
            .'ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE reinitialisation_mot_de_passe '
            .'DROP FOREIGN KEY FK_3F694751FB88E14F'
        );
        $this->addSql('DROP TABLE reinitialisation_mot_de_passe');
    }
}
