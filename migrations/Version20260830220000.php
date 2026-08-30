<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les saisons et collections du catalogue StickVerse.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE collection_jeu (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, saison INT NOT NULL, statut_actif TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_2A6D22D1989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caisse ADD collection_jeu_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE stickman ADD collection_jeu_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE caisse ADD CONSTRAINT FK_CAISSE_COLLECTION_JEU FOREIGN KEY (collection_jeu_id) REFERENCES collection_jeu (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE stickman ADD CONSTRAINT FK_STICKMAN_COLLECTION_JEU FOREIGN KEY (collection_jeu_id) REFERENCES collection_jeu (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_B2A353C87EBBC1D8 ON caisse (collection_jeu_id)');
        $this->addSql('CREATE INDEX IDX_D477D0E57EBBC1D8 ON stickman (collection_jeu_id)');

        $this->addSql("INSERT INTO collection_jeu (nom, slug, description, saison, statut_actif) VALUES ('Collection Origine', 'collection-origine', 'Les premiers Stickmen de StickVerse.', 0, 1), ('Saison 1 - Premiers Renforts', 'saison-1-premiers-renforts', 'Les renforts de la première saison de StickVerse.', 1, 1)");
        $this->addSql("UPDATE caisse SET collection_jeu_id = (SELECT id FROM collection_jeu WHERE slug = 'collection-origine') WHERE slug = 'caisse-origine'");
        $this->addSql("UPDATE caisse SET collection_jeu_id = (SELECT id FROM collection_jeu WHERE slug = 'saison-1-premiers-renforts') WHERE slug = 'caisse-saison-1-premiers-renforts' OR nom LIKE 'Caisse Saison 1%'");
        $this->addSql("UPDATE stickman s INNER JOIN caisse_stickman cs ON cs.stickman_id = s.id INNER JOIN caisse c ON c.id = cs.caisse_id SET s.collection_jeu_id = c.collection_jeu_id WHERE c.collection_jeu_id IS NOT NULL");
        $this->addSql("UPDATE stickman SET collection_jeu_id = (SELECT id FROM collection_jeu WHERE slug = 'saison-1-premiers-renforts') WHERE collection_jeu_id IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caisse DROP FOREIGN KEY FK_CAISSE_COLLECTION_JEU');
        $this->addSql('ALTER TABLE stickman DROP FOREIGN KEY FK_STICKMAN_COLLECTION_JEU');
        $this->addSql('DROP INDEX IDX_B2A353C87EBBC1D8 ON caisse');
        $this->addSql('DROP INDEX IDX_D477D0E57EBBC1D8 ON stickman');
        $this->addSql('ALTER TABLE caisse DROP collection_jeu_id');
        $this->addSql('ALTER TABLE stickman DROP collection_jeu_id');
        $this->addSql('DROP TABLE collection_jeu');
    }
}
