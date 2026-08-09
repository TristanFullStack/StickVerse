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


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


## J6 - Génération et compréhension du premier CRUD Symfony

### Objectif
Créer le premier CRUD du projet StickVerse à partir de l'entité Stickman et comprendre comment les différentes parties de Symfony communiquent entre elles.

### Travail réalisé
- Génération du CRUD de l'entité Stickman avec `php bin/console make:crud`.
- Génération automatique de :
  - `StickmanController.php`
  - `StickmanType.php`
  - `index.html.twig`
  - `show.html.twig`
  - `new.html.twig`
  - `edit.html.twig`
  - `_form.html.twig`
  - `_delete_form.html.twig`
- Découverte des différentes opérations CRUD :
  - Create : `new()`
  - Read : `index()` et `show()`
  - Update : `edit()`
  - Delete : `delete()`
- Compréhension du système de routes Symfony.
- Différenciation entre l'URL d'une route et son nom interne.
- Compréhension du rôle du Repository et de `findAll()`.
- Compréhension du passage de données du Controller vers Twig.
- Découverte de `StickmanType` et de son lien avec l'entité Stickman.
- Compréhension du principe DRY avec `_form.html.twig`, réutilisé pour la création et la modification.
- Découverte de `Request`, `handleRequest()`, `isSubmitted()` et `isValid()`.
- Compréhension de la différence entre `persist()` et `flush()`.
- Découverte du principe du token CSRF pour protéger la suppression.
- Test du CRUD directement dans le navigateur.
- Création du premier Stickman de StickVerse :
  - Nom : Stickman Test
  - Slug : stickman-test
  - PV : 100
  - Attaque : 20
  - Défense : 10
  - Rareté : 1
  - Statut actif : oui
- Vérification de l'affichage de la liste des Stickman.
- Test de la page individuelle `show`.
- Test du formulaire `edit`.

### Difficultés et erreurs de compréhension
- Je pensais au départ que Symfony choisissait le premier Controller disponible. J'ai compris que Symfony choisit l'action à exécuter grâce à la route correspondant à l'URL demandée.
- J'ai d'abord confondu le chemin d'une route, par exemple `/stickman/{id}`, avec son nom interne comme `app_stickman_show`.
- Le fonctionnement de `'stickmen' => $stickmanRepository->findAll()` était encore flou : j'ai compris que `findAll()` récupère les Stickman tandis que `stickmen` est le nom de la variable transmise au template Twig.
- Je pensais que `StickmanType` servait principalement à vérifier les informations. J'ai compris qu'il sert surtout à définir/construire le formulaire associé à l'entité Stickman.
- J'ai inversé au départ le rôle de `persist()` et `flush()`. `persist()` indique à Doctrine qu'un nouvel objet doit être géré/enregistré, tandis que `flush()` synchronise réellement les changements avec la base de données.
- J'ai compris pourquoi `edit()` n'a pas besoin de `persist()` : l'objet récupéré depuis la base est déjà géré par Doctrine.
- Beaucoup de concepts restent encore nouveaux et certaines parties sont encore floues, mais je commence à comprendre le chemin global d'une requête Symfony.

### Ce que je retiens
Le CRUD généré par Symfony repose sur plusieurs éléments qui ont chacun une responsabilité :

`Route -> Controller -> Entity/Repository -> Doctrine -> MySQL -> Controller -> Twig -> Navigateur`

Pour les formulaires :

`Entity -> StickmanType -> Controller -> Twig -> Request -> Doctrine -> MySQL`

Symfony automatise énormément de code répétitif, mais il reste nécessaire de comprendre l'architecture pour pouvoir modifier le CRUD et ajouter la logique métier du projet.

### Temps passé
Environ 4 heures.


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J7 - Nettoyage et personnalisation du CRUD Stickman

### Objectif
Nettoyer le CRUD généré automatiquement par Symfony et commencer à personnaliser le formulaire et les templates Twig.

### Travail réalisé
- Découverte et personnalisation de `StickmanType.php`.
- Utilisation explicite des types de formulaire Symfony :
  - `TextType`
  - `TextareaType`
  - `IntegerType`
  - `CheckboxType`
- Ajout de labels personnalisés aux champs.
- Compréhension de la structure `->add('propriete', Type::class, options)`.
- Nettoyage de `index.html.twig`.
- Suppression de l'affichage des colonnes inutiles :
  - id
  - slug
  - description
  - image
- Conservation des colonnes utiles :
  - nom
  - rareté
  - PV
  - attaque
  - défense
  - statut actif
  - actions
- Modification du `colspan` de 11 à 7.
- Test des pages index, new, edit et show après modification.

