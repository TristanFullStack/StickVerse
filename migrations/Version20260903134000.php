<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use JsonException;

final class Version20260903134000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne la description de Protection jurée sur sa condition de double défense';
    }

    /** @throws JsonException */
    public function up(Schema $schema): void
    {
        $description = '+10 % DEF lorsque la carte protège un allié pendant une double défense.';
        $this->addSql('UPDATE passif SET description = ? WHERE type = ?', [$description, 'protection_juree']);

        $passif = $this->connection->fetchAssociative(
            'SELECT id, nom, description, type, valeur, puissance, a_partir_round FROM passif WHERE type = ? LIMIT 1',
            ['protection_juree'],
        );
        if ($passif === false) {
            return;
        }

        foreach ($this->connection->fetchAllAssociative('SELECT id, passifs FROM stickman WHERE passifs IS NOT NULL') as $stickman) {
            $snapshots = json_decode((string) $stickman['passifs'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($snapshots)) {
                continue;
            }
            $modifies = false;
            foreach ($snapshots as &$snapshot) {
                if (!is_array($snapshot) || ($snapshot['type'] ?? null) !== 'protection_juree') {
                    continue;
                }
                $snapshot['id'] = (int) $passif['id'];
                $snapshot['nom'] = (string) $passif['nom'];
                $snapshot['description'] = $description;
                $snapshot['valeur'] = (int) $passif['valeur'];
                $snapshot['puissance'] = (int) $passif['puissance'];
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
    }

    public function down(Schema $schema): void
    {
    }
}
