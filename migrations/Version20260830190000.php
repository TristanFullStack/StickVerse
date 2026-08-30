<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la date de dernière récompense quotidienne des joueurs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE user ADD date_derniere_recompense_quotidienne DATE DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE user DROP date_derniere_recompense_quotidienne'
        );
    }
}
