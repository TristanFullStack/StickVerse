<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Distingue les combats publics des combats privés.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE combat ADD prive TINYINT(1) DEFAULT 0 NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE combat DROP prive');
    }
}
