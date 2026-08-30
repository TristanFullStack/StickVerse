<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute un code d’invitation unique aux combats en ligne.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE combat ADD code_invitation VARCHAR(9) DEFAULT NULL'
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_8D51E398D9B39C44'
            .' ON combat (code_invitation)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'DROP INDEX UNIQ_8D51E398D9B39C44 ON combat'
        );
        $this->addSql(
            'ALTER TABLE combat DROP code_invitation'
        );
    }
}
