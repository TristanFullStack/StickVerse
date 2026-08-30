<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la confirmation de préparation des deux joueurs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE combat'
            .' ADD joueur1_pret TINYINT(1) DEFAULT NULL,'
            .' ADD joueur2_pret TINYINT(1) DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE combat'
            .' DROP joueur1_pret,'
            .' DROP joueur2_pret'
        );
    }
}
