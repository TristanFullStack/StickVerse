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

## J10 — Wiki public dynamique — 12/08/2026

### Objectif

Créer la page publique `/wiki` et afficher automatiquement les Stickmans actifs enregistrés dans MySQL.

### Travail réalisé

- Création du `WikiController`.
- Injection de `StickmanRepository` dans la méthode `index()`.
- Utilisation de `findBy()` avec le critère `statutActif => true`.
- Transmission du tableau `$stickmen` au template Twig.
- Création d’une boucle Twig pour afficher chaque Stickman.
- Affichage dynamique du nom, de l’image, de la rareté et des statistiques.

### Ce que j’ai compris

Le navigateur demande `/wiki`. Symfony trouve la route et exécute `WikiController`. Le Controller demande les données au Repository. Doctrine transforme la recherche en requête SQL vers MySQL, puis reconstruit des objets `Stickman`. Le Controller transmet ces objets à Twig sous le nom `stickmen`. Twig parcourt ensuite le tableau avec une boucle `for` et génère le HTML.

### Erreur rencontrée

Après avoir supprimé la variable `controller_name` du Controller, le template généré par Symfony essayait encore de l’afficher. Twig a donc produit une `RuntimeError`. J’ai remplacé cette ancienne variable par un titre fixe.

### Tests effectués

- `dd()` a confirmé la récupération des Entities.
- Le wiki affiche les Stickmans actifs.
- Le Guerrier désactivé a immédiatement disparu du wiki.
- Un nouveau Stickman actif apparaît automatiquement.
- Les images et les statistiques s’affichent correctement.

### Résultat

La page `/wiki` est maintenant entièrement alimentée par MySQL. Aucun Stickman n’est écrit manuellement dans le HTML.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J11 — Fiche publique dynamique avec slug — 12/08/2026

### Objectif

Créer une page publique différente pour chaque Stickman avec une URL basée sur son slug, par exemple `/wiki/archer`.

### Travail réalisé

- Création de la route dynamique `/wiki/{slug}`.
- Récupération du slug depuis l’URL.
- Utilisation de `findOneBy()` pour récupérer un seul Stickman actif.
- Création d’une erreur 404 si le Stickman n’existe pas ou est inactif.
- Création du template public `wiki/show.html.twig`.
- Transmission de l’objet `stickman` du Controller vers Twig.
- Ajout d’un lien « Voir la fiche » pour chaque Stickman du wiki.
- Génération automatique des URLs avec la fonction Twig `path()`.

### Ce que j’ai compris

`findBy()` renvoie un tableau de plusieurs résultats, tandis que `findOneBy()` renvoie un seul objet ou `null`.

Le slug présent dans l’URL devient la variable PHP `$slug`. Le Repository recherche ensuite l’Entity correspondante dans MySQL.

La fonction Twig `path()` utilise le nom de la route et le slug du Stickman pour générer automatiquement son URL.

### Difficulté rencontrée

Le fichier `templates/wiki/show.html.twig` n’existait pas encore. J’ai compris qu’il fallait le créer et ne pas utiliser `templates/stickman/show.html.twig`, qui appartient au CRUD.

### Résultat

Chaque Stickman actif possède maintenant une fiche publique dynamique accessible depuis le wiki.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J12 — Cartes wiki responsive — 12/08/2026

### Objectif

Transformer la liste publique des Stickmans en grille de cartes responsive.

### Travail réalisé

- Identification du chargement CSS avec `importmap('app')`.
- Vérification que `assets/app.js` importe `assets/styles/app.css`.
- Remplacement du fond bleu Symfony par un style plus propre.
- Création d’une grille responsive avec CSS Grid.
- Mise en forme des cartes, images, statistiques et liens.
- Utilisation de Flexbox pour aligner les boutons.
- Ajout d’un effet au survol.
- Ajout d’une Media Query pour les écrans mobiles.

### Ce que j’ai compris

`repeat(auto-fit, minmax(230px, 1fr))` adapte automatiquement le nombre de colonnes à la largeur disponible.

`object-fit: contain` conserve les proportions des images sans les déformer.

La Media Query applique des règles spécifiques lorsque l’écran mesure moins de 600 pixels.

### Résultat

Le wiki affiche maintenant les Stickmans sous forme de cartes. La grille s’adapte automatiquement aux ordinateurs, tablettes et mobiles.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J13 — Ajout des vrais Stickmans — 12/08/2026

### Travail réalisé

Les 10 premiers Stickmans réels de StickVerse sont enregistrés dans MySQL avec leur nom, slug, description, image, rareté et statistiques.

Le wiki les récupère et les affiche automatiquement.

### Ce que j’ai compris

Les Stickmans créés depuis le CRUD sont des données stockées dans MySQL. Ils ne sont pas enregistrés directement dans Git.

Une migration conserve la structure de la base de données, mais pas les lignes créées depuis les formulaires.

### Résultat

StickVerse possède maintenant une première collection réelle de 10 Stickmans utilisables dans le wiki.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J14 — Vérifications et version stable — 12/08/2026

### Vérifications effectuées

- Les 10 templates Twig ont une syntaxe valide.
- Le conteneur Symfony et les injections de dépendances sont valides.
- Le mapping Doctrine est correct.
- La structure MySQL est synchronisée avec les Entities.

### Résultat

La première partie de StickVerse est stable : CRUD, validations, images, wiki dynamique, fiches par slug et responsive fonctionnent correctement.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J15 — Entity User — 12/08/2026

### Travail réalisé

- Génération de l’Entity `User` avec `make:user`.
- Utilisation de l’email comme identifiant unique.
- Ajout des rôles et du mot de passe haché.
- Création de `UserRepository`.
- Mise à jour de la configuration Security.
- Génération et exécution de la migration créant la table `user`.

