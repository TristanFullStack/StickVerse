<?php

namespace App\Service;

use App\Entity\Caisse;
use App\Entity\CaisseStickman;
use App\Entity\CollectionJeu;
use App\Entity\Stickman;
use App\Repository\CaisseRepository;
use App\Repository\CaisseStickmanRepository;
use App\Repository\CollectionJeuRepository;
use App\Repository\StickmanRepository;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use LogicException;
use RuntimeException;

final class CatalogueJeuService
{
    public const VERSION_FORMAT = 1;

    public function __construct(
        private readonly StickmanRepository $stickmanRepository,
        private readonly CaisseRepository $caisseRepository,
        private readonly CaisseStickmanRepository $caisseStickmanRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ?CollectionJeuRepository $collectionRepository = null,
    ) {
    }

    /**
     * @return array{
     *     version: int,
     *     stickmans: list<array<string, bool|int|string|list<array<string, mixed>>>>,
     *     caisses: list<array<string, bool|int|string|list<array{stickman: string, poids: int}>>>
     * }
     */
    public function exporter(): array
    {
        /** @var list<Stickman> $stickmans */
        $stickmans = $this->stickmanRepository->findBy(
            [],
            ['slug' => 'ASC', 'id' => 'ASC'],
        );

        /** @var list<Caisse> $caisses */
        $caisses = $this->caisseRepository->findBy(
            [],
            ['slug' => 'ASC', 'id' => 'ASC'],
        );

        $this->verifierSlugsStickmansUniques($stickmans);

        return [
            'version' => self::VERSION_FORMAT,
            'stickmans' => array_map(
                static function (Stickman $stickman): array {
                    $donnees = [
                        'slug' => (string) $stickman->getSlug(),
                        'nom' => (string) $stickman->getNom(),
                        'description' => (string) $stickman->getDescription(),
                        'image' => (string) $stickman->getImage(),
                        'rarete' => (int) $stickman->getRarete(),
                        'pv' => (int) $stickman->getPv(),
                        'attaque' => (int) $stickman->getAttaque(),
                        'defense' => (int) $stickman->getDefense(),
                        'actif' => (bool) $stickman->isStatutActif(),
                    ];

                    $passifs = $stickman->getPassifs();

                    if ($passifs !== []) {
                        $donnees['passifs'] = $passifs;
                    }

                    return $donnees;
                },
                $stickmans,
            ),
            'caisses' => array_map(
                static function (Caisse $caisse): array {
                    $contenus = [];

                    foreach ($caisse->getContenus() as $contenu) {
                        $stickman = $contenu->getStickman();

                        if (null === $stickman || null === $stickman->getSlug()) {
                            throw new LogicException(
                                sprintf(
                                    'La caisse "%s" contient une association sans Stickman valide.',
                                    $caisse->getSlug(),
                                ),
                            );
                        }

                        $contenus[] = [
                            'stickman' => $stickman->getSlug(),
                            'poids' => (int) $contenu->getPoids(),
                        ];
                    }

                    usort(
                        $contenus,
                        static fn (array $gauche, array $droite): int =>
                            $gauche['stickman'] <=> $droite['stickman'],
                    );

                    return [
                        'slug' => (string) $caisse->getSlug(),
                        'nom' => (string) $caisse->getNom(),
                        'description' => (string) $caisse->getDescription(),
                        'image' => (string) $caisse->getImage(),
                        'prix' => (int) $caisse->getPrix(),
                        'actif' => (bool) $caisse->isStatutActif(),
                        'contenus' => $contenus,
                    ];
                },
                $caisses,
            ),
        ];
    }

    /**
     * @return array{stickmans: int, caisses: int, associations: int}
     *
     * @throws JsonException
     */
    public function exporterVersFichier(string $chemin): array
    {
        $catalogue = $this->exporter();
        $dossier = dirname($chemin);

        if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            throw new RuntimeException(
                sprintf('Impossible de créer le dossier "%s".', $dossier),
            );
        }

        $json = json_encode(
            $catalogue,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ).PHP_EOL;

        if (false === file_put_contents($chemin, $json, LOCK_EX)) {
            throw new RuntimeException(
                sprintf('Impossible d’écrire le catalogue dans "%s".', $chemin),
            );
        }

