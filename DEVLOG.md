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



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