### Ce que j’ai compris

L’Entity représente un compte dans PHP et Doctrine. La migration transforme cette structure en table MySQL.

Chaque utilisateur possède automatiquement `ROLE_USER`. Le champ `password` contiendra uniquement un hash sécurisé, jamais le mot de passe brut.

### Résultat

StickVerse peut maintenant enregistrer des comptes utilisateurs en base de données.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J16 — Inscription, connexion et déconnexion — 12/08/2026

### Travail réalisé

- Création du formulaire d’inscription.
- Validation de l’email, du mot de passe et des conditions.
- Hachage sécurisé du mot de passe avant enregistrement.
- Enregistrement du compte dans MySQL.
- Connexion automatique après inscription.
- Création du formulaire de connexion.
- Ajout de la déconnexion.
- Activation de la protection CSRF.
- Redirections vers le wiki après connexion et déconnexion.

### Ce que j’ai compris

Le formulaire récupère le mot de passe brut, mais seul son hash est enregistré en BDD.

Le firewall Symfony traite la connexion et la déconnexion. Le Controller de connexion affiche principalement le formulaire et les erreurs.

### Problème rencontré

Après déconnexion, Symfony redirigeait vers `/`, une route inexistante, ce qui provoquait une page 404. J’ai configuré `default_target_path` et `target` vers `app_wiki`.

### Résultat

Un visiteur peut créer un compte, se connecter et se déconnecter correctement.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J17 — Protection de l’administration — 12/08/2026

### Travail réalisé

- Déplacement du CRUD Stickman sous `/admin/stickman`.
- Attribution de `ROLE_ADMIN` à mon compte.
- Protection de toutes les URL commençant par `/admin`.
- Redirection des visiteurs non connectés vers `/login`.
- Retour automatique vers la page demandée après connexion.

### Ce que j’ai compris

`access_control` vérifie les URL dans l’ordre. La règle `^/admin` exige désormais `ROLE_ADMIN`.

Un visiteur non connecté est redirigé vers la connexion. Un utilisateur connecté sans le bon rôle reçoit une erreur 403. Un administrateur peut accéder au CRUD.

Les rôles sont des données MySQL : leur modification ne nécessite pas de migration.

### Difficulté rencontrée

phpMyAdmin ne pouvait pas se connecter avec les bons identifiants MySQL. J’ai utilisé `dbal:run-sql` pour modifier directement le rôle depuis Symfony.

### Résultat

Le CRUD Stickman n’est plus accessible publiquement et constitue maintenant une véritable zone d’administration.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J18 — Entity Caisse — 12/08/2026

### Travail réalisé

- Création de l’Entity `Caisse`.
- Ajout des champs `nom`, `slug`, `description`, `image`, `prix` et `statutActif`.
- Ajout d’une contrainte unique sur le slug.
- Création de `CaisseRepository`.
- Génération et exécution de la migration.
- Création de la table `caisse` dans MySQL.

### Ce que j’ai compris

Le prix est stocké sous forme d’entier pour représenter des pièces virtuelles ou, plus tard, des centimes sans erreur d’arrondi.

Le slug unique permettra d’identifier précisément une caisse dans une future URL publique.

### Résultat

La structure permettant d’enregistrer les caisses de StickVerse existe maintenant dans PHP et MySQL.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J19 — CRUD des caisses — 12/08/2026

### Travail réalisé

- Génération de `CaisseController`.
- Génération de `CaisseType`.
- Création des templates index, new, show, edit et delete.
- Déplacement du CRUD vers `/admin/caisse`.
- Protection automatique grâce à `ROLE_ADMIN`.
- Création d’une première caisse.
- Modification de son prix de 100 à 120.

### Ce que j’ai compris

Le Controller orchestre les actions CRUD, `CaisseType` construit le formulaire et Twig affiche les pages.

Les noms des routes ne changent pas lorsque le préfixe passe de `/caisse` à `/admin/caisse`. Les liens générés avec `path()` continuent donc de fonctionner.

La caisse créée est stockée dans MySQL et non dans Git.

### Résultat

L’administrateur peut créer, consulter, modifier et supprimer des caisses depuis une zone protégée.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J20 — Relation caisses / Stickmans et probabilités — 15/08/2026

### Objectif

Relier les caisses aux Stickmans et définir leurs probabilités de tirage.

### Réalisation

- Création de l’Entity d’association `CaisseStickman`.
- Relation `ManyToOne` vers `Caisse`.
- Relation `ManyToOne` vers `Stickman`.
- Ajout d’un poids de tirage positif.
- Ajout d’une contrainte empêchant un même Stickman d’être ajouté deux fois dans une caisse.
- Création et exécution de la migration.
- Création du CRUD d’administration.
- Affichage du nom de la caisse et du Stickman.
- Calcul dynamique des probabilités.

### Compréhension

Une relation `ManyToMany` simple ne suffisait pas, car la relation elle-même devait posséder une information : le poids de tirage.

La probabilité est calculée ainsi :

`poids du Stickman / poids total de la caisse × 100`

Le pourcentage n’est pas enregistré en base. Il est calculé automatiquement à partir des poids.

### Erreurs rencontrées

- La validation `Positive` avait d’abord été placée sur l’identifiant au lieu du poids.
- Le type `Caisse` avait été écrit avec une minuscule.
- `choice_label` utilisait `name`, alors que la propriété française de mes Entities est `nom`.
- Le CRUD généré n’affichait pas automatiquement les relations.

### Tests

