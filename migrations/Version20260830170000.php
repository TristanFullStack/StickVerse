<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le suivi de distribution des récompenses de combat.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE combat ADD recompense_attribuee TINYINT(1) '
            .'DEFAULT 0 NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE combat DROP recompense_attribuee'
        );
    }
}
