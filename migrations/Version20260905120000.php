<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne les noms des index de l’inventaire des caisses avec Doctrine';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE caisse_possedee RENAME INDEX idx_caisse_possedee_utilisateur TO IDX_360B92E5FB88E14F'
        );
        $this->addSql(
            'ALTER TABLE caisse_possedee RENAME INDEX idx_caisse_possedee_caisse TO IDX_360B92E527B4FEBF'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE caisse_possedee RENAME INDEX IDX_360B92E5FB88E14F TO idx_caisse_possedee_utilisateur'
        );
        $this->addSql(
            'ALTER TABLE caisse_possedee RENAME INDEX IDX_360B92E527B4FEBF TO idx_caisse_possedee_caisse'
        );
    }
}