- Guerrier : poids 60 → 60 %.
- Archer : poids 30 → 30 %.
- Tank : poids 10 → 10 %.
- Un doublon Caisse/Stickman est correctement refusé.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J21 — Service d’ouverture aléatoire pondérée — 15/08/2026

### Objectif

Créer un service PHP capable de choisir un Stickman selon les poids définis dans une caisse.

### Réalisation

- Création de `OuvertureCaisseService`.
- Récupération des contenus de la caisse.
- Gestion du cas d’une caisse vide.
- Calcul du poids total.
- Génération d’un nombre avec `random_int()`.
- Parcours des contenus avec un poids cumulé.
- Retour du Stickman correspondant à la zone tirée.
- Test du service avec une route temporaire d’administration.

### Compréhension

Avec les poids 60, 30 et 10, le service construit implicitement les zones suivantes :

- 1 à 60 : Guerrier ;
- 61 à 90 : Archer ;
- 91 à 100 : Tank.

Le contrôleur ne réalise pas le tirage lui-même. Il appellera le service, ce qui sépare la logique métier de la gestion HTTP.

Symfony détecte automatiquement le service grâce à l’autowiring.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J22 — Page publique des caisses — 15/08/2026

### Objectif

Créer une page publique affichant automatiquement les caisses actives.

### Réalisation

- Création de `CaissePubliqueController`.
- Création de la route publique `/caisses`.
- Injection de `CaisseRepository`.
- Récupération des caisses avec `statutActif = true`.
- Transmission de la liste à Twig.
- Création d’une boucle affichant le nom, l’image, la description et le prix.
- Ajout du dossier public contenant les images des caisses.

### Erreur rencontrée

L’image ne s’affichait pas alors que son URL directe fonctionnait.

La base contenait `caisse-commune.png`, mais le véritable fichier s’appelait `caisse-image-commune.png`. La valeur enregistrée dans MySQL doit correspondre exactement au nom du fichier.

### Tests

- Une caisse active apparaît sur `/caisses`.
- Une caisse inactive disparaît.
- Le message « Aucune caisse disponible » fonctionne.
- L’image enregistrée dans la base est chargée depuis `public/images/caisses`.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J23 — Inventaire utilisateur — 16/08/2026

### Objectif

Créer la structure permettant à chaque utilisateur de posséder des Stickmans, y compris plusieurs exemplaires du même Stickman.

### Réalisation

- Création de l’Entity `Inventaire`.
- Ajout du champ `quantite`.
- Relation `ManyToOne` entre `Inventaire` et `User`.
- Relation `ManyToOne` entre `Inventaire` et `Stickman`.
- Ajout de la collection `inventaires` dans `User`.
- Quantité initialisée à 1 et obligatoirement positive.
- Ajout d’une contrainte unique utilisateur/Stickman.
- Création et exécution de la migration.
- Validation du mapping Doctrine et du schéma MySQL.

### Compréhension

Une ligne d’inventaire représente :

`un utilisateur + un Stickman + une quantité`

La contrainte unique n’empêche pas les doublons. Elle empêche plusieurs lignes identiques.

Si un utilisateur possède trois Guerriers, la base stocke :

`Tristan + Guerrier + quantité 3`

Lorsqu’il gagnera un autre Guerrier, le programme augmentera la quantité au lieu de créer une nouvelle ligne.

### Erreurs rencontrées

- J’ai d’abord saisi `Inventaire` comme nom de propriété au lieu de `quantite`.
- J’ai terminé Maker avant d’ajouter la relation `stickman`.
- J’ai ensuite écrit `yes` et `stickman` directement dans PowerShell après la fin de Maker.
- J’ai relancé `make:entity Inventaire` pour compléter proprement l’Entity.

### Tests

- La migration a exécuté trois requêtes SQL.
- Le mapping Doctrine est correct.
- Le schéma MySQL est synchronisé avec les Entities.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J24 — Relier ouverture de caisse et inventaire — 16/08/2026

### Objectif

Enregistrer automatiquement dans l’inventaire le Stickman obtenu lors de l’ouverture d’une caisse.

### Réalisation

- Injection de `InventaireRepository` dans `OuvertureCaisseService`.
- Injection de `EntityManagerInterface`.
- Transmission de l’utilisateur connecté au service.
- Recherche d’une ligne utilisateur/Stickman existante.
- Création d’une ligne avec quantité 1 lors de la première obtention.
- Augmentation de la quantité lors d’un doublon.
- Création d’une route POST protégée par `ROLE_USER`.
- Ajout d’une protection CSRF.
- Création du formulaire Twig d’ouverture.
- Affichage du résultat avec un message flash.

### Compréhension

Le contrôleur récupère l’utilisateur connecté et demande au service d’ouvrir la caisse.

Le service effectue le tirage puis recherche dans `InventaireRepository` si l’utilisateur possède déjà le Stickman.

- Première obtention : `persist()` puis `flush()`.
- Doublon : modification de la quantité puis `flush()`.

Doctrine surveille déjà une Entity récupérée depuis MySQL. Il n’est donc pas nécessaire d’appeler `persist()` pour augmenter sa quantité.

### Erreur rencontrée

Le code PHP du contrôleur a momentanément été collé dans le template Twig. Twig l’a traité comme du texte et le navigateur a affiché le code source sur la page.

Chaque langage doit rester dans son fichier :

- PHP dans `src/Controller`;
- Twig dans `templates`.

### Tests

- Deux Stickmans différents ont créé deux lignes d’inventaire.
- Un deuxième Archer n’a pas créé de doublon.
- La quantité de l’Archer est passée de 1 à 2.
- Le formulaire utilise une requête POST et un jeton CSRF.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J25 — Page Ma collection — 16/08/2026

### Objectif

