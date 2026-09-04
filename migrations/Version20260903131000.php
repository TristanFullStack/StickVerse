<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use JsonException;

/**
 * Rend le passif du Porte-Bouclier dépendant d'une vraie double défense :
 * protéger un allié seul ne suffit plus, l'adversaire peut ainsi le contrer.
 */
final class Version20260903131000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rend le passif du Porte-Bouclier réellement conditionnel';
    }

    /** @throws JsonException */
    public function up(Schema $schema): void
    {
        $passifId = $this->connection->fetchOne(
            'SELECT id FROM passif WHERE type = ? LIMIT 1',
            ['protection_juree'],
        );
        if ($passifId === false) {
            return;
        }

        $stickman = $this->connection->fetchAssociative(
            'SELECT id, passifs FROM stickman WHERE slug = ? LIMIT 1',
            ['portebouclier'],
        );
        if ($stickman === false || $stickman['passifs'] === null) {
            return;
        }

        $passifs = json_decode((string) $stickman['passifs'], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($passifs)) {
            return;
        }

        $modifies = false;
        foreach ($passifs as &$passif) {
            if (!is_array($passif) || ($passif['type'] ?? null) !== 'protecteur') {
                continue;
            }
            $passif = [
                'id' => (int) $passifId,
                'nom' => 'Protection jurée',
                'description' => '+10 % DEF lorsque la carte protège un allié pendant une double défense.',
                'type' => 'protection_juree',
                'valeur' => 10,
                'puissance' => 12,
            ];
            $modifies = true;
        }
        unset($passif);

        if ($modifies) {
            $this->addSql(
                'UPDATE stickman SET passifs = ? WHERE id = ?',
                [json_encode($passifs, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), (int) $stickman['id']],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Le retour arrière conserve la nouvelle définition dans le catalogue :
        // aucune carte ne doit retrouver un passif supprimé implicitement.
    }
}