        return [
            'stickmans' => count($catalogue['stickmans']),
            'caisses' => count($catalogue['caisses']),
            'associations' => array_sum(
                array_map(
                    static fn (array $caisse): int => count($caisse['contenus']),
                    $catalogue['caisses'],
                ),
            ),
        ];
    }

    /**
     * Importe le catalogue par slug sans supprimer les données absentes du fichier.
     *
     * @return array{stickmans: int, caisses: int, associations: int}
     *
     * @throws JsonException
     */
    public function importerDepuisFichier(string $chemin): array
    {
        $catalogue = $this->lireCatalogue($chemin);

        /** @var array{version: int, stickmans: list<array<string, mixed>>, caisses: list<array<string, mixed>>} $catalogue */
        return $this->entityManager->wrapInTransaction(
            function () use ($catalogue): array {
                /** @var array<string, Stickman> $stickmansParSlug */
                $stickmansParSlug = [];

                foreach ($catalogue['stickmans'] as $donnees) {
                    $slug = $donnees['slug'];
                    $existants = $this->stickmanRepository->findBy(['slug' => $slug]);

                    if (count($existants) > 1) {
                        throw new LogicException(
                            sprintf(
                                'Import impossible : plusieurs Stickmans utilisent déjà le slug "%s".',
                                $slug,
                            ),
                        );
                    }

                    $stickman = $existants[0] ?? new Stickman();

                    $stickman
                        ->setSlug($slug)
                        ->setNom($donnees['nom'])
                        ->setDescription($donnees['description'])
                        ->setImage($donnees['image'])
                        ->setRarete($donnees['rarete'])
                        ->setPv($donnees['pv'])
                        ->setAttaque($donnees['attaque'])
                        ->setDefense($donnees['defense'])
                        ->setPassifs(
                            isset($donnees['passifs']) && is_array($donnees['passifs'])
                                ? $donnees['passifs']
                                : [],
                        )
                        ->setStatutActif($donnees['actif']);

                    if ([] === $existants) {
                        $this->entityManager->persist($stickman);
                    }

                    $stickmansParSlug[$slug] = $stickman;
                }

                $nombreAssociations = 0;

                foreach ($catalogue['caisses'] as $donnees) {
                    $caisse = $this->caisseRepository->findOneBy([
                        'slug' => $donnees['slug'],
                    ]) ?? new Caisse();

                    $caisse
                        ->setSlug($donnees['slug'])
                        ->setNom($donnees['nom'])
                        ->setDescription($donnees['description'])
                        ->setImage($donnees['image'])
                        ->setPrix($donnees['prix'])
                        ->setStatutActif($donnees['actif']);

                    $collection = $this->collectionPourCaisse($caisse);
                    if ($collection !== null) {
                        $caisse->setCollectionJeu($collection);
                    }

                    if (null === $caisse->getId()) {
                        $this->entityManager->persist($caisse);
                    }

                    foreach ($donnees['contenus'] as $donneesContenu) {
                        $stickman = $stickmansParSlug[$donneesContenu['stickman']];
                        $association = $this->caisseStickmanRepository->findOneBy([
                            'caisse' => $caisse,
                            'stickman' => $stickman,
                        ]) ?? new CaisseStickman();

                        $association
                            ->setCaisse($caisse)
                            ->setStickman($stickman)
                            ->setPoids($donneesContenu['poids']);

                        if ($collection !== null && $stickman->getCollectionJeu() === null) {
                            $stickman->setCollectionJeu($collection);
                        }

                        if (null === $association->getId()) {
                            $caisse->addContenu($association);
                            $this->entityManager->persist($association);
                        }

                        ++$nombreAssociations;
                    }
                }

                return [
                    'stickmans' => count($catalogue['stickmans']),
                    'caisses' => count($catalogue['caisses']),
                    'associations' => $nombreAssociations,
                ];
            },
        );
    }

    private function collectionPourCaisse(Caisse $caisse): ?CollectionJeu
    {
        $collection = $caisse->getCollectionJeu();
        if ($collection !== null || $this->collectionRepository === null) {
            return $collection;
        }

        $slugCollection = match ($caisse->getSlug()) {
            'caisse-origine' => 'collection-origine',
            Caisse::SLUG_PREMIERS_RENFORTS => 'saison-1-premiers-renforts',
            default => null,
        };

        return $slugCollection === null
            ? null
            : $this->collectionRepository->findOneBy(['slug' => $slugCollection]);
    }

    /**
     * Vérifie sans écriture que la base et les images correspondent au fichier versionné.
     *
     * @return array{stickmans: int, caisses: int, associations: int, images: int}
     *
     * @throws JsonException
     */
    public function verifierInstallationDepuisFichier(
        string $chemin,
        string $dossierProjet,
    ): array {
        $catalogueFichier = $this->lireCatalogue($chemin);
        $catalogueBase = $this->exporter();

        if ($catalogueFichier !== $catalogueBase) {
            throw new LogicException(
                'La base de données ne correspond pas exactement au catalogue versionné. Lancez d’abord app:catalogue:importer.',
            );
        }

        $nombreImages = 0;

        foreach ($catalogueFichier['stickmans'] as $stickman) {
            $this->verifierImage(
                $dossierProjet.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'stickmen',
                $stickman['image'],
                sprintf('Stickman "%s"', $stickman['slug']),
            );
            ++$nombreImages;
        }

        foreach ($catalogueFichier['caisses'] as $caisse) {
            $this->verifierImage(
                $dossierProjet.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'caisses',
                $caisse['image'],
                sprintf('caisse "%s"', $caisse['slug']),
            );
            ++$nombreImages;
        }

        return [
            'stickmans' => count($catalogueFichier['stickmans']),
            'caisses' => count($catalogueFichier['caisses']),
            'associations' => array_sum(
                array_map(
                    static fn (array $caisse): int => count($caisse['contenus']),
                    $catalogueFichier['caisses'],
                ),
            ),
            'images' => $nombreImages,
        ];
    }

    /**
     * @param list<Stickman> $stickmans
     */
    private function verifierSlugsStickmansUniques(array $stickmans): void
    {
        $occurrences = [];

        foreach ($stickmans as $stickman) {
            $slug = trim((string) $stickman->getSlug());

            if ('' === $slug) {
                throw new LogicException(
                    sprintf(
                        'Le Stickman #%d ne possède pas de slug.',
                        $stickman->getId(),
                    ),
                );
            }

            $occurrences[$slug] = ($occurrences[$slug] ?? 0) + 1;
        }

        $doublons = array_keys(
            array_filter(
                $occurrences,
                static fn (int $nombre): bool => $nombre > 1,
            ),
        );

        if ([] !== $doublons) {
            sort($doublons);

            throw new LogicException(
                sprintf(
                    'Export impossible : les slugs Stickmans suivants sont en double : %s.',
                    implode(', ', $doublons),
                ),
            );
        }
    }

    private function validerCatalogueImporte(mixed $catalogue): void
    {
        if (!is_array($catalogue)) {
            throw new LogicException('Le catalogue JSON doit contenir un objet à sa racine.');
        }

        if (self::VERSION_FORMAT !== ($catalogue['version'] ?? null)) {
            throw new LogicException(
                sprintf(
                    'Version de catalogue non supportée. Version attendue : %d.',
                    self::VERSION_FORMAT,
                ),
            );
        }

        if (!isset($catalogue['stickmans']) || !is_array($catalogue['stickmans']) || !array_is_list($catalogue['stickmans'])) {
            throw new LogicException('La clé "stickmans" doit contenir une liste.');
        }

        if (!isset($catalogue['caisses']) || !is_array($catalogue['caisses']) || !array_is_list($catalogue['caisses'])) {
            throw new LogicException('La clé "caisses" doit contenir une liste.');
        }

        $slugsStickmans = [];

        foreach ($catalogue['stickmans'] as $index => $stickman) {
            if (!is_array($stickman)) {
                throw new LogicException(sprintf('Le Stickman à l’index %d est invalide.', $index));
            }

            $this->validerChampsTexte(
                $stickman,
                ['slug', 'nom', 'description', 'image'],
                sprintf('stickmans[%d]', $index),
            );
            $this->validerEntier($stickman, 'rarete', 1, 5, sprintf('stickmans[%d]', $index));
            $this->validerEntier($stickman, 'pv', 1, null, sprintf('stickmans[%d]', $index));
            $this->validerEntier($stickman, 'attaque', 0, null, sprintf('stickmans[%d]', $index));
            $this->validerEntier($stickman, 'defense', 0, null, sprintf('stickmans[%d]', $index));
            $this->validerBooleen($stickman, 'actif', sprintf('stickmans[%d]', $index));

            if (isset($slugsStickmans[$stickman['slug']])) {
                throw new LogicException(
                    sprintf('Le slug Stickman "%s" apparaît plusieurs fois dans le catalogue.', $stickman['slug']),
                );
            }

            $slugsStickmans[$stickman['slug']] = true;
        }

        $slugsCaisses = [];

        foreach ($catalogue['caisses'] as $index => $caisse) {
            if (!is_array($caisse)) {
                throw new LogicException(sprintf('La caisse à l’index %d est invalide.', $index));
            }

            $contexte = sprintf('caisses[%d]', $index);
            $this->validerChampsTexte($caisse, ['slug', 'nom', 'description', 'image'], $contexte);
            $this->validerEntier($caisse, 'prix', 0, null, $contexte);
            $this->validerBooleen($caisse, 'actif', $contexte);

            if (isset($slugsCaisses[$caisse['slug']])) {
                throw new LogicException(
                    sprintf('Le slug caisse "%s" apparaît plusieurs fois dans le catalogue.', $caisse['slug']),
                );
            }

            $slugsCaisses[$caisse['slug']] = true;

            if (!isset($caisse['contenus']) || !is_array($caisse['contenus']) || !array_is_list($caisse['contenus'])) {
                throw new LogicException(sprintf('La clé "%s.contenus" doit contenir une liste.', $contexte));
            }

            $contenusVus = [];

            foreach ($caisse['contenus'] as $indexContenu => $contenu) {
                if (!is_array($contenu)) {
                    throw new LogicException(
                        sprintf('Le contenu "%s.contenus[%d]" est invalide.', $contexte, $indexContenu),
                    );
                }

                $contexteContenu = sprintf('%s.contenus[%d]', $contexte, $indexContenu);
                $this->validerChampsTexte($contenu, ['stickman'], $contexteContenu);
                $this->validerEntier($contenu, 'poids', 1, null, $contexteContenu);
                $slugStickman = $contenu['stickman'];

                if (!isset($slugsStickmans[$slugStickman])) {
                    throw new LogicException(
                        sprintf(
                            'Le contenu "%s" référence le Stickman inconnu "%s".',
                            $contexteContenu,
                            $slugStickman,
                        ),
                    );
                }

                if (isset($contenusVus[$slugStickman])) {
                    throw new LogicException(
                        sprintf(
                            'Le Stickman "%s" apparaît plusieurs fois dans la caisse "%s".',
                            $slugStickman,
                            $caisse['slug'],
                        ),
                    );
                }

                $contenusVus[$slugStickman] = true;
            }
        }
    }

    /**
     * @param array<string, mixed> $donnees
     * @param list<string>         $champs
     */
    private function validerChampsTexte(array $donnees, array $champs, string $contexte): void
    {
        foreach ($champs as $champ) {
            if (!isset($donnees[$champ]) || !is_string($donnees[$champ]) || '' === trim($donnees[$champ])) {
                throw new LogicException(
                    sprintf('Le champ "%s.%s" doit contenir un texte non vide.', $contexte, $champ),
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $donnees
     */
    private function validerEntier(
        array $donnees,
        string $champ,
        int $minimum,
        ?int $maximum,
        string $contexte,
    ): void {
        $valeur = $donnees[$champ] ?? null;

        if (!is_int($valeur) || $valeur < $minimum || (null !== $maximum && $valeur > $maximum)) {
            throw new LogicException(
                sprintf('Le champ "%s.%s" contient un entier invalide.', $contexte, $champ),
            );
        }
    }

    /**
     * @param array<string, mixed> $donnees
     */
    private function validerBooleen(array $donnees, string $champ, string $contexte): void
    {
        if (!array_key_exists($champ, $donnees) || !is_bool($donnees[$champ])) {
            throw new LogicException(
                sprintf('Le champ "%s.%s" doit être un booléen.', $contexte, $champ),
            );
        }
    }

    /**
     * @return array{version: int, stickmans: list<array<string, mixed>>, caisses: list<array<string, mixed>>}
     *
     * @throws JsonException
     */
    private function lireCatalogue(string $chemin): array
    {
        if (!is_file($chemin) || !is_readable($chemin)) {
            throw new RuntimeException(
                sprintf('Le fichier catalogue "%s" est introuvable ou illisible.', $chemin),
            );
        }

        $contenu = file_get_contents($chemin);

        if (false === $contenu) {
            throw new RuntimeException(
                sprintf('Impossible de lire le catalogue "%s".', $chemin),
            );
        }

        $catalogue = json_decode(
            $contenu,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->validerCatalogueImporte($catalogue);

        /** @var array{version: int, stickmans: list<array<string, mixed>>, caisses: list<array<string, mixed>>} $catalogue */
        return $catalogue;
    }

    private function verifierImage(
        string $dossier,
        string $nomFichier,
        string $contexte,
    ): void {
        if ($nomFichier !== basename($nomFichier)) {
            throw new LogicException(
                sprintf('Le nom d’image du %s est invalide.', $contexte),
            );
        }

        $chemin = $dossier.DIRECTORY_SEPARATOR.$nomFichier;

        if (!is_file($chemin) || !is_readable($chemin)) {
            throw new LogicException(
                sprintf('L’image du %s est introuvable : %s.', $contexte, $chemin),
            );
        }
    }
}
