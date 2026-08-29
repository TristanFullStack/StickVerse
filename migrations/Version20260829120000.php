<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Garantit l’unicité du slug technique de chaque Stickman.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_D477D0E5989D9B62 ON stickman (slug)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'DROP INDEX UNIQ_D477D0E5989D9B62 ON stickman'
        );
    }
}
