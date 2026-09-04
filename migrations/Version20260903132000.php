<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260903132000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne les défauts SQL du catalogue des passifs sur le mapping Doctrine';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE passif CHANGE puissance puissance INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE passif CHANGE statut_actif statut_actif TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE passif RENAME INDEX UNIQ_PASSIF_SLUG TO UNIQ_21613F67989D9B62');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE passif CHANGE puissance puissance INT NOT NULL');
        $this->addSql('ALTER TABLE passif CHANGE statut_actif statut_actif TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE passif RENAME INDEX UNIQ_21613F67989D9B62 TO UNIQ_PASSIF_SLUG');
    }
}
