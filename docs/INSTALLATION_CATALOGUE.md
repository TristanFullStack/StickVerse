# Installation de la base et du catalogue StickVerse

Ce document décrit uniquement l’initialisation des données de jeu. La configuration complète du serveur de production sera traitée séparément.

## Prérequis

- la variable `DATABASE_URL` doit viser la base à initialiser ;
- les dépendances Composer doivent être installées ;
- le fichier `data/catalogue.json` doit être présent ;
- les images du catalogue doivent être déployées dans `public/images/stickmen` et `public/images/caisses`.

Sur un hébergement administré, la base est généralement créée depuis le panneau de contrôle. Il ne faut alors pas lancer `doctrine:database:create`.

## Initialiser une base vide

Exécuter les commandes suivantes depuis la racine du projet :

```shell
php bin/console doctrine:migrations:migrate --env=prod --no-debug --no-interaction
php bin/console app:catalogue:importer data/catalogue.json --env=prod --no-debug --no-interaction
php bin/console app:catalogue:verifier data/catalogue.json --env=prod --no-debug --no-interaction
```

La dernière commande doit confirmer les nombres de Stickmans, de caisses, d’associations et d’images attendus.

## Réexécuter un import

L’import peut être relancé après une interruption ou une mise à jour du catalogue.

Il est idempotent : les éléments existants sont mis à jour grâce à leur slug et les éléments absents sont créés. Il ne supprime aucune donnée automatiquement.

La commande de vérification reste stricte. Elle échoue si la base contient des données de catalogue différentes ou supplémentaires par rapport au fichier versionné.

## Mettre à jour le catalogue versionné

Après une modification volontaire effectuée dans l’administration locale :

```shell
php bin/console app:catalogue:exporter
php bin/console app:catalogue:verifier
git diff -- data/catalogue.json
```

Le fichier exporté et les nouvelles images doivent être ajoutés au même commit.

## Base contenant déjà des données

Avant toute intervention sur une base existante, effectuer une sauvegarde avec les outils proposés par l’hébergeur ou le serveur de base de données.

L’import du catalogue ne modifie pas les comptes, les collections, les équipes ni l’historique des combats. Il crée ou met à jour uniquement :

- les Stickmans ;
- les caisses ;
- les associations entre les caisses et les Stickmans ;
- les poids utilisés pour les probabilités de tirage.

## Résultat de la simulation J48

La procédure a été validée sur une base MySQL temporaire entièrement vide :

- 12 migrations appliquées depuis zéro ;
- 27 Stickmans importés ;
- 1 caisse importée ;
- 10 associations importées ;
- 28 images vérifiées ;
- contrôle final réussi ;
- base temporaire supprimée après la validation.
