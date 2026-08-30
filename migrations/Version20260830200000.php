<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les objectifs réclamés sur le compte joueur.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE user ADD objectifs_reclames JSON NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP objectifs_reclames');
    }
}
