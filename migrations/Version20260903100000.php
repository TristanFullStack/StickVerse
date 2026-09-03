<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260903100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les passifs configurables et leur snapshot de combat';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE stickman ADD passifs JSON DEFAULT NULL'
        );
        $this->addSql(
            'ALTER TABLE combattant_combat '
            .'ADD passifs_snapshot JSON DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE combattant_combat DROP passifs_snapshot'
        );
        $this->addSql('ALTER TABLE stickman DROP passifs');
    }
}