### Difficultés / erreurs rencontrées
- J'ai importé par erreur `Doctrine\DBAL\Types\IntegerType` au lieu du `IntegerType` du composant Form de Symfony.
- PHP signalait alors `Duplicate symbol declaration 'IntegerType'`.
- J'ai compris que Doctrine et Symfony Form sont deux couches différentes.
- J'ai essayé d'utiliser `FileType` pour l'image avant de comprendre qu'un `FileType` fournit un fichier alors que la propriété `image` de mon Entity contient actuellement une string.
- L'upload réel des images sera traité plus tard.
- J'ai utilisé des commentaires Twig pour masquer certaines colonnes avant de comprendre qu'il était préférable de supprimer le code devenu inutile puisque Git conserve l'historique.

### Ce que je retiens
`StickmanType` définit la structure du formulaire associé à l'objet Stickman.

Exemple :

`->add('nom', TextType::class, ['label' => 'Nom du Stickman'])`

- `nom` = propriété de l'objet Stickman.
- `TextType::class` = type de champ du formulaire Symfony.
- `label` = texte affiché à l'utilisateur.

Twig contrôle l'affichage HTML tandis que le Controller fournit les données au template.

Les types de formulaire ne sont pas les mêmes choses que les types PHP ou SQL.

### Temps passé
Environ 1 heure.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J8 - Ajout des validations Symfony

### Objectif

Ajouter des règles de validation à l'entité Stickman afin d'empêcher l'enregistrement de données vides ou incohérentes dans l'application.

### Travail réalisé

- Découverte du système de validation de Symfony.
- Import des contraintes avec :
  - `use Symfony\Component\Validator\Constraints as Assert;`
- Ajout de `NotBlank` sur les propriétés obligatoires :
  - nom
  - slug
  - description
  - image
- Ajout de `Range` sur la rareté afin d'accepter uniquement les valeurs comprises entre 1 et 5.
- Ajout de `Positive` sur les PV afin d'imposer une valeur strictement supérieure à 0.
- Ajout de `PositiveOrZero` sur l'attaque et la défense afin d'autoriser 0 mais d'interdire les valeurs négatives.
- Ajout de messages d'erreur personnalisés aux différentes contraintes.
- Test volontaire du formulaire avec des données invalides.
- Test d'une rareté à `999`, correctement refusée.
- Test simultané avec :
  - rareté = 0
  - PV = 0
  - attaque = -10
  - défense = -50
- Vérification que Symfony affiche plusieurs erreurs de validation simultanément.
- Test des champs texte vides pour vérifier les contraintes `NotBlank`.
- Création finale d'un Stickman avec des données valides pour vérifier que les validations n'empêchent pas un enregistrement correct.
- Création réussie du Stickman `Test Validation`.

### Difficultés / erreurs rencontrées

- Au début, la différence entre les responsabilités de `StickmanType.php` et de l'entité `Stickman.php` n'était pas encore totalement claire.
- J'ai compris que `StickmanType` définit principalement comment les données sont saisies dans le formulaire, alors que les contraintes placées dans `Stickman.php` définissent les règles que les données doivent respecter.
- J'ai dû comprendre la différence entre `Positive` et `PositiveOrZero`.
- Les tests avec volontairement de mauvaises valeurs m'ont permis de mieux comprendre le fonctionnement de `isValid()`.
- J'ai constaté un code HTTP `422` lorsque le formulaire contenait des données invalides et compris que le formulaire était bien traité mais refusé à cause des erreurs de validation.

### Ce que je retiens

Les contraintes Symfony permettent de protéger la cohérence des données avant leur enregistrement.

Principales contraintes découvertes :

- `NotBlank` = valeur obligatoire.
- `Range` = valeur comprise entre un minimum et un maximum.
- `Positive` = valeur strictement supérieure à 0.
- `PositiveOrZero` = valeur supérieure ou égale à 0.
- `message` / `notInRangeMessage` = personnalisation du message d'erreur.

Je comprends mieux la séparation des responsabilités :

`StickmanType -> définit comment saisir les données`

`Stickman + Assert -> définit quelles données sont valides`

Le fonctionnement général que je retiens est :

`Formulaire -> Stickman -> Validation Assert -> isValid() -> persist()/flush() -> MySQL`

Si les contraintes échouent, `isValid()` retourne faux et l'enregistrement n'est pas effectué.

J'ai également compris qu'aucune migration Doctrine n'était nécessaire aujourd'hui car je n'ai pas modifié la structure de la table SQL. J'ai uniquement ajouté des règles de validation dans le code PHP.

### Temps passé

Environ 1 heure.


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J9 - Affichage et sélection dynamique des images des Stickmen

### Objectif

Permettre à chaque Stickman d'utiliser une image présente dans le projet et automatiser la sélection des images disponibles dans le formulaire Symfony.