Créer une page privée permettant à chaque utilisateur de consulter les Stickmans présents dans son inventaire.

### Réalisation

- Création de `CollectionController`.
- Création de la route `/ma-collection`.
- Protection de la route avec `ROLE_USER`.
- Récupération de l’utilisateur connecté avec `getUser()`.
- Recherche de ses lignes avec `InventaireRepository`.
- Transmission des inventaires à Twig.
- Affichage des cartes Stickmans et de leurs quantités.
- Ajout d’un lien vers chaque fiche du wiki.
- Gestion du cas d’une collection vide.

### Compréhension

Le Repository ne récupère pas tous les inventaires. Il applique ce filtre :

`utilisateur = utilisateur connecté`

Chaque ligne `Inventaire` permet ensuite d’accéder :

- à la quantité avec `inventaire.quantite` ;
- au Stickman associé avec `inventaire.stickman` ;
- aux propriétés du Stickman avec `inventaire.stickman.nom`, `image`, `pv`, etc.

Le raccourci Twig `{% set stickman = inventaire.stickman %}` évite de répéter `inventaire.stickman` partout.

### Erreur rencontrée

Le template généré utilisait encore la variable `controller_name`, alors que le nouveau contrôleur transmettait seulement `inventaires`.

Twig a donc signalé que `controller_name` n’existait pas. Le remplacement du template généré par la vraie page de collection a corrigé l’erreur.

### Tests

- L’utilisateur connecté voit ses Stickmans.
- Les quantités correspondent aux données de MySQL.
- Les images et les statistiques s’affichent.
- Les liens vers les fiches du wiki fonctionnent.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J26 — Création et sauvegarde d’une équipe — 16/08/2026

### Objectif

Permettre à un utilisateur connecté de composer et sauvegarder son équipe de quatre Stickmans possédés :

- A + B = équipe X ;
- C + D = équipe Y.

### Modèle de données

Création de l’Entity `Equipe` avec :

- `id` ;
- `nom` ;
- une relation vers `User` ;
- `stickmanA` ;
- `stickmanB` ;
- `stickmanC` ;
- `stickmanD`.

Les équipes X et Y ne sont pas enregistrées séparément. Elles sont déduites des quatre emplacements.

### Formulaire

Création de `EquipeType`.

Le champ `utilisateur` généré automatiquement a été retiré du formulaire. Le propriétaire est défini directement avec l’utilisateur connecté afin d’empêcher un utilisateur de créer une équipe pour quelqu’un d’autre.

Les quatre `EntityType` affichent le nom des Stickmans et utilisent uniquement les Stickmans présents dans l’inventaire de l’utilisateur.

### Controller

Création de `EquipeController`.

Trajet des données :

Navigateur  
→ formulaire POST  
→ `EquipeController`  
→ `InventaireRepository`  
→ Stickmans possédés  
→ Entity `Equipe`  
→ Doctrine  
→ MySQL  
→ redirection GET  
→ formulaire prérempli.

Le Controller recherche d’abord l’équipe existante de l’utilisateur. Si elle n’existe pas, il crée une nouvelle Entity `Equipe`.

### Règles métier

- Il faut posséder au moins quatre Stickmans différents.
- Un même Stickman ne peut pas occuper plusieurs emplacements.
- Les doublons restent autorisés dans l’inventaire grâce à `quantite`, mais ils ne permettent pas d’utiliser deux fois le même Stickman dans l’équipe.
- La route `/equipe` est réservée aux utilisateurs connectés avec `ROLE_USER`.

### Erreurs et raisonnements

J’ai oublié d’ajouter `stickmanC` pendant la première génération de l’Entity.

Après l’avoir ajouté, il apparaissait après `stickmanD`. Cela n’aurait pas empêché Doctrine de fonctionner, car Doctrine utilise les noms des propriétés et non leur position dans le fichier. J’ai néanmoins remis les propriétés dans l’ordre A, B, C, D pour conserver un code plus lisible.

La première migration avait été générée avant cette correction. Comme elle n’avait pas encore été exécutée, je l’ai supprimée puis régénérée proprement.

Lors du test avec Guerrier dans les quatre emplacements, Symfony a renvoyé un code HTTP 422. Ce n’était pas une panne : le formulaire avait bien été reçu, mais la règle métier interdisant les doublons avait refusé les données.

### Tests effectués

- Blocage lorsque l’inventaire ne contient que trois Stickmans différents.
- Affichage du formulaire après l’obtention d’un quatrième Stickman.
- Refus d’une équipe contenant plusieurs fois le même Stickman.
- Sauvegarde d’une équipe valide.
- Redirection après sauvegarde.
- Réaffichage des choix enregistrés.
- Vérification directe de la table `equipe` avec SQL.
- Validation du mapping Doctrine et synchronisation avec MySQL.

### Résultat

L’utilisateur connecté peut maintenant créer, sauvegarder et modifier son équipe principale avec quatre Stickmans différents provenant réellement de son inventaire.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J27 — Créer le noyau du moteur de combat — 16/08/2026

### Objectif

Commencer le moteur de combat Symfony sans intégrer directement tout le prototype JavaScript V24.

La première étape consiste à isoler les calculs fondamentaux dans un service PHP indépendant du navigateur, de Twig et de MySQL.

### Service créé

Création de :

`src/Service/CombatService.php`

Le service contient les méthodes suivantes :

- `calculerImpact()` ;
- `calculerAttaqueTotale()` ;
- `calculerDefenseTotale()` ;
- `resoudreCible()`.

### Formule des dégâts

La formule principale est :

`max(0, attaque totale - défense totale)`

Le service calcule également :

- les dégâts calculés ;
- les dégâts réellement subis ;
- les PV restants ;
- l’overkill lorsque les dégâts dépassent les PV disponibles.

