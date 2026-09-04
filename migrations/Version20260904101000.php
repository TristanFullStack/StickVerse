<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use JsonException;

/** Réindexe les snapshots après le changement massif des valeurs de J97. */
final class Version20260904101000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Resynchronise les snapshots de passifs après le buff J97';
    }

    /** @throws JsonException */
    public function up(Schema $schema): void
    {
        $centraux = $this->connection->fetchAllAssociative(
            'SELECT id, nom, description, type, valeur, puissance, a_partir_round, statut_actif FROM passif',
        );
        $parType = [];
        foreach ($centraux as $central) {
            $parType[(string) $central['type']] = $central;
        }

        $r1 = [
            'recrue' => 'premier_sang',
            'garde' => 'ancrage',
            'bagarreur' => 'dernier_survivant',
            'sorciernovice' => 'fragilite_aube',
        ];

        foreach ($this->connection->fetchAllAssociative('SELECT id, slug, rarete, passifs FROM stickman') as $stickman) {
            $passifs = json_decode((string) ($stickman['passifs'] ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
            $passifs = is_array($passifs) ? $passifs : [];
            $normalises = [];
            foreach ($passifs as $snapshot) {
                $type = is_array($snapshot) ? ($snapshot['type'] ?? null) : null;
                if (is_string($type) && isset($parType[$type])) {
                    $normalises[] = $this->snapshot($parType[$type]);
                } elseif (is_array($snapshot)) {
                    $normalises[] = $snapshot;
                }
            }

            $slug = strtolower((string) $stickman['slug']);
            if ((int) $stickman['rarete'] === 1 && isset($r1[$slug], $parType[$r1[$slug]])) {
                $type = $r1[$slug];
                $normalises = array_values(array_filter(
                    $normalises,
                    static fn (array $snapshot): bool => ($snapshot['type'] ?? null) !== $type,
                ));
                $normalises[] = $this->snapshot($parType[$type]);
            }

            $this->addSql(
                'UPDATE stickman SET passifs = ? WHERE id = ?',
                [json_encode(array_slice($normalises, 0, 6), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), (int) $stickman['id']],
            );
        }
    }

    /** @param array<string, mixed> $central */
    private function snapshot(array $central): array
    {
        $snapshot = [
            'id' => (int) $central['id'],
            'nom' => (string) $central['nom'],
            'description' => (string) $central['description'],
            'type' => (string) $central['type'],
            'valeur' => (int) $central['valeur'],
            'puissance' => (int) $central['puissance'],
            'actif' => (bool) $central['statut_actif'],
        ];
        if ($central['a_partir_round'] !== null && (int) $central['a_partir_round'] > 1) {
            $snapshot['a_partir_round'] = (int) $central['a_partir_round'];
        }

        return $snapshot;
    }

    public function down(Schema $schema): void
    {
    }
}