### Travail réalisé

- Création/utilisation du dossier `public/images/stickmen/` pour stocker les images des Stickmen.
- Ajout des premières images PNG des personnages.
- Compréhension du rôle du dossier `public` dans Symfony.
- Affichage de l'image d'un Stickman dans `show.html.twig`.
- Utilisation de la fonction Twig `asset()` pour générer le chemin public vers une image.
- Construction dynamique du chemin de l'image avec l'opérateur Twig `~`.
- Ajout des images dans `index.html.twig` afin d'afficher une miniature pour chaque Stickman.
- Utilisation temporaire de l'attribut CSS `style` pour contrôler la largeur des images.
- Remplacement du champ texte `image` du formulaire par un `ChoiceType`.
- Première version du `ChoiceType` avec une liste d'images écrite manuellement.
- Identification d'un problème de scalabilité : il aurait fallu modifier le PHP à chaque nouvelle image.
- Automatisation de la récupération des images disponibles dans `public/images/stickmen/`.
- Utilisation de `glob()` pour rechercher automatiquement les fichiers `.png`.
- Utilisation de `basename()` pour récupérer uniquement le nom du fichier.
- Utilisation d'une boucle `foreach` pour construire dynamiquement le tableau `$choixImages`.
- Utilisation de `dirname(__DIR__, 2)` pour récupérer le chemin absolu vers la racine du projet.
- Utilisation de `dd()` pour inspecter les valeurs pendant le débogage.
- Injection du tableau `$choixImages` dans l'option `choices` du `ChoiceType`.
- Test du système : l'ajout d'une nouvelle image PNG dans le dossier la rend automatiquement disponible dans le formulaire.
- Ajout des 10 premiers Stickmen de StickVerse avec leurs images et leurs statistiques.

### Difficultés / erreurs rencontrées

- Au départ, j'utilisais directement :

`glob('public/images/stickmen/*.png')`

mais aucune image n'était trouvée.

- J'ai utilisé `dd($images)` et obtenu un tableau vide `[]`, ce qui m'a permis de comprendre que le problème venait du chemin utilisé par PHP.
- J'ai ensuite utilisé :

`dirname(__DIR__, 2)`

pour retrouver la racine réelle du projet.
- J'ai vérifié le résultat avec `dd()` et obtenu le chemin de mon projet :

`C:\Users\pelle\StickVerse`

- J'ai ensuite construit le chemin complet vers les images avant d'utiliser `glob()`.
- J'ai compris que `glob()` retourne le chemin des fichiers alors que `basename()` permet de ne conserver que leur nom.
- J'ai eu du mal à comprendre comment transformer les fichiers récupérés en choix utilisables par `ChoiceType`.
- J'ai compris qu'il fallait construire un tableau sous la forme :

`nom affiché => valeur enregistrée`

- J'avais initialement écrit les trois images directement dans `choices`, mais j'ai compris que cette méthode ne serait pas viable avec des dizaines ou centaines de Stickmen.
- J'ai également rencontré des difficultés avec l'autocomplétion HTML/Emmet dans les fichiers `.html.twig`, notamment pour les balises et attributs HTML.

### Ce que je retiens

Une image n'est pas enregistrée directement dans la base de données.

La base contient seulement son nom, par exemple :

`Card02Archer.png`

Le fichier réel se trouve dans :

`public/images/stickmen/`

Twig peut ensuite reconstruire son URL :

`asset('images/stickmen/' ~ stickman.image)`

Pour automatiser le formulaire :

`dirname()` → retrouve la racine du projet.

`glob()` → recherche les fichiers correspondant à un motif.

`foreach` → parcourt les fichiers trouvés.

`basename()` → retire le chemin et conserve uniquement le nom du fichier.

`$choixImages[$nomImage] = $nomImage` → construit les choix du formulaire.

`ChoiceType` → affiche ces choix dans une liste déroulante.

Le flux global est donc :

`Dossier PNG -> glob() -> foreach -> basename() -> $choixImages -> ChoiceType -> Entity -> BDD -> Twig -> asset() -> Navigateur`

Le système est maintenant dynamique : ajouter une nouvelle image `.png` dans le dossier suffit pour qu'elle soit proposée automatiquement dans le formulaire, sans modifier manuellement la liste des images dans le PHP.

### Résultat

Les 10 premiers Stickmen de StickVerse sont maintenant présents dans l'application avec leurs images et leurs statistiques :

- Guerrier
- Archer
- Lancier
- Tank
- Assassin
- Mage
- Berserker
- Double Lancier
- Ultra Mage
- Roi Stick

### Temps passé

Environ 3 heures.


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////





///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////





///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



