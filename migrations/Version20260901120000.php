<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le classement ELO indépendant de chaque saison.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE classement_saison_joueur (id INT AUTO_INCREMENT NOT NULL, joueur_id INT NOT NULL, saison_id INT NOT NULL, elo INT UNSIGNED DEFAULT 1000 NOT NULL, parties INT UNSIGNED DEFAULT 0 NOT NULL, victoires INT UNSIGNED DEFAULT 0 NOT NULL, defaites INT UNSIGNED DEFAULT 0 NOT NULL, matchs_nuls INT UNSIGNED DEFAULT 0 NOT NULL, date_mise_a_jour DATETIME NOT NULL, INDEX IDX_3DB47032A9E2D76C (joueur_id), INDEX IDX_3DB47032F965414C (saison_id), UNIQUE INDEX UNIQ_CLASSEMENT_SAISON_JOUEUR (joueur_id, saison_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE classement_saison_joueur ADD CONSTRAINT FK_CLASSEMENT_SAISON_JOUEUR FOREIGN KEY (joueur_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classement_saison_joueur ADD CONSTRAINT FK_CLASSEMENT_SAISON_COLLECTION FOREIGN KEY (saison_id) REFERENCES collection_jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE combat ADD saison_classement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE combat ADD CONSTRAINT FK_COMBAT_SAISON_CLASSEMENT FOREIGN KEY (saison_classement_id) REFERENCES collection_jeu (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8D51E3983828FAAD ON combat (saison_classement_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE classement_saison_joueur DROP FOREIGN KEY FK_CLASSEMENT_SAISON_JOUEUR');
        $this->addSql('ALTER TABLE classement_saison_joueur DROP FOREIGN KEY FK_CLASSEMENT_SAISON_COLLECTION');
        $this->addSql('ALTER TABLE combat DROP FOREIGN KEY FK_COMBAT_SAISON_CLASSEMENT');
        $this->addSql('DROP INDEX IDX_8D51E3983828FAAD ON combat');
        $this->addSql('ALTER TABLE combat DROP saison_classement_id');
        $this->addSql('DROP TABLE classement_saison_joueur');
    }
}