### Puissance des équipes

Les statistiques des membres d’un duo sont additionnées.

Exemple avec Guerrier et Archer :

- attaque : `2 + 4 = 6` ;
- défense : `2 + 1 = 3`.

Les méthodes acceptent une liste de Stickmans. Plus tard, les Stickmans KO seront simplement retirés de cette liste avant le calcul.

### Focus et défense

La méthode `resoudreCible()` reçoit :

- les Stickmans qui attaquent la cible ;
- les Stickmans qui défendent la cible ;
- les PV actuels de la cible.

Elle permet déjà de représenter :

- une attaque de l’équipe X ;
- une attaque de l’équipe Y ;
- un focus X+Y ;
- une défense simple ;
- une double défense ;
- une cible sans défense.

### Tests PHPUnit

Création de :

`tests/Service/CombatServiceTest.php`

Scénarios testés :

- impact normal ;
- défense supérieure à l’attaque ;
- KO avec overkill ;
- calcul de l’attaque et de la défense d’un duo ;
- focus X+Y contre une défense simple.

Résultat :

- 5 tests réussis ;
- 16 assertions réussies.

### Erreur rencontrée

PHPUnit a temporairement indiqué que la méthode `resoudreCible()` était inconnue.

La méthode était correctement écrite, mais le test avait été lancé avant l’enregistrement du fichier `CombatService.php`.

Après avoir enregistré le fichier et relancé PHPUnit, tous les tests sont passés.

### Ce que j’ai compris

Un test unitaire appelle directement une classe PHP sans navigateur, Controller, Twig ou base de données.

Cela permet de savoir immédiatement si une erreur vient du moteur de calcul lui-même.

Le moteur est construit progressivement par modules testables afin d’éviter de mélanger les erreurs de calcul, d’interface, de requête HTTP et de persistance.

### Résultat

Le noyau mathématique du futur combat StickVerse est fonctionnel et testé.

La résolution complète et simultanée d’un round sera construite à partir de cette base lors de la prochaine étape.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J28 — Résolution simultanée d’un round — 16/08/2026

### Objectif

Permettre au moteur de calculer plusieurs impacts pendant un même round sans modifier immédiatement les PV.

Cette règle est importante car les choix des deux joueurs sont secrets et simultanés.

### Méthode ajoutée

Ajout de `resoudreRound()` dans `CombatService`.

Cette méthode reçoit un tableau d’impacts. Pour chaque cible, elle reçoit :

- les Stickmans attaquants ;
- les Stickmans défenseurs ;
- les PV de la cible au début du round.

Elle appelle ensuite `resoudreCible()` pour chaque impact et retourne tous les résultats.

### Identification des cibles

Les clés comme :

- `joueur_A` ;
- `adversaire_A` ;
- `adversaire_D` ;

servent uniquement à identifier les résultats.

Elles ne représentent pas les équipes X et Y.

Les équipes restent :

- X = A+B ;
- Y = C+D.

### Résolution simultanée

Les PV ne sont pas changés pendant la boucle de calcul.

Tous les résultats sont d’abord préparés à partir de l’état initial du round. Ils pourront ensuite être appliqués ensemble.

Cela permet, par exemple, à deux Stickmans ayant chacun 5 PV de se mettre KO mutuellement pendant le même tour.

### Test ajouté

Ajout d’un test dans `CombatServiceTest` avec :

- un attaquant joueur possédant 10 ATK ;
- un attaquant adverse possédant 10 ATK ;
- deux cibles possédant chacune 5 PV ;
- aucune défense.

Résultat attendu et obtenu :

- le Stickman du joueur termine à 0 PV ;
- le Stickman adverse termine à 0 PV ;
- les deux KO sont calculés pendant le même round.

### Tests PHPUnit

Résultat final :

- 6 tests réussis ;
- 20 assertions réussies.

### Ce que j’ai compris

Une résolution simultanée ne signifie pas exécuter les calculs exactement au même instant.

Le programme calcule d’abord tous les résultats à partir d’un même état initial, puis applique les changements après les calculs.

### Résultat

Le moteur StickVerse sait maintenant résoudre plusieurs impacts indépendants pendant un même round tout en conservant la possibilité d’un double KO.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J29 — Plans secrets et résolution complète d’un round — 16/08/2026

### Objectif

Construire la logique permettant de représenter les choix secrets des deux joueurs, de suivre les PV temporaires pendant un combat et de résoudre un round complet simultanément.

### Travail réalisé

- Création de `PlanCombat`.
- Stockage des quatre choix d’un joueur :
  - cible de l’attaque de l’équipe X ;
  - cible de l’attaque de l’équipe Y ;
  - cible de la défense de l’équipe X ;
  - cible de la défense de l’équipe Y.
- Validation des emplacements autorisés : A, B, C ou D.
- Détection d’un focus, d’un split et d’une double défense.
- Création de `EtatEquipeCombat`.
- Initialisation des PV actuels à partir des PV maximum des Stickmans.
- Gestion des groupes X = A+B et Y = C+D.
- Détection des Stickmans vivants et éliminés.
- Création de `ResolutionRoundService`.
- Addition des attaques lorsqu’elles ciblent le même Stickman.
- Addition des défenses lorsqu’elles protègent le même Stickman.
- Calcul de tous les impacts avant de modifier les PV afin de conserver la simultanéité.
- Application des PV restants seulement après le calcul complet du round.
- Un groupe éliminé ne peut plus contribuer à une attaque ou une défense.
- Une cible déjà éliminée ne peut plus être choisie.

### Architecture comprise

`PlanCombat` représente les décisions d’un joueur pendant un round.

