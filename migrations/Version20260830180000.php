<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute l’historique des mouvements de pièces des joueurs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE mouvement_pieces ('
            .'id INT AUTO_INCREMENT NOT NULL,'
            .'utilisateur_id INT NOT NULL,'
            .'montant INT NOT NULL,'
            .'type VARCHAR(30) NOT NULL,'
            .'libelle VARCHAR(255) NOT NULL,'
            .'date_creation DATETIME NOT NULL,'
            .'INDEX IDX_C36DFA44FB88E14F (utilisateur_id),'
            .'PRIMARY KEY(id)'
            .') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` '
            .'ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE mouvement_pieces ADD CONSTRAINT '
            .'FK_MOUVEMENT_PIECES_UTILISATEUR FOREIGN KEY (utilisateur_id) '
            .'REFERENCES user (id) ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE mouvement_pieces');
    }
}
