<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260823120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de l’historique persistant des résultats de rounds.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE resultat_round_combat (id INT AUTO_INCREMENT NOT NULL, combat_id INT NOT NULL, numero_round INT NOT NULL, resultats JSON NOT NULL, date_resolution DATETIME NOT NULL, INDEX IDX_86ECD08AFC7EEDB8 (combat_id), UNIQUE INDEX UNIQ_RESULTAT_ROUND_COMBAT_COMBAT_ROUND (combat_id, numero_round), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4'
        );
        $this->addSql(
            'ALTER TABLE resultat_round_combat ADD CONSTRAINT FK_RESULTAT_ROUND_COMBAT_COMBAT FOREIGN KEY (combat_id) REFERENCES combat (id) ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE resultat_round_combat DROP FOREIGN KEY FK_RESULTAT_ROUND_COMBAT_COMBAT'
        );
        $this->addSql('DROP TABLE resultat_round_combat');
    }
}
