<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le portefeuille de pièces virtuelles des joueurs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE user ADD pieces INT UNSIGNED '
            .'DEFAULT 1000 NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP pieces');
    }
}