`EtatEquipeCombat` représente l’état temporaire de son équipe pendant le combat, notamment les PV actuels.

`ResolutionRoundService` transforme les deux plans en impacts, utilise `CombatService` pour calculer les dégâts, puis applique les résultats aux deux équipes.

Ces classes ne sont pas des Entity Doctrine : elles représentent de la logique temporaire et ne nécessitent donc aucune migration.

### Erreur rencontrée

J’ai d’abord confondu le fichier de production `src/Model/EtatEquipeCombat.php` avec son fichier de test `tests/Model/EtatEquipeCombatTest.php`.

PHPUnit ne trouvait donc pas la classe `EtatEquipeCombatTest`.

J’ai compris que :

- `src/` contient le véritable code utilisé par l’application ;
- `tests/` contient uniquement le code qui vérifie le fonctionnement de l’application ;
- les fichiers de test ne représentent pas le joueur 2 ;
- dans une future version en ligne, les deux joueurs enverront leurs choix au même serveur Symfony, qui utilisera le même moteur de combat pour résoudre le round.

### Tests effectués

- Tests de `PlanCombat`.
- Tests de `EtatEquipeCombat`.
- Tests unitaires de `CombatService`.
- Test d’intégration d’un round complet avec focus, split, défense simple et double défense.
- Vérification de la résolution simultanée.
- Résultat global : 12 tests et 50 assertions validés.
- Container Symfony validé avec `lint:container`.

### Résultat

Le moteur peut maintenant recevoir les plans secrets des deux joueurs, calculer toutes les attaques et défenses d’un round, puis mettre à jour simultanément les PV des deux équipes.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J30 — Première interface de combat local — 16/08/2026

### Objectif

Connecter le moteur de combat PHP à une véritable page Symfony et permettre à deux joueurs locaux de saisir successivement leurs plans secrets.

### Travail réalisé

- Création de `CombatController`.
- Création de la route protégée `/combat`.
- Accès réservé aux utilisateurs connectés avec `ROLE_USER`.
- Récupération de l’équipe de l’utilisateur avec `EquipeRepository`.
- Transmission de l’équipe au template Twig.
- Affichage des quatre Stickmans :
  - équipe X : slots A et B ;
  - équipe Y : slots C et D.
- Création de `PlanCombatType`.
- Ajout de quatre listes de choix :
  - cible attaquée par X ;
  - cible attaquée par Y ;
  - cible défendue par X ;
  - cible défendue par Y.
- Transformation des données du formulaire en objet `PlanCombat`.
- Stockage temporaire du plan du joueur 1 dans la session Symfony.
- Masquage du plan du joueur 1 avant le passage au joueur 2.
- Création du plan du joueur 2 après sa validation.
- Création de deux objets `EtatEquipeCombat`.
- Résolution du round avec `ResolutionRoundService`.
- Stockage temporaire des résultats dans la session.
- Redirection vers `/combat/resultat`.
- Affichage des attaques, défenses, dégâts, overkill et PV restants dans Twig.

### Architecture comprise

La session Symfony conserve temporairement les informations entre deux requêtes HTTP.

Le plan du joueur 1 suit ce trajet :

Navigateur
→ formulaire Twig
→ requête POST
→ CombatController
→ session Symfony.

Lorsque le joueur 2 valide son plan :

Session du joueur 1 + formulaire du joueur 2
→ deux objets PlanCombat
→ deux objets EtatEquipeCombat
→ ResolutionRoundService
→ CombatService
→ résultats du round
→ session
→ redirection
→ Twig.

### Pourquoi utiliser une redirection après le POST ?

Après la résolution, le Controller stocke temporairement le résultat dans la session puis redirige vers une route GET.

Cela évite qu’un rafraîchissement du navigateur renvoie le formulaire et relance accidentellement le même round.

### Erreurs et incompréhensions rencontrées

J’ai d’abord pensé que les notions de joueur 1 et joueur 2 signifiaient que nous commencions le multijoueur en ligne.

J’ai compris qu’il s’agit actuellement d’un combat local sur le même navigateur :

- le joueur 1 saisit son plan ;
- l’écran est transmis au joueur 2 ;
- le joueur 2 saisit son plan ;
- Symfony résout ensuite le round.

Le véritable multijoueur en ligne demandera plus tard de stocker les combats, les participants et les choix de chaque round en base de données.

J’ai également oublié de fermer un bloc conditionnel Twig avec un deuxième `{% endif %}`. Twig exige que chaque `{% if %}` possède sa propre fermeture.

### Limites actuelles

- Les deux joueurs utilisent temporairement la même équipe.
- Un seul round est joué à la fois.
- Les PV ne sont pas encore conservés pour le round suivant.
- Aucun combat n’est enregistré dans MySQL.
- Il ne s’agit pas encore d’un combat en ligne.

### Tests effectués

- Syntaxe de `CombatController.php` valide.
- Syntaxe de `PlanCombatType.php` valide.
- Template Twig valide.
- Container Symfony valide.
- 12 tests PHPUnit validés.
- 50 assertions validées.
- Test manuel d’un focus contre une double défense.
- Affichage correct des dégâts et des PV restants.

### Résultat

StickVerse possède maintenant une première boucle de combat locale complète :

choix secret du joueur 1
→ choix secret du joueur 2
→ résolution simultanée
→ affichage du résultat.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J31 — Fondation des combats en ligne — 16/08/2026

### Objectif

Créer la première structure persistante permettant de représenter un combat entre deux utilisateurs dans MySQL.

### Travail réalisé

