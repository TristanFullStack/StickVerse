<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les reçus idempotents et atomiques des ouvertures de caisses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ouverture_caisse (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, caisse_id INT DEFAULT NULL, stickman_id INT DEFAULT NULL, jeton VARCHAR(64) NOT NULL, quantite_apres INT NOT NULL, nouveau TINYINT(1) NOT NULL, collection_possedes INT NOT NULL, collection_total INT NOT NULL, date_creation DATETIME NOT NULL, UNIQUE INDEX UNIQ_OUVERTURE_CAISSE_JETON (jeton), INDEX IDX_69F20A0EFB88E14F (utilisateur_id), INDEX IDX_69F20A0E27B4FEBF (caisse_id), INDEX IDX_69F20A0E8631F62F (stickman_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ouverture_caisse ADD CONSTRAINT FK_OUVERTURE_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ouverture_caisse ADD CONSTRAINT FK_OUVERTURE_CAISSE FOREIGN KEY (caisse_id) REFERENCES caisse (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ouverture_caisse ADD CONSTRAINT FK_OUVERTURE_STICKMAN FOREIGN KEY (stickman_id) REFERENCES stickman (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ouverture_caisse');
    }
}
