<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la cote ELO des joueurs et le suivi de son attribution par combat.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE user ADD elo INT UNSIGNED NOT NULL DEFAULT 1000'
        );
        $this->addSql(
            'ALTER TABLE combat ADD elo_attribuee TINYINT(1) NOT NULL DEFAULT 0'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE combat DROP elo_attribuee');
        $this->addSql('ALTER TABLE user DROP elo');
    }
}
