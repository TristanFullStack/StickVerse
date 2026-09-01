<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Prépare le nouveau compte joueur avec des caisses de départ.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE user ADD caisses_premiers_renforts INT UNSIGNED '
            .'NOT NULL DEFAULT 5'
        );
        $this->addSql(
            'ALTER TABLE user MODIFY elo INT UNSIGNED NOT NULL DEFAULT 500'
        );
        $this->addSql(
            'ALTER TABLE classement_saison_joueur '
            .'MODIFY elo INT UNSIGNED NOT NULL DEFAULT 500'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP caisses_premiers_renforts');
        $this->addSql(
            'ALTER TABLE user MODIFY elo INT UNSIGNED NOT NULL DEFAULT 1000'
        );
        $this->addSql(
            'ALTER TABLE classement_saison_joueur '
            .'MODIFY elo INT UNSIGNED NOT NULL DEFAULT 1000'
        );
    }
}
