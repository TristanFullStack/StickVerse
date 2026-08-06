# Défi StickVerse

## J1
- Installation de PHP, Composer et Symfony CLI.
- Création du projet Symfony.
- Premier commit Git.

## J2
- Création du HomeController.
- Première route.
- Première vue Twig.
- Compréhension du fonctionnement Controller → Twig.

## J3
- Installation et configuration de MySQL.
- Configuration de Doctrine.
- Création de la base `stickverse`.
- Compréhension de DATABASE_URL.
- Activation de l'extension `pdo_mysql`.

## J4

- Création de la première Entity Doctrine : Stickman.
- Compréhension du lien entre une classe PHP et une table SQL.
- Ajout des propriétés métier : nom, slug, description, image, rarete, pv, attaque, defense et statutActif.
- Réflexion sur le choix des types (`string`, `text`, `integer`, `boolean`).
- Choix de stocker la rareté sous forme d'entier plutôt que de texte afin de faciliter les comparaisons et les tris.
- Compréhension du rôle de `private`, des getters et des setters.
- Compréhension des types nullable (`?string`, `?int`, `?bool`) et de `GeneratedValue`.


## J5

- Compréhension du rôle de Doctrine ORM comme traducteur entre PHP et MySQL.
- Compréhension de la différence entre une Entity, une Migration et une table SQL.
- Génération de la première migration avec `php bin/console make:migration`.
- Lecture et analyse du SQL généré automatiquement par Doctrine.
- Compréhension des méthodes `up()` (application) et `down()` (annulation).
- Exécution de la migration avec `php bin/console doctrine:migrations:migrate`.
- Création de la table `stickman` dans la base `stickverse`.
- Découverte de la table `doctrine_migration_versions` utilisée pour suivre les migrations déjà exécutées.
- Vérification de la structure de la table dans MySQL Workbench.