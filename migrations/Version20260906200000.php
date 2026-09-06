<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260906200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rattache les caisses et Stickmans existants à leurs collections.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "UPDATE caisse c
             INNER JOIN collection_jeu collection ON collection.slug = 'collection-origine'
             SET c.collection_jeu_id = collection.id
             WHERE c.collection_jeu_id IS NULL AND c.slug = 'caisse-origine'",
        );
        $this->addSql(
            "UPDATE caisse c
             INNER JOIN collection_jeu collection ON collection.slug = 'saison-1-premiers-renforts'
             SET c.collection_jeu_id = collection.id
             WHERE c.collection_jeu_id IS NULL AND c.slug = 'caisse-saison-1-premiers-renforts'",
        );
        $this->addSql(
            'UPDATE stickman s
             INNER JOIN caisse_stickman association ON association.stickman_id = s.id
             INNER JOIN caisse c ON c.id = association.caisse_id
             SET s.collection_jeu_id = c.collection_jeu_id
             WHERE s.collection_jeu_id IS NULL AND c.collection_jeu_id IS NOT NULL',
        );
        $this->addSql(
            "UPDATE stickman s
             INNER JOIN collection_jeu collection ON collection.slug = 'saison-1-premiers-renforts'
             SET s.collection_jeu_id = collection.id
             WHERE s.collection_jeu_id IS NULL",
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "UPDATE stickman s
             INNER JOIN collection_jeu collection ON collection.id = s.collection_jeu_id
             SET s.collection_jeu_id = NULL
             WHERE collection.slug IN ('collection-origine', 'saison-1-premiers-renforts')",
        );
        $this->addSql(
            "UPDATE caisse c
             INNER JOIN collection_jeu collection ON collection.id = c.collection_jeu_id
             SET c.collection_jeu_id = NULL
             WHERE c.slug IN ('caisse-origine', 'caisse-saison-1-premiers-renforts')",
        );
    }
}