- Création de l’Entity `Combat`.
- Création de `CombatRepository`.
- Création de la table `combat`.
- Ajout des relations :
  - `joueur1` obligatoire ;
  - `joueur2` nullable pendant l’attente d’un adversaire ;
  - `gagnant` nullable tant que le combat n’est pas terminé.
- Ajout du statut du combat.
- Ajout du numéro du round.
- Ajout des dates de création et de dernière modification.
- Création de constantes pour les statuts :
  - `en_attente` ;
  - `en_cours` ;
  - `termine` ;
  - `abandonne`.
- Valeurs par défaut :
  - statut `en_attente` ;
  - round numéro 1.
- Mise à jour automatique de `dateMiseAJour`.
- Ajout de méthodes permettant de vérifier l’état du combat.
- Ajout de `passerAuRoundSuivant()`.

### Règles métier ajoutées

- Le joueur 1 est obligatoire dès la création du combat.
- Le numéro du round doit être supérieur à zéro.
- Un statut inconnu est refusé.
- Un utilisateur ne peut pas combattre contre lui-même.
- Le gagnant doit obligatoirement être l’un des participants.

### Architecture comprise

Le combat local du J30 utilisait la session Symfony.

Cette session convenait pour une démonstration sur un seul navigateur, mais elle ne permet pas à deux ordinateurs différents de partager durablement un même combat.

L’Entity `Combat` devient maintenant la source persistante commune :

Navigateur du joueur 1
→ Symfony
→ MySQL
← Symfony
← Navigateur du joueur 2.

Les deux joueurs consulteront donc le même combat enregistré en base.

### Choix architectural important

L’Entity `Equipe` n’est pas encore reliée directement au combat.

Une équipe peut être modifiée après le lancement d’un match. Si le combat utilisait directement cette équipe, un joueur pourrait modifier sa composition ou profiter de statistiques modifiées pendant la partie.

Le J32 créera donc des snapshots des quatre Stickmans au lancement du combat. Le match conservera ainsi ses propres statistiques et ses propres PV.

### Différence entre validation et exception PHP

Les contraintes Symfony Validator produisent des messages propres avant l’enregistrement.

Les vérifications dans les méthodes comme `setStatut()` empêchent également le code PHP interne de placer l’objet dans un état invalide.

Ces deux protections sont complémentaires.

### Migration

La migration crée :

- la table `combat` ;
- trois clés étrangères vers `user` ;
- les index associés ;
- les colonnes du statut, du round et des dates.

La méthode `down()` permet de supprimer proprement les contraintes et la table.

### Tests effectués

- Syntaxe PHP valide.
- Mapping Doctrine valide.
- Schéma MySQL synchronisé.
- Valeurs initiales du combat vérifiées.
- Passage au round suivant vérifié.
- Statut invalide refusé.
- Round zéro refusé.
- Combat contre soi-même refusé.
- Gagnant extérieur au combat refusé.
- Container Symfony valide.
- Suite complète : 18 tests et 67 assertions validés.

### Résultat

StickVerse possède maintenant la fondation persistante d’un combat en ligne.

Le combat peut identifier ses deux joueurs, son état actuel, son round et son éventuel gagnant.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J32 — Ajouter les snapshots des combattants — 16/08/2026

### Objectif

Préparer les combats en ligne en enregistrant une copie figée des quatre Stickmans de chaque joueur au début d’un combat.

Un combat complet possédera donc huit combattants :

- joueur 1 : slots A, B, C et D ;
- joueur 2 : slots A, B, C et D.

### Pourquoi utiliser des snapshots ?

Une équipe et les statistiques des Stickmans peuvent être modifiées après la création d’un combat.

Sans snapshot, une modification effectuée depuis l’administration pourrait modifier un combat déjà commencé ou son historique.

Chaque combattant conserve donc les données utilisées au démarrage du combat :

- identifiant original du Stickman ;
- nom ;
- image ;
- rareté ;
- PV maximum ;
- PV actuels ;
- attaque ;
- défense ;
- joueur propriétaire ;
- slot A, B, C ou D.

Les données du snapshot restent figées. Seuls les PV actuels pourront évoluer pendant le combat.

### Entity CombattantCombat

Création de l’Entity `CombattantCombat` et de son Repository.

Ajout d’une relation `ManyToOne` vers `Combat` et d’une relation `ManyToOne` vers `User`.

La contrainte unique suivante empêche un joueur d’utiliser deux fois le même slot dans un combat :

`combat + joueur + slot`

La suppression d’un combat supprime automatiquement ses combattants grâce à `ON DELETE CASCADE`.

La relation `Combat -> combattants` utilise également :

`cascade: ['persist']`

Doctrine pourra ainsi enregistrer les combattants ajoutés au combat.

### Service de création

Création de `CreationCombattantsCombatService`.

Le service :

- vérifie que l’utilisateur participe au combat ;
- vérifie que l’équipe appartient bien à cet utilisateur ;
- vérifie que les quatre Stickmans sont enregistrés ;
- vérifie que les quatre Stickmans sont différents ;
- refuse de créer deux fois les snapshots du même joueur ;
- crée les snapshots des slots A, B, C et D.

Le service ne fait pas directement de `flush()` afin que la création du combat et de ses huit combattants puisse plus tard être enregistrée dans une transaction atomique.

### Erreur rencontrée

PHPUnit indiquait initialement :

`Class CreationCombattantsCombatServiceTest cannot be found`

Le fichier de test n’était probablement pas encore enregistré dans VS Code. Après enregistrement, PHPUnit a correctement trouvé et exécuté la classe.

### Tests

Le test du service vérifie :

- la création des quatre snapshots ;
- la copie correcte des statistiques ;
- l’indépendance entre le snapshot et le Stickman original ;
- le refus d’une deuxième création ;
- le refus d’une équipe appartenant à un autre utilisateur.

