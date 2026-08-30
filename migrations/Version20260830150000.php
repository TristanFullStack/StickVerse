<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute un pseudo public unique à chaque joueur.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE user ADD pseudo VARCHAR(24) DEFAULT NULL'
        );
        $this->addSql(
            "UPDATE user SET pseudo = CONCAT('Joueur-', id) "
            .'WHERE pseudo IS NULL'
        );
        $this->addSql(
            'ALTER TABLE user CHANGE pseudo pseudo VARCHAR(24) NOT NULL'
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_IDENTIFIER_PSEUDO '
            .'ON user (pseudo)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'DROP INDEX UNIQ_IDENTIFIER_PSEUDO ON user'
        );
        $this->addSql('ALTER TABLE user DROP pseudo');
    }
}
