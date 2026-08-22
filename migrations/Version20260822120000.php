<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persistance du dernier résultat résolu pour les deux participants.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE combat ADD dernier_round_resolu INT DEFAULT NULL, ADD derniers_resultats JSON DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE combat DROP dernier_round_resolu, DROP derniers_resultats'
        );
    }
}