Résultats finaux :

- 22 tests réussis ;
- 92 assertions ;
- conteneur Symfony valide ;
- mapping Doctrine valide ;
- base de données synchronisée avec les Entities.

### Compréhension retenue

Une `Equipe` représente la composition actuelle choisie par l’utilisateur.

Un `CombattantCombat` représente la copie historique et sécurisée d’un Stickman utilisée dans un combat précis.

Le trajet prévu devient :

`Equipe -> CreationCombattantsCombatService -> CombattantCombat -> Doctrine -> MySQL`

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J33 — Persistance des choix secrets par round

### Objectif

Préparer le stockage sécurisé dans MySQL des quatre décisions secrètes envoyées par chaque joueur pendant un round.

Le combat en ligne complet n’est pas encore jouable. Cette journée construit sa couche de persistance afin que l’état d’une partie ne dépende plus de la session Symfony ou du navigateur.

### Différence entre PlanCombat et PlanRoundCombat

`App\Model\PlanCombat` reste un objet PHP temporaire utilisé par le moteur de combat.

Il vérifie que les quatre cibles correspondent aux slots A, B, C ou D et permet de reconnaître :

- un focus ;
- un split ;
- une double défense.

`App\Entity\PlanRoundCombat` représente la copie persistante d’un plan envoyé pour un round précis.

Elle conserve :

- le combat concerné ;
- le joueur ayant soumis le plan ;
- le numéro du round ;
- la cible d’attaque du groupe X ;
- la cible d’attaque du groupe Y ;
- la cible de défense du groupe X ;
- la cible de défense du groupe Y ;
- la date de soumission.

### Entity PlanRoundCombat

Création de l’Entity `PlanRoundCombat` et de son Repository.

Son constructeur reçoit :

- un `Combat` ;
- un `User` ;
- un `PlanCombat`.

Le constructeur vérifie que le joueur participe réellement au combat, puis copie automatiquement :

- le numéro actuel du round ;
- les quatre choix validés par `PlanCombat` ;
- la date de soumission.

Le plan persistant ne possède aucun setter. Une fois soumis, ses choix, son joueur et son numéro de round ne peuvent donc plus être modifiés par le code métier.

La méthode `toPlanCombat()` permet de reconstruire un objet `PlanCombat` compatible avec le moteur de résolution existant.

### Relation avec Combat

Ajout d’une collection `plans` dans l’Entity `Combat`.

La relation `Combat -> plans` utilise :

`cascade: ['persist']`

Doctrine peut ainsi enregistrer les plans ajoutés au combat.

La relation utilise également `orphanRemoval: true`.

La clé étrangère vers `Combat` possède `ON DELETE CASCADE` afin que la suppression d’un combat supprime également ses plans.

La méthode `estParticipant()` a été ajoutée dans `Combat`. Elle centralise la vérification de l’appartenance d’un joueur et pourra être réutilisée par le futur Voter du J34.

### Protection contre les doubles soumissions

Ajout d’une contrainte unique MySQL sur :

`combat + joueur + numeroRound`

Un joueur ne peut donc enregistrer qu’un seul plan pour un round donné.

Cette protection se trouve directement dans la base de données. Elle reste efficace même en cas de double clic ou de deux requêtes reçues presque simultanément.

### Migration Doctrine

Création et exécution de la migration :

`Version20260817195431.php`

La migration crée la table `plan_round_combat` avec :

- ses huit données métier ;
- une clé étrangère vers `combat` ;
- une clé étrangère vers `user` ;
- les index de recherche ;
- la contrainte unique ;
- la suppression en cascade liée au combat.

La méthode `down()` supprime les clés étrangères puis la table afin de permettre un retour en arrière propre.

### Erreurs rencontrées

La première version finale de `PlanRoundCombat` a été collée par erreur dans :

`tests/Entity/CombatTest.php`

La commande `git diff` a permis d’identifier précisément l’écrasement du test. Le fichier a été restauré depuis le dernier commit avec :

`git restore -- tests/Entity/CombatTest.php`

Les nouveaux tests ont ensuite échoué parce que le véritable fichier `src/Entity/PlanRoundCombat.php` contenait encore le squelette mutable généré par Symfony Maker.

Les erreurs observées étaient cohérentes :

- propriétés encore égales à `null` ;
- méthode `toPlanCombat()` absente ;
- setters encore présents ;
- joueur extérieur non refusé.

Après avoir placé l’Entity finale dans le bon fichier, les cinq tests ciblés ont réussi.

### Tests et validations

Les tests de `PlanRoundCombat` vérifient :

- la copie des quatre décisions ;
- l’association automatique avec le combat ;
- l’acceptation du joueur 2 ;
- le refus d’un joueur extérieur ;
- la conservation du numéro du round soumis ;
- la reconstruction d’un `PlanCombat` ;
- l’absence de setters de modification.

Résultats finaux :

- 28 tests réussis ;
- 122 assertions ;
- conteneur Symfony valide ;
- mapping Doctrine valide ;
- schéma MySQL synchronisé avec les Entities.

### Compréhension retenue

Le navigateur ne devra envoyer que les décisions du joueur.

`PlanCombat` valide et représente temporairement ces décisions.

`PlanRoundCombat` les fige dans MySQL pour un joueur et un round précis.

Le trajet prévu devient :

`Navigateur -> Controller -> PlanCombat -> PlanRoundCombat -> Doctrine -> MySQL`

Pour la résolution future :

`MySQL -> PlanRoundCombat -> toPlanCombat() -> ResolutionRoundService`

Le serveur Symfony reste donc l’unique autorité chargée de valider les choix et de calculer le résultat officiel.

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



