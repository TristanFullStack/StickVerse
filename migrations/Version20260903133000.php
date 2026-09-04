<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use JsonException;

final class Version20260903133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Resynchronise le snapshot du Porte-Bouclier avec le catalogue central';
    }

    /** @throws JsonException */
    public function up(Schema $schema): void
    {
        $passif = $this->connection->fetchAssociative(
            'SELECT id, nom, description, type, valeur, puissance, a_partir_round FROM passif WHERE type = ? LIMIT 1',
            ['protection_juree'],
        );
        $stickman = $this->connection->fetchAssociative(
            'SELECT id, passifs FROM stickman WHERE slug = ? LIMIT 1',
            ['portebouclier'],
        );
        if ($passif === false || $stickman === false || $stickman['passifs'] === null) {
            return;
        }

        $snapshots = json_decode((string) $stickman['passifs'], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($snapshots)) {
            return;
        }
        $modifies = false;
        foreach ($snapshots as &$snapshot) {
            if (!is_array($snapshot) || ($snapshot['type'] ?? null) !== 'protection_juree') {
                continue;
            }
            $snapshot = [
                'id' => (int) $passif['id'],
                'nom' => (string) $passif['nom'],
                'description' => (string) $passif['description'],
                'type' => (string) $passif['type'],
                'valeur' => (int) $passif['valeur'],
                'puissance' => (int) $passif['puissance'],
                ...($passif['a_partir_round'] !== null ? ['a_partir_round' => (int) $passif['a_partir_round']] : []),
            ];
            $modifies = true;
        }
        unset($snapshot);
        if ($modifies) {
            $this->addSql(
                'UPDATE stickman SET passifs = ? WHERE id = ?',
                [json_encode($snapshots, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), (int) $stickman['id']],
            );
        }
    }

    public function down(Schema $schema): void
    {
    }
}
