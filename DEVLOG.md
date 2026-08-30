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

## J34 — Sécurisation des combats avec un Voter

### Objectif

Empêcher un utilisateur d’accéder à un combat en ligne auquel il ne participe pas.

Le combat en ligne complet n’est pas encore jouable. Le Voter prépare la couche d’autorisation qui protégera les futures pages de consultation, de soumission des plans et de résultats.

### Sécurité déjà existante

L’application possédait déjà deux protections générales :

- les routes d’administration sont réservées à `ROLE_ADMIN` dans `security.yaml` ;
- le contrôleur local de combat est réservé à `ROLE_USER` avec `#[IsGranted('ROLE_USER')]`.

Ces protections vérifient le rôle général de l’utilisateur, mais elles ne permettent pas de savoir si celui-ci participe à un combat précis.

Un utilisateur connecté ne doit pas pouvoir consulter un combat simplement en modifiant son identifiant dans une URL.

### Création de CombatVoter

Création du fichier :

`src/Security/Voter/CombatVoter.php`

Symfony a automatiquement enregistré la classe comme service grâce au tag :

`security.voter`

Le Voter reconnaît deux permissions métier :

- `COMBAT_CONSULTER` pour afficher un combat ;
- `COMBAT_JOUER` pour envoyer une décision ou un plan.

Pour J34, ces deux permissions sont accordées uniquement si l’utilisateur connecté correspond au joueur 1 ou au joueur 2 du combat.

La méthode `Combat::estParticipant()` créée pendant J33 centralise cette vérification.

### Fonctionnement du Voter

La méthode `supports()` vérifie deux éléments :

- la permission demandée est connue ;
- la ressource reçue est bien une Entity `Combat`.

Si la permission ou la ressource n’est pas prise en charge, le Voter s’abstient.

La méthode `voteOnAttribute()` vérifie ensuite :

- que le token contient une véritable Entity `User` ;
- que la ressource est bien un `Combat` ;
- que l’utilisateur participe au combat.

Le Voter refuse volontairement l’accès dans tous les cas incertains.

Les décisions possibles sont :

- `ACCESS_GRANTED` : accès autorisé ;
- `ACCESS_DENIED` : accès explicitement refusé ;
- `ACCESS_ABSTAIN` : le Voter n’est pas concerné par cette demande.

### Séparation entre autorisation et règles métier

Le Voter vérifie qui possède le droit d’agir sur un combat.

Il ne vérifie pas encore :

- si le combat est en cours ;
- si le round accepte encore des plans ;
- si le joueur a déjà soumis son plan ;
- si le délai du round est dépassé.

Ces règles appartiendront aux services métier des prochaines journées.

Cette séparation évite de mélanger la sécurité d’accès avec le fonctionnement interne du moteur de combat.

### Conservation du round local J30

Le Voter n’a pas été ajouté au `CombatController` actuel.

Ce contrôleur utilise encore la session Symfony pour simuler localement les deux plans successifs et ne charge aucune Entity `Combat`.

Le modifier maintenant casserait potentiellement la version locale fonctionnelle sans apporter de véritable combat réseau.

Le Voter sera utilisé lorsqu’une future route recevra une Entity `Combat` persistée.

Le trajet prévu sera :

`Navigateur -> Route -> CombatController -> CombatVoter -> Service métier`

Si l’utilisateur ne participe pas au combat, Symfony renverra une erreur HTTP 403 avant l’exécution de l’action protégée.

### Tests du Voter

Création du fichier :

`tests/Security/Voter/CombatVoterTest.php`

Les tests vérifient :

- que le joueur 1 peut consulter le combat ;
- que le joueur 2 peut jouer ;
- qu’un utilisateur extérieur est refusé ;
- qu’un utilisateur anonyme est refusé ;
- qu’une permission inconnue produit une abstention ;
- qu’une ressource différente de `Combat` produit une abstention.

Résultats ciblés :

- 6 tests réussis ;
- 7 assertions.

### Erreur rencontrée

Le fichier `CombatVoterTest.php` a d’abord été créé avec une taille de zéro octet.

La commande `code` ouvrait correctement le fichier dans VS Code, mais elle n’ajoutait aucun contenu automatiquement.

La commande `php -l` indiquait malgré tout qu’aucune erreur de syntaxe n’était présente, car un fichier vide reste syntaxiquement valide.

PHPUnit indiquait cependant :

`Class CombatVoterTest cannot be found`

La taille du fichier a permis d’identifier le problème :

`(Get-Item .\tests\Security\Voter\CombatVoterTest.php).Length`

Après avoir collé le code dans VS Code et enregistré le fichier avec `Ctrl + S`, les tests ont été correctement détectés.

### Validations finales

Résultats finaux du projet :

- 34 tests réussis ;
- 129 assertions ;
- conteneur Symfony valide ;
- mapping Doctrine valide ;
- schéma MySQL synchronisé.

### Compréhension retenue

`ROLE_USER` signifie qu’un utilisateur est connecté et possède un rôle général.

Le Voter répond à une question plus précise :

« Cet utilisateur possède-t-il le droit d’effectuer cette action sur ce combat particulier ? »

La sécurité prévue devient :

`Utilisateur connecté -> permission demandée -> CombatVoter -> Combat::estParticipant() -> autorisation ou refus 403`

Cette protection restera côté serveur et ne pourra pas être contournée en modifiant le HTML, JavaScript ou l’URL.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J35 — Résolution atomique d’un round en ligne

### Objectif

Empêcher qu’un même round soit calculé plusieurs fois lorsque deux requêtes arrivent presque simultanément.

Cette protection est indispensable pour éviter :

- des dégâts appliqués plusieurs fois ;
- une double modification des PV ;
- plusieurs changements de numéro de round ;
- un état différent entre les deux joueurs ;
- des résultats incohérents en cas de double clic ou de requêtes concurrentes.

### Verrou pessimiste

Ajout de la méthode suivante dans `CombatRepository` :

`trouverAvecVerrouEcriture()`

Elle charge le combat avec le verrou Doctrine :

`LockMode::PESSIMISTIC_WRITE`

Le verrou est appliqué sur la ligne du combat dans MySQL.

Pendant qu’une requête résout le round, une autre requête souhaitant verrouiller le même combat doit attendre la fin de la première transaction.

Une fois le verrou obtenu, la deuxième requête relit l’état mis à jour du combat.

Elle constate alors que le numéro de round a déjà changé et ne peut pas recalculer les anciens plans.

### Transaction Doctrine

Création du service :

`ResolutionRoundCombatEnLigneService`

La résolution complète est exécutée dans :

`EntityManagerInterface::wrapInTransaction()`

La transaction contient :

1. le verrouillage du combat ;
2. la vérification de son statut ;
3. la récupération des plans du round courant ;
4. la récupération des huit snapshots ;
5. la reconstruction des deux états d’équipe ;
6. le calcul simultané du round ;
7. la mise à jour des PV des snapshots ;
8. le passage au round suivant ;
9. le `flush()` automatique de Doctrine ;
10. le commit de la transaction.

Si une exception survient avant la fin, Doctrine annule la transaction.

Les PV et le numéro de round ne peuvent donc pas être enregistrés partiellement.

### Requêtes métier des repositories

Ajout de méthodes spécifiques dans les repositories.

`PlanRoundCombatRepository::trouverPourCombatEtRound()`

Cette méthode récupère uniquement les plans appartenant au combat et au numéro de round demandés.

`CombattantCombatRepository::trouverPourCombatEtJoueur()`

Cette méthode récupère les quatre snapshots d’un participant, triés par slot.

Ces requêtes évitent de disperser la logique Doctrine dans le service métier.

### Reconstruction depuis les snapshots

Création du service :

`CreationEtatEquipeCombatDepuisSnapshotsService`

Le moteur local utilisait encore une `Equipe` contenant les Entities `Stickman`.

Le moteur en ligne doit utiliser les statistiques figées dans les `CombattantCombat`.

Le service effectue donc l’adaptation suivante :

`CombattantCombat -> Stickman temporaire -> Equipe temporaire -> EtatEquipeCombat`

Les Stickmans temporaires :

- utilisent les PV maximum des snapshots ;
- utilisent l’attaque du snapshot ;
- utilisent la défense du snapshot ;
- ne sont jamais persistés dans MySQL ;
- servent uniquement à conserver la compatibilité avec le moteur existant.

Les PV actuels sont ensuite restaurés dans `EtatEquipeCombat`.

Le service vérifie également :

- que les quatre slots A, B, C et D existent ;
- qu’un slot n’apparaît pas plusieurs fois ;
- que tous les snapshots appartiennent au même combat ;
- que tous les snapshots appartiennent au même joueur ;
- que le joueur participe réellement au combat.

### Résolution conditionnelle

La méthode principale est :

`resoudreSiPret()`

Si un seul plan existe, elle retourne `null`.

Cela signifie que le premier joueur a joué mais que son adversaire n’a pas encore soumis son plan.

Aucun dégât n’est calculé et le numéro du round ne change pas.

Lorsque les deux plans existent :

- chaque plan est associé à son propriétaire ;
- les choix secrets sont transformés en `PlanCombat` ;
- le moteur résout les attaques et défenses simultanément ;
- les nouveaux PV sont reportés dans les snapshots ;
- le combat passe au round suivant.

### Protection contre la double résolution

Après la première résolution, le combat passe par exemple du round 1 au round 2.

Une deuxième requête en attente obtient ensuite le verrou.

Elle recherche alors les plans du round 2 et ne trouve pas les anciens plans du round 1.

La méthode retourne `null` et aucun dégât supplémentaire n’est appliqué.

Le numéro de round devient ainsi le marqueur persistant indiquant que le round précédent a déjà été traité.

### Secret des plans

Les plans restent enregistrés séparément dans MySQL.

Le navigateur ne reçoit pas le plan adverse avant la résolution.

Seul le serveur charge les deux plans dans la transaction lorsque les deux joueurs ont soumis leurs choix.

Le serveur Symfony reste donc autoritaire sur :

- les participants ;
- les plans utilisés ;
- les statistiques ;
- les PV ;
- les dégâts ;
- le numéro du round.

### Trajet des données

Le trajet préparé pour la version en ligne devient :

`Contrôleur futur`

`-> ResolutionRoundCombatEnLigneService`

`-> transaction Doctrine`

`-> verrou du Combat`

`-> PlanRoundCombatRepository`

`-> CombattantCombatRepository`

`-> CreationEtatEquipeCombatDepuisSnapshotsService`

`-> ResolutionRoundService`

`-> CombatService`

`-> mise à jour des CombattantCombat`

`-> passage au round suivant`

`-> flush et commit MySQL`

### Tests

Création de tests pour vérifier :

- la reconstruction d’un état depuis quatre snapshots ;
- la copie des statistiques figées ;
- la restauration des PV actuels ;
- le refus d’un slot manquant ;
- le refus d’un slot en double ;
- le refus de mélanger deux joueurs ;
- l’attente du deuxième plan ;
- la résolution lorsque les deux plans existent ;
- l’écriture des nouveaux PV ;
- le passage au round suivant ;
- l’impossibilité de résoudre deux fois le même round ;
- le refus d’un combat qui n’est pas en cours.

Une notice PHPUnit a été corrigée en remplaçant `createMock()` par `createStub()` lorsque le double de test servait uniquement à retourner des valeurs.

Résultats finaux :

- 41 tests réussis ;
- 186 assertions ;
- aucune notice PHPUnit ;
- conteneur Symfony valide ;
- mapping Doctrine valide ;
- schéma MySQL synchronisé.

### Limites actuelles

Le service atomique n’est pas encore relié à une interface réseau ou à un contrôleur en ligne.

Le véritable combat entre deux navigateurs n’est donc pas encore jouable.

La version locale J30 utilisant la session Symfony reste intacte et fonctionnelle.

J36 devra vérifier spécifiquement la conservation des PV sur plusieurs rounds successifs.

### Compréhension retenue

Le verrou protège le combat contre plusieurs résolutions concurrentes.

La transaction garantit que toutes les modifications sont enregistrées ensemble ou qu’aucune ne l’est.

Les repositories lisent les données persistées.

Les services transforment et appliquent les règles métier.

Le moteur calcule les résultats sans faire confiance au navigateur.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J36 — Persistance des PV entre les rounds

### Objectif

J36 devait vérifier que les points de vie actuels des combattants sont conservés entre plusieurs rounds successifs.

Les PV ne doivent jamais être réinitialisés depuis les statistiques originales du Stickman au début d’un nouveau round.

Le trajet attendu est :

`PV MySQL du round précédent`

`-> reconstruction de l’état de combat`

`-> résolution du nouveau round`

`-> mise à jour des snapshots`

`-> flush Doctrine`

`-> nouveaux PV MySQL`

### Test unitaire de plusieurs rounds

Création du test :

`tests/Service/PersistancePvEntreRoundsTest.php`

Ce test utilise des doubles PHPUnit pour simuler des snapshots persistants.

Il exécute deux résolutions successives avec le même combat :

- départ à 10 PV ;
- premier round : passage à 8 PV ;
- deuxième round : reprise depuis 8 PV ;
- fin du deuxième round : passage à 6 PV.

Ce test vérifie la logique métier du service sans dépendre d’une base de données.

### Base MySQL réservée aux tests

La configuration Doctrine possède déjà un suffixe automatique pour l’environnement de test :

`dbname_suffix: '_test%env(default::TEST_TOKEN)%'`

Une configuration locale non versionnée a été ajoutée dans :

`.env.test.local`

Elle permet à Symfony d’utiliser le pilote MySQL pendant les tests sans exposer les identifiants dans Git.

La base suivante a été créée :

`stickverse_test`

Les 9 migrations existantes y ont été exécutées.

Le schéma de cette base est synchronisé avec les Entities Doctrine.

Le fichier `.env.test.local` est ignoré par Git grâce à la règle :

`/.env.*.local`

### Test d’intégration MySQL

Création du test :

`tests/Integration/Service/PersistancePvMySqlTest.php`

Contrairement au test unitaire, ce test utilise :

- le véritable `EntityManager` Doctrine ;
- les véritables repositories ;
- la véritable base `stickverse_test` ;
- les véritables Entities ;
- le véritable service de résolution en ligne ;
- de véritables transactions ;
- de véritables écritures et lectures MySQL.

Le test crée :

- deux utilisateurs ;
- quatre Stickmans ;
- un combat en cours ;
- huit snapshots de combattants ;
- deux plans secrets pour le round 1.

Le premier round est ensuite résolu.

Les slots A et B passent de 10 à 8 PV.

### Vérification après vidage de Doctrine

Après le premier round, le test exécute :

`EntityManager::clear()`

Cette opération retire tous les objets actuellement suivis par Doctrine.

Le combat et les combattants sont ensuite entièrement rechargés depuis MySQL.

Cela prouve que les valeurs retrouvées ne viennent pas simplement de la mémoire PHP.

Les valeurs relues sont :

- joueur 1 : A = 8, B = 8, C = 10, D = 10 ;
- joueur 2 : A = 8, B = 8, C = 10, D = 10 ;
- numéro du round = 2.

### Deuxième round

Deux nouveaux plans sont enregistrés pour le round 2.

La résolution repart des 8 PV réellement enregistrés dans MySQL.

Après le deuxième round :

- joueur 1 : A = 6, B = 6, C = 10, D = 10 ;
- joueur 2 : A = 6, B = 6, C = 10, D = 10 ;
- numéro du round = 3.

Un second `EntityManager::clear()` confirme une nouvelle fois que les valeurs finales proviennent bien de MySQL.

### Isolation et nettoyage du test

Avant d’écrire des données, le test vérifie que le nom de la base se termine par :

`_test`

Cette protection empêche le test d’intégration de modifier accidentellement la base de développement.

Toutes les écritures sont placées dans une transaction extérieure.

À la fin du test, cette transaction est annulée avec un rollback.

Les données temporaires du test ne restent donc pas dans `stickverse_test`.

### Service retiré du conteneur compilé

Lors du premier lancement, Symfony indiquait que :

`ResolutionRoundCombatEnLigneService`

avait été retiré ou intégré pendant la compilation du conteneur.

Ce comportement est normal : le service n’est pas encore utilisé par un contrôleur de production.

Il n’a pas été rendu public uniquement pour satisfaire le test.

Le test le construit manuellement avec :

- le véritable `EntityManager` ;
- les véritables repositories ;
- `CreationEtatEquipeCombatDepuisSnapshotsService` ;
- `ResolutionRoundService` ;
- `CombatService`.

Le comportement testé reste donc celui de la véritable architecture en ligne.

### Résultats finaux

- 43 tests réussis ;
- 246 assertions ;
- aucune notice PHPUnit ;
- test d’intégration MySQL réussi ;
- conteneur Symfony valide ;
- mapping Doctrine valide ;
- base de développement synchronisée ;
- base de test synchronisée.

### Compréhension retenue

Un test unitaire vérifie la logique d’une classe en isolant ses dépendances.

Un test d’intégration vérifie que plusieurs composants réels fonctionnent correctement ensemble.

`EntityManager::clear()` permet de distinguer une valeur encore présente en mémoire d’une valeur réellement persistée dans MySQL.

Les `pvMaximum` restent figés dans les snapshots.

Les `pvActuels` sont modifiés après chaque round puis enregistrés par Doctrine.

J36 confirme donc que les PV peuvent évoluer durablement pendant un combat en ligne de plusieurs rounds.

### Limite actuelle

La persistance des PV est maintenant validée, mais le combat en ligne n’est toujours pas relié à deux navigateurs.

La version locale J30 reste intacte.

J37 pourra maintenant traiter la détection de fin de partie : victoire, élimination simultanée et futurs cas d’abandon.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


## J37 — Gérer les fins de combat

### Objectif

Cette étape ajoute la détection automatique de la fin d’un combat en ligne ainsi que la possibilité pour un joueur d’abandonner.

Le serveur peut désormais déterminer de manière autoritaire :

- si le joueur 1 a gagné ;
- si le joueur 2 a gagné ;
- si les deux équipes sont éliminées simultanément ;
- si le combat est bloqué dans une égalité mathématique ;
- si un participant abandonne.

### Détermination automatique de la fin

Le nouveau service utilisé est :

`DeterminationFinCombatService`

Il analyse les snapshots des deux équipes après l’application des dégâts du round.

Un joueur gagne lorsque son équipe possède encore au moins un combattant vivant et que l’équipe adverse n’en possède plus aucun.

Le combat est déclaré nul lorsque :

- les deux équipes sont éliminées simultanément ;
- ou chaque équipe ne possède plus qu’un seul combattant vivant et qu’aucun des deux ne peut infliger de dégâts à l’autre.

Dans le cas d’une égalité :

- le statut du combat devient `termine` ;
- le gagnant reste `null`.

Dans le cas d’une victoire normale :

- le statut du combat devient `termine` ;
- le participant encore vivant devient le gagnant.

### Intégration à la résolution atomique

`ResolutionRoundCombatEnLigneService` utilise maintenant `DeterminationFinCombatService` après avoir reporté les nouveaux PV dans les snapshots.

Le déroulement devient :

`verrouillage du combat`

`-> chargement des plans`

`-> chargement des snapshots`

`-> résolution simultanée du round`

`-> mise à jour des PV`

`-> détermination de la fin du combat`

`-> passage éventuel au round suivant`

Le numéro du round augmente uniquement lorsque le combat peut continuer.

Lorsqu’une victoire ou une égalité termine le combat, le numéro du round reste celui du dernier round joué.

### Abandon d’un combat

Le nouveau service utilisé est :

`AbandonCombatService`

L’abandon est exécuté dans une transaction Doctrine et utilise le même verrou d’écriture que la résolution des rounds.

Le service vérifie que :

- le combat existe ;
- le combat est encore en cours ;
- les deux joueurs sont présents ;
- l’utilisateur qui abandonne participe réellement au combat.

Lorsque le joueur 1 abandonne, le joueur 2 devient gagnant.

Lorsque le joueur 2 abandonne, le joueur 1 devient gagnant.

Le statut du combat devient alors :

`abandonne`

Le numéro du round n’est pas modifié.

### Protection contre les requêtes concurrentes

Le verrou d’écriture empêche une résolution de round et un abandon de modifier simultanément le même combat.

La transaction garantit que le statut, le gagnant, les PV et le numéro du round restent cohérents.

Si une opération échoue, l’ensemble de ses modifications est annulé.

### Tests unitaires

Les tests vérifient notamment :

- la victoire du joueur 1 ;
- la victoire du joueur 2 ;
- l’élimination simultanée des deux équipes ;
- l’égalité mathématique entre les derniers combattants ;
- la poursuite du combat lorsqu’une attaque peut encore infliger des dégâts ;
- l’intégration de la détection de victoire à la résolution en ligne ;
- l’absence de passage au round suivant lorsque le combat se termine ;
- l’abandon du joueur 1 ;
- l’abandon du joueur 2 ;
- le refus d’un abandon effectué par un joueur extérieur ;
- le refus d’abandonner un combat déjà terminé ;
- le refus d’un combat introuvable.

### Tests d’intégration MySQL

Un test d’intégration vérifie réellement l’abandon avec Doctrine et MySQL.

Après l’abandon :

- l’EntityManager est vidé ;
- le combat est relu depuis MySQL ;
- le statut `abandonne` est retrouvé ;
- le gagnant correspond à l’adversaire ;
- le numéro du round est resté inchangé.

La base de test est protégée par une vérification imposant que son nom se termine par `_test`.

Toutes les données créées sont annulées à la fin du test grâce à une transaction de test.

### Résultats finaux

- 55 tests réussis ;
- 305 assertions ;
- aucune notice PHPUnit ;
- conteneur Symfony valide ;
- mapping Doctrine valide ;
- schéma de développement synchronisé ;
- schéma de test synchronisé ;
- aucune migration nécessaire.

### Limites actuelles

Les services de victoire et d’abandon ne sont pas encore reliés à un contrôleur réseau.

Un joueur ne peut donc pas encore déclencher l’abandon depuis l’interface web.

L’affichage du gagnant, de l’égalité ou de l’abandon devra également être ajouté à la future interface du combat en ligne.

La version locale utilisant la session Symfony reste intacte.

### Compréhension retenue

Les PV persistés permettent au serveur de déterminer l’état réel des équipes.

La fin du combat est décidée uniquement par le serveur après la résolution du round.

Un combat terminé ne passe pas artificiellement au round suivant.

L’abandon est une modification métier sensible qui doit être protégée par une transaction et un verrou d’écriture.

Le gagnant peut être `null` uniquement lorsqu’un combat terminé se conclut par une égalité.



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


## J38 — Exposer le combat en ligne via HTTP

### Objectif

J38 relie les services métier du combat en ligne à des routes HTTP sécurisées, sans modifier le prototype local disponible sur `/combat`.

Le navigateur peut maintenant :

- consulter l’état d’un combat ;
- soumettre un plan secret ;
- attendre le plan adverse ;
- déclencher la résolution lorsque les deux plans existent ;
- abandonner un combat.

### Routes HTTP

Le nouveau `CombatEnLigneController` expose :

- `GET /combat-en-ligne/{id}` ;
- `POST /combat-en-ligne/{id}/plan` ;
- `POST /combat-en-ligne/{id}/abandon`.

La route GET retourne le statut, le numéro du round, le gagnant éventuel, l’état de préparation des joueurs et les jetons CSRF.

Elle ne retourne jamais le contenu des plans.

### Soumission transactionnelle

Le nouveau `SoumissionPlanCombatService` :

- verrouille le combat en écriture ;
- vérifie que le combat existe et qu’il est en cours ;
- vérifie la présence des deux joueurs ;
- refuse les utilisateurs extérieurs ;
- refuse une deuxième soumission pour le même round ;
- enregistre le `PlanRoundCombat` dans MySQL.

La contrainte unique MySQL complète la protection transactionnelle.

### Résolution automatique

Après chaque soumission, le contrôleur appelle `ResolutionRoundCombatEnLigneService`.

Si un seul plan existe, la réponse indique :

`en_attente_adversaire`

Lorsque le deuxième plan est soumis :

- les deux plans sont chargés côté serveur ;
- le round est résolu ;
- les PV sont enregistrés ;
- le numéro du round évolue ;
- les résultats sont retournés en JSON.

### Sécurité

Toutes les routes nécessitent `ROLE_USER`.

Le `CombatVoter` vérifie ensuite que l’utilisateur participe réellement au combat.

Les actions POST utilisent un jeton transmis dans l’en-tête :

`X-CSRF-TOKEN`

Un jeton invalide produit une réponse HTTP `403` sans modifier la base.

Le contrôleur utilise également les codes HTTP `400`, `409` et `422` pour distinguer les erreurs JSON, métier et de validation.

### Secret des plans

Le navigateur peut savoir si l’adversaire est prêt, mais ne reçoit jamais :

- ses cibles d’attaque ;
- ses cibles de défense ;
- son objet `PlanRoundCombat`.

Le plan reste secret dans MySQL jusqu’à la résolution.

### Trajet des données

`navigateur`

`-> requête HTTP JSON`

`-> CombatEnLigneController`

`-> authentification`

`-> CombatVoter`

`-> vérification CSRF`

`-> SoumissionPlanCombatService`

`-> transaction et verrou Doctrine`

`-> PlanRoundCombat dans MySQL`

`-> ResolutionRoundCombatEnLigneService`

`-> mise à jour des PV et du round`

`-> réponse JSON`

### Tests HTTP

Les tests vérifient :

- la consultation par un participant ;
- le refus d’un utilisateur extérieur ;
- l’absence des plans dans les réponses ;
- la génération et la validation des jetons CSRF ;
- l’enregistrement réel du premier plan ;
- l’attente du deuxième joueur ;
- l’abandon depuis HTTP ;
- la persistance du gagnant ;
- la soumission des deux plans ;
- la résolution automatique ;
- le passage du round 1 au round 2 ;
- la persistance des PV de 10 à 8 dans MySQL.

Les tests utilisant plusieurs requêtes appellent `disableReboot()` afin de conserver la connexion Doctrine et la transaction de test.

### Résultats finaux

- 66 tests réussis ;
- 449 assertions ;
- aucune notice PHPUnit ;
- conteneur Symfony valide ;
- trois routes HTTP enregistrées ;
- mapping Doctrine valide ;
- schémas de développement et de test synchronisés ;
- aucune migration nécessaire.

### Limites actuelles

Les routes retournent uniquement du JSON.

Aucune interface Twig ou JavaScript ne les utilise encore.

La création du combat en ligne et l’association initiale des joueurs ne sont pas encore exposées au navigateur.

La version locale basée sur la session Symfony reste intacte.

### Compréhension retenue

Le contrôleur traduit HTTP sans contenir les règles métier.

Le voter contrôle l’autorisation.

Le CSRF protège les actions de la session.

Le verrou protège la soumission concurrente.

Le deuxième plan déclenche la résolution sans révéler le premier.


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J39 — Créer et rejoindre un combat en ligne

### Objectif

J39 ajoute le salon permettant à deux utilisateurs de former un combat en ligne.

Le parcours devient :

- le joueur 1 sélectionne son équipe ;
- il crée un combat en attente ;
- ses quatre Stickmans sont copiés dans des snapshots ;
- le joueur 2 consulte les combats disponibles ;
- il rejoint le combat avec sa propre équipe ;
- ses quatre snapshots sont créés ;
- le combat passe automatiquement à `en_cours`.

La version locale disponible sur `/combat` reste intacte.

### Recherche des combats

`CombatRepository` possède maintenant deux nouvelles requêtes.

`trouverActifPourJoueur()` recherche un combat au statut `en_attente` ou `en_cours` dans lequel l’utilisateur est joueur 1 ou joueur 2.

Cette vérification empêche un utilisateur de participer à plusieurs combats actifs.

`trouverDisponiblesPour()` retourne uniquement les combats :

- au statut `en_attente` ;
- sans joueur 2 ;
- créés par un autre utilisateur.

Les combats déjà commencés ne restent donc pas visibles dans le salon.

### Création d’un combat

Le nouveau `CreationCombatEnLigneService` exécute la création dans une transaction Doctrine.

Il vérifie que :

- le joueur est enregistré ;
- l’équipe est enregistrée ;
- le joueur ne participe pas déjà à un combat actif ;
- l’équipe appartient réellement au joueur ;
- les quatre slots contiennent des Stickmans distincts et enregistrés.

Le service crée ensuite un `Combat` au statut :

`en_attente`

Il utilise `CreationCombattantsCombatService` pour créer les quatre snapshots du joueur 1.

Le combat et ses snapshots sont enregistrés ensemble grâce au cascade persist Doctrine.

### Jonction du deuxième joueur

Le nouveau `RejoindreCombatEnLigneService` charge le combat avec un verrou d’écriture.

Il vérifie que :

- le joueur est enregistré ;
- son équipe est enregistrée ;
- le combat existe ;
- le combat est encore disponible ;
- aucun joueur 2 n’est déjà présent ;
- le joueur ne tente pas de rejoindre son propre combat ;
- le joueur ne participe pas déjà à un autre combat actif ;
- l’équipe lui appartient.

Après validation :

- le joueur devient `joueur2` ;
- ses quatre snapshots sont créés ;
- le statut devient `en_cours` ;
- le numéro du round reste égal à 1.

Le verrou empêche deux joueurs de prendre simultanément la dernière place disponible.

### Salon HTTP

Le nouveau `SalonCombatEnLigneController` expose trois routes JSON :

- `GET /salon-combat-en-ligne` ;
- `POST /salon-combat-en-ligne/creer` ;
- `POST /salon-combat-en-ligne/{id}/rejoindre`.

La route GET retourne :

- l’identifiant du combat actif éventuel ;
- les équipes appartenant au joueur connecté ;
- les combats disponibles ;
- les jetons CSRF de création et de jonction.

Les routes POST nécessitent un `equipeId` valide.

Une équipe appartenant à un autre utilisateur n’est jamais acceptée.

### Sécurité

Le salon nécessite `ROLE_USER`.

La création et la jonction utilisent des jetons CSRF transmis dans l’en-tête :

`X-CSRF-TOKEN`

Le joueur qui rejoint n’est pas encore participant au combat.

La jonction ne peut donc pas utiliser `CombatVoter`.

L’autorisation est vérifiée directement par le service transactionnel avant l’ajout du joueur 2.

### Trajet des données

`joueur 1`

`-> GET du salon`

`-> sélection de son équipe`

`-> POST de création`

`-> CreationCombatEnLigneService`

`-> création du Combat`

`-> quatre snapshots`

`-> statut en_attente`

Puis :

`joueur 2`

`-> GET du salon`

`-> sélection du combat et de son équipe`

`-> POST de jonction`

`-> verrou d’écriture`

`-> RejoindreCombatEnLigneService`

`-> quatre nouveaux snapshots`

`-> statut en_cours`

`-> combat prêt pour le round 1`

### Tests unitaires

Les tests vérifient :

- la création d’un combat en attente ;
- la création des quatre snapshots du joueur 1 ;
- le refus d’un joueur déjà engagé ;
- le refus d’un joueur non enregistré ;
- le refus d’une équipe non enregistrée ;
- l’arrivée du joueur 2 ;
- la création de ses quatre snapshots ;
- le passage à `en_cours` ;
- le refus de rejoindre son propre combat ;
- le refus d’un combat déjà commencé ;
- le refus d’un combat introuvable.

### Tests MySQL

Un test d’intégration crée réellement les utilisateurs, Stickmans et équipes dans MySQL.

Après la création du combat, Doctrine est vidé puis les données sont relues.

Le test retrouve :

- un combat au statut `en_attente` ;
- le joueur 1 ;
- aucun joueur 2 ;
- quatre snapshots.

Après la jonction, Doctrine est de nouveau vidé.

Le test retrouve :

- le statut `en_cours` ;
- les deux participants ;
- huit snapshots ;
- les slots A, B, C et D pour chaque joueur ;
- le même combat actif pour les deux participants.

### Test HTTP complet

Le test du salon simule les deux utilisateurs.

Il vérifie que :

- le joueur 1 voit uniquement sa propre équipe ;
- il crée le combat avec un jeton CSRF ;
- le joueur 2 voit le combat disponible ;
- il voit uniquement sa propre équipe ;
- il rejoint le combat avec un jeton CSRF ;
- le combat disparaît de la liste disponible ;
- le joueur 2 retrouve ensuite son combat actif ;
- les huit snapshots sont persistés dans MySQL.

### Résultats finaux

- 77 tests réussis ;
- 593 assertions ;
- aucune notice PHPUnit ;
- conteneur Symfony valide ;
- six routes HTTP de combat en ligne enregistrées ;
- mapping Doctrine valide ;
- schéma de développement synchronisé ;
- schéma de test synchronisé ;
- aucune migration nécessaire.

### Limites actuelles

Le salon retourne uniquement du JSON.

Aucune page Twig ou logique JavaScript ne consomme encore ces routes.

Le rafraîchissement automatique du salon et du combat devra être ajouté à la future interface.

La version locale basée sur la session Symfony reste intacte.

### Compréhension retenue

La création et la jonction sont deux opérations métier différentes.

Le créateur produit un combat incomplet au statut `en_attente`.

Le deuxième joueur complète le combat et déclenche le statut `en_cours`.

Les équipes originales restent liées aux utilisateurs, tandis que le combat utilise des snapshots figés.

Le verrou d’écriture protège la dernière place du combat contre les requêtes concurrentes.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J40 — Ajouter l’interface du combat en ligne

### Objectif

J40 rend le combat en ligne réellement utilisable depuis le navigateur.

L’interface Twig et JavaScript consomme les routes JSON créées pendant J38 et J39 sans réécrire le moteur métier ni modifier l’ancien prototype local disponible sur `/combat`.

Le joueur peut maintenant :

- consulter ses équipes ;
- sélectionner une équipe ;
- créer un combat ;
- rejoindre un combat disponible ;
- attendre l’arrivée d’un adversaire ;
- voir les deux équipes et leurs PV persistés ;
- préparer les quatre cibles de son plan secret ;
- envoyer son plan ;
- attendre le plan adverse ;
- détecter automatiquement la résolution du round ;
- consulter les dégâts et les nouveaux PV ;
- abandonner le combat ;
- voir la victoire, la défaite, le match nul ou l’abandon.

### Architecture choisie

Une route HTML séparée a été ajoutée :

- `GET /combats`.

Cette route est gérée par `InterfaceCombatEnLigneController`.

Elle affiche uniquement la structure Twig de l’interface. Les données métier ne sont pas injectées directement dans le HTML.

Le contrôleur Stimulus `combat_en_ligne_controller.js` orchestre ensuite les appels vers les routes JSON existantes :

- `GET /salon-combat-en-ligne` ;
- `POST /salon-combat-en-ligne/creer` ;
- `POST /salon-combat-en-ligne/{id}/rejoindre` ;
- `GET /combat-en-ligne/{id}` ;
- `POST /combat-en-ligne/{id}/plan` ;
- `POST /combat-en-ligne/{id}/abandon`.

Les contrôleurs HTTP restent minces.

Les règles de création, de jonction, de soumission, de résolution et d’abandon restent dans les services métier existants.

Le moteur local disponible sur `/combat` n’a pas été modifié.

### Enrichissement des réponses du salon

La réponse du salon contient maintenant les compositions complètes des équipes appartenant au joueur.

Pour chaque combattant, elle expose les informations nécessaires à l’affichage :

- slot ;
- nom ;
- image ;
- rareté ;
- PV ;
- attaque ;
- défense.

Les combats disponibles indiquent également l’adresse email du créateur afin que l’interface puisse présenter clairement l’adversaire potentiel.

Le serveur reste responsable de vérifier l’appartenance de l’équipe lors de la création ou de la jonction.

### Enrichissement de l’état du combat

La réponse de `GET /combat-en-ligne/{id}` fournit maintenant une représentation adaptée au participant connecté.

Elle contient notamment :

- `moi` ;
- `adversaire` ;
- leurs combattants issus des snapshots ;
- les PV maximums et actuels ;
- l’état vivant ou KO ;
- le statut du combat ;
- le numéro du round ;
- le gagnant éventuel ;
- l’état de préparation des plans ;
- les jetons CSRF ;
- le dernier round résolu.

Le participant reçoit uniquement les informations nécessaires à son interface.

Le contenu du plan adverse n’est jamais exposé.

### Persistance du dernier round résolu

Avant J40, seul le deuxième joueur qui envoyait son plan recevait immédiatement le résultat de la résolution dans la réponse de son POST.

Le premier joueur, déjà en attente, ne pouvait pas retrouver ce résultat complet avec un GET ultérieur.

Deux propriétés ont donc été ajoutées à `Combat` :

- `dernierRoundResolu` ;
- `derniersResultats`.

`ResolutionRoundCombatEnLigneService` enregistre maintenant dans le combat :

- le numéro du round résolu ;
- les attaques totales ;
- les défenses totales ;
- les dégâts calculés ;
- les dégâts réellement appliqués ;
- l’overkill ;
- les PV avant l’impact ;
- les PV restants.

La migration `Version20260822120000` ajoute les colonnes correspondantes dans MySQL.

Les deux participants peuvent ainsi récupérer exactement le même résultat finalisé avec `GET /combat-en-ligne/{id}`, même après la réponse du deuxième POST.

### Interface du salon

Au chargement de `/combats`, le contrôleur Stimulus consulte le salon.

Si le joueur ne possède aucun combat actif, l’interface affiche :

- ses équipes disponibles ;
- la composition de l’équipe sélectionnée ;
- le bouton de création ;
- la liste des combats disponibles ;
- les boutons permettant de rejoindre un combat.

L’absence d’équipe et l’absence de combat disponible sont gérées explicitement.

Après une création ou une jonction réussie, le salon est rechargé et l’interface bascule automatiquement vers le combat actif.

### Interface du combat actif

L’interface présente :

- l’identifiant du combat ;
- son statut ;
- le numéro du round ;
- l’adresse email des deux participants ;
- les quatre combattants de chaque équipe ;
- leurs statistiques ;
- leurs PV maximums et actuels ;
- leur état vivant ou KO.

Les cartes KO sont visuellement atténuées.

Lorsqu’un combat attend encore un adversaire, l’interface affiche un état d’attente sans inventer de deuxième composition.

### Plans secrets

Pendant un combat en cours, le joueur choisit :

- la cible d’attaque de l’équipe X ;
- la cible de défense de l’équipe X ;
- la cible d’attaque de l’équipe Y ;
- la cible de défense de l’équipe Y.

Les listes proposent uniquement des combattants encore vivants.

Le plan est envoyé en JSON au serveur avec le jeton CSRF dans l’en-tête `X-CSRF-TOKEN`.

Après l’envoi :

- le formulaire est masqué ;
- le joueur voit que son plan est enregistré ;
- les choix adverses restent secrets ;
- l’interface attend la résolution.

Le navigateur ne calcule aucun dégât et ne décide jamais du résultat du round.

### Polling HTTP

Une première synchronisation temps réel simple a été choisie.

Tant que le combat est au statut `en_attente` ou `en_cours`, le contrôleur Stimulus recharge l’état toutes les trois secondes.

Ce polling permet de détecter :

- l’arrivée du deuxième joueur ;
- l’envoi du plan adverse ;
- la résolution du round ;
- la mise à jour des PV ;
- le passage au round suivant ;
- la fin du combat.

Le polling s’arrête automatiquement lorsque le combat est terminé ou abandonné.

Aucun WebSocket ou Mercure n’a été introduit pendant J40.

### Affichage de la résolution

Après la résolution, l’interface affiche un tableau contenant :

- la cible touchée ;
- l’attaque totale ;
- la défense totale ;
- les dégâts effectifs ;
- les PV restants.

Les clés `joueur1` et `joueur2` sont traduites selon le point de vue de l’utilisateur connecté :

- `Ton équipe` ;
- `Équipe adverse`.

Cette traduction est uniquement visuelle. Les valeurs proviennent toutes du serveur.

### Abandon

Un bouton d’abandon est disponible uniquement lorsque le combat est réellement en cours et possède deux participants.

Une confirmation est demandée avant l’envoi.

Le navigateur transmet ensuite une requête POST avec le jeton CSRF prévu pour l’abandon.

`AbandonCombatService` reste l’unique responsable de :

- verrouiller le combat ;
- vérifier son statut ;
- vérifier le participant ;
- définir l’adversaire comme gagnant ;
- enregistrer le statut `abandonne`.

Après l’action, l’interface recharge l’état final fourni par le serveur.

### États de fin

L’interface distingue maintenant :

- victoire ;
- défaite ;
- match nul ;
- victoire par abandon ;
- défaite par abandon ;
- combat abandonné sans gagnant, par sécurité défensive.

Le rendu dépend uniquement de :

- `statut` ;
- `gagnantId` ;
- l’identifiant du participant connecté.

Le navigateur n’invente jamais le gagnant.

### Prévention des doubles actions

Pendant une création, une jonction, une soumission ou un abandon, les boutons sont désactivés.

Cette protection réduit les doubles clics et les doubles soumissions côté interface.

Les protections métier restent également présentes côté serveur :

- transaction Doctrine ;
- verrou d’écriture ;
- contraintes de validation ;
- refus des plans dupliqués ;
- vérification des statuts.

### Gestion des erreurs

Le contrôleur JavaScript vérifie :

- la présence d’une équipe sélectionnée ;
- la validité des identifiants ;
- la présence des quatre cibles ;
- la présence du jeton CSRF ;
- le type JSON de la réponse ;
- le statut HTTP.

Les messages d’erreur renvoyés par le serveur sont affichés à l’utilisateur.

Une réponse non JSON ou une erreur inattendue produit un message générique sans exposer d’information sensible.

### Sécurité

La route HTML `/combats` nécessite `ROLE_USER`.

Les routes de consultation et d’action conservent leurs protections existantes :

- authentification Symfony ;
- `CombatVoter` pour les participants ;
- appartenance des équipes vérifiée côté serveur ;
- jetons CSRF dans `X-CSRF-TOKEN` ;
- validation du JSON ;
- transactions Doctrine ;
- verrous d’écriture ;
- absence des plans adverses dans les réponses.

Le JavaScript construit les éléments avec `textContent`.

Il n’insère pas de données provenant du serveur avec `innerHTML`, ce qui limite les risques d’injection dans l’interface.

Les PV, les statistiques, les dégâts, le numéro du round et le gagnant restent contrôlés par le serveur.

### Habillage visuel

Un fichier CSS dédié à `/combats` reprend l’univers graphique de la V24 :

- fond bleu nuit ;
- panneaux sombres ;
- accents cyan et violet ;
- boutons bleus ;
- cartes claires pour les Stickmans ;
- couleurs spécifiques pour la victoire et la défaite ;
- mise en page responsive.

Le style est chargé uniquement par le template du combat en ligne.

L’ancien CSS global et les autres pages ne sont pas volontairement refactorisés.

Une collision de priorité avec `app.css` a été détectée pendant le contrôle visuel.

Les règles du fond utilisent maintenant `:has(#combat-en-ligne)` afin de garantir le thème sombre uniquement lorsque cette interface est présente.

### Trajet des données

`navigateur`

`-> GET /combats`

`-> InterfaceCombatEnLigneController`

`-> template Twig`

`-> contrôleur Stimulus`

`-> GET du salon ou du combat`

`-> contrôleur JSON`

`-> authentification et autorisation`

`-> service métier`

`-> repository`

`-> entités et snapshots`

`-> Doctrine`

`-> MySQL`

`-> réponse JSON sécurisée`

`-> mise à jour de l’interface`

Pour un plan :

`formulaire secret`

`-> JavaScript`

`-> POST JSON avec X-CSRF-TOKEN`

`-> CombatEnLigneController`

`-> CombatVoter`

`-> SoumissionPlanCombatService`

`-> transaction et verrou Doctrine`

`-> PlanRoundCombat dans MySQL`

`-> ResolutionRoundCombatEnLigneService si les deux plans existent`

`-> mise à jour des PV et du dernier résultat`

`-> réponse JSON`

`-> polling GET`

`-> affichage du résultat par les deux participants`

### Fichiers créés

- `assets/controllers/combat_en_ligne_controller.js` ;
- `assets/styles/combat_en_ligne.css` ;
- `migrations/Version20260822120000.php` ;
- `src/Controller/InterfaceCombatEnLigneController.php` ;
- `templates/combat_en_ligne/index.html.twig` ;
- `tests/Controller/InterfaceCombatEnLigneControllerTest.php`.

### Fichiers modifiés

- `src/Controller/CombatEnLigneController.php` ;
- `src/Controller/SalonCombatEnLigneController.php` ;
- `src/Entity/Combat.php` ;
- `src/Service/ResolutionRoundCombatEnLigneService.php` ;
- `tests/Controller/CombatEnLigneControllerTest.php` ;
- `tests/Controller/ResolutionRoundCombatHttpTest.php` ;
- `tests/Controller/SalonCombatEnLigneControllerTest.php` ;
- `tests/Service/ResolutionRoundCombatEnLigneServiceTest.php`.

### Tests ajoutés ou enrichis

Les tests vérifient notamment :

- la redirection d’un visiteur anonyme vers la connexion ;
- l’accès du joueur connecté à `/combats` ;
- la présence des URL JSON dans le HTML ;
- la présence des cibles Stimulus ;
- la présence du CSS dédié ;
- les compositions complètes dans le salon ;
- les deux participants et leurs snapshots dans l’état du combat ;
- l’absence des plans secrets dans les réponses ;
- l’état initial sans dernier résultat ;
- l’enregistrement du dernier round dans `Combat` ;
- la persistance JSON dans MySQL ;
- la relecture du même résultat par les deux participants ;
- la position relative `joueur1` ou `joueur2` ;
- la résolution HTTP complète d’un round ;
- la persistance des nouveaux PV.

### Erreurs rencontrées et corrections

MySQL normalise l’ordre des clés d’une colonne JSON.

Une comparaison PHPUnit avec `assertSame()` échouait donc malgré des valeurs identiques.

Le test utilise maintenant `assertEquals()` pour comparer le contenu JSON sémantiquement sans dépendre de l’ordre d’insertion des clés.

Un test BrowserKit conservait également un proxy Doctrine paresseux entre deux requêtes HTTP.

Lors de la deuxième connexion simulée, le proxy ne pouvait plus être réhydraté correctement.

Le test utilise maintenant les objets utilisateurs complets pour les sessions de sécurité, tout en conservant les objets relus depuis MySQL pour les assertions de persistance.

Node.js n’était pas installé dans l’environnement PowerShell.

La présence du contrôleur JavaScript et du CSS a donc été vérifiée avec AssetMapper, tandis que le comportement HTTP et la structure Twig sont couverts par PHPUnit.

Symfony CLI a rencontré un verrou sur son ancien fichier journal pendant le contrôle visuel.

Le serveur PHP intégré a été utilisé temporairement sur `127.0.0.1:8000`, puis arrêté avant la validation finale.

Les avertissements Git concernant la conversion future LF vers CRLF sont informatifs.

`git diff --check` ne signale aucune erreur d’espacement.

### Compréhension retenue

Twig fournit une structure HTML initiale, mais ne doit pas recevoir les secrets ou prendre de décision métier.

Stimulus orchestre les requêtes, le polling et le rendu dynamique.

Les contrôleurs HTTP adaptent les entrées et les sorties sans remplacer les services.

Le résultat d’un round doit être persisté pour que les deux participants puissent le retrouver indépendamment de l’ordre de leurs requêtes.

Une colonne JSON MySQL conserve les données mais pas nécessairement l’ordre original de ses clés.

Un proxy Doctrine ne doit pas être conservé sans précaution entre plusieurs requêtes BrowserKit.

La désactivation visuelle d’un bouton complète les protections serveur, mais ne les remplace jamais.

### Limites restantes

Le combat utilise un polling HTTP de trois secondes.

Mercure ou WebSocket pourra être étudié plus tard si un vrai temps réel devient nécessaire.

Le JavaScript ne possède pas encore de tests unitaires dédiés, car Node.js n’est pas installé dans l’environnement actuel.

La confirmation d’abandon utilise la boîte de dialogue native du navigateur.

Une modale personnalisée inspirée de la V24 pourra être ajoutée ultérieurement.

L’interface se concentre sur le combat actif et ne fournit pas encore d’historique détaillé ou de rapport complet des rounds.

Des améliorations visuelles restent possibles, mais le parcours métier principal est fonctionnel.

### Résultats finaux

- 79 tests réussis ;
- 689 assertions ;
- aucune notice PHPUnit ;
- syntaxe PHP valide pour tous les fichiers modifiés ;
- template Twig valide ;
- conteneur Symfony valide ;
- mapping Doctrine valide ;
- schéma de développement synchronisé ;
- schéma de test synchronisé ;
- migration J40 exécutée en développement et en test ;
- sept routes HTTP du combat en ligne enregistrées ;
- contrôleur Stimulus détecté par AssetMapper ;
- CSS dédié détecté par AssetMapper ;
- `git diff --check` valide ;
- ancien parcours local `/combat` préservé.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J41 — Sécuriser l’attente des combats en ligne

### Objectif

J41 empêche un joueur de rester définitivement bloqué dans un combat en attente lorsqu’aucun adversaire ne le rejoint.

Deux mécanismes complémentaires ont été ajoutés :

- l’annulation manuelle par le créateur ;
- l’annulation automatique après cinq minutes sans adversaire.

### Annulation manuelle

Une nouvelle route sécurisée permet au créateur d’annuler son combat :

- `POST /combat-en-ligne/{id}/annuler`.

L’annulation est autorisée uniquement lorsque :

- le combat existe ;
- le joueur connecté est son créateur ;
- le combat possède le statut `en_attente` ;
- aucun deuxième joueur ne l’a encore rejoint.

La requête utilise un jeton CSRF dédié nommé `annuler`.

`AnnulationCombatEnLigneService` centralise les règles métier. Il utilise une transaction Doctrine et un verrou d’écriture afin d’éviter qu’une annulation et une jonction puissent modifier simultanément le même combat.

Après l’annulation :

- le statut devient `annule` ;
- aucun gagnant n’est enregistré ;
- le combat n’est plus considéré comme actif ;
- le joueur peut créer ou rejoindre un autre combat.

### Annulation automatique

Un combat en attente sans adversaire expire après 300 secondes, soit cinq minutes.

La date limite est calculée depuis `dateCreation` par l’entité `Combat`.

`ExpirationCombatEnAttenteService` vérifie cette limite sous transaction et verrou d’écriture.

L’expiration est déclenchée côté serveur lors :

- de la consultation de l’état du combat ;
- du chargement du salon ;
- d’une tentative pour rejoindre le combat.

Le polling HTTP exécuté toutes les trois secondes déclenche donc naturellement l’expiration lorsque le créateur reste sur l’écran d’attente.

Il ne s’agit pas encore d’une tâche planifiée indépendante : un accès HTTP au salon, au combat ou à sa route de jonction déclenche la vérification.

### Protection de la jonction

`RejoindreCombatEnLigneService` vérifie maintenant la date d’expiration pendant sa transaction.

Si un joueur tente de rejoindre un combat expiré :

- le combat est enregistré avec le statut `annule` ;
- la jonction est refusée ;
- aucun joueur 2 n’est associé ;
- un message métier explique que le délai de cinq minutes est dépassé.

L’annulation est enregistrée avant le déclenchement de l’exception afin de ne pas être annulée par un rollback de transaction.

### Évolution de l’entité Combat

Un nouveau statut a été ajouté :

- `Combat::STATUT_ANNULE`.

L’entité fournit également :

- `DUREE_MAX_ATTENTE_SECONDES` ;
- `getDateExpirationAttente()` ;
- `estAttenteExpiree()` ;
- `estAnnule()`.

Aucune migration supplémentaire n’est nécessaire, car le statut est déjà stocké dans une colonne texte compatible avec la nouvelle valeur.

### Interface utilisateur

Lorsque le combat attend encore un adversaire, son créateur dispose maintenant du bouton :

- `Annuler ce combat`.

Après une annulation réussie, l’interface recharge automatiquement le salon et affiche un message d’information.

Lorsqu’une expiration automatique est détectée :

- le combat actif est retiré de l’interface ;
- le salon est rechargé ;
- un message indique que le combat a été annulé après cinq minutes sans adversaire.

Le bouton d’abandon reste réservé aux combats réellement commencés avec deux participants.

### Sécurité

Les protections suivantes sont conservées :

- authentification avec `ROLE_USER` ;
- autorisation par `CombatVoter` ;
- jeton CSRF ;
- transaction Doctrine ;
- verrou pessimiste d’écriture ;
- vérification du créateur ;
- vérification du statut ;
- vérification de l’absence d’adversaire.

Le navigateur ne décide jamais seul si un combat peut être annulé ou s’il a expiré.

### Fichiers créés

- `src/Service/AnnulationCombatEnLigneService.php` ;
- `src/Service/ExpirationCombatEnAttenteService.php` ;
- `tests/Service/AnnulationCombatEnLigneServiceTest.php` ;
- `tests/Service/ExpirationCombatEnAttenteServiceTest.php`.

### Fichiers modifiés

- `assets/controllers/combat_en_ligne_controller.js` ;
- `assets/styles/combat_en_ligne.css` ;
- `src/Controller/CombatEnLigneController.php` ;
- `src/Controller/SalonCombatEnLigneController.php` ;
- `src/Entity/Combat.php` ;
- `src/Service/RejoindreCombatEnLigneService.php` ;
- `templates/combat_en_ligne/index.html.twig` ;
- `tests/Controller/CombatEnLigneControllerTest.php` ;
- `tests/Controller/InterfaceCombatEnLigneControllerTest.php` ;
- `tests/Service/RejoindreCombatEnLigneServiceTest.php`.

### Tests ajoutés ou enrichis

Les tests vérifient notamment :

- l’annulation d’un combat en attente par son créateur ;
- le refus d’annuler un combat déjà commencé ;
- le refus d’annuler par un autre utilisateur ;
- le refus d’annuler un combat possédant déjà un adversaire ;
- l’expiration exactement après cinq minutes ;
- l’absence d’expiration avant cinq minutes ;
- l’absence d’expiration d’un combat commencé ;
- le refus de rejoindre un combat expiré ;
- la persistance du statut `annule` ;
- l’absence de gagnant après une annulation ;
- la présence du bouton et des cibles Stimulus ;
- le retour automatique vers le salon.

### Erreur rencontrée et correction

Le premier test HTTP d’expiration utilisait `CURRENT_TIMESTAMP` côté MySQL alors que le service utilisait l’horloge PHP/Symfony.

Selon la configuration des fuseaux horaires, les deux valeurs pouvaient être décalées et rendre le test instable.

Le test retire maintenant six minutes à la date de création déjà enregistrée en base. Il ne dépend donc plus du fuseau horaire du serveur MySQL.

### Validation manuelle

Le parcours a été contrôlé avec deux comptes :

- les anciens combats en attente sont annulés ;
- le joueur n’est plus bloqué sur l’écran d’attente ;
- l’annulation manuelle fonctionne ;
- un second compte peut rejoindre le combat du premier joueur ;
- les joueurs peuvent ensuite poursuivre le combat normalement.

### Résultats finaux

- 92 tests réussis ;
- 763 assertions ;
- 29 templates Twig valides ;
- conteneur Symfony valide ;
- mapping Doctrine valide ;
- schéma de base de données synchronisé ;
- syntaxe PHP valide ;
- tests manuels réussis ;
- aucun commit ni push effectué avant la validation complète.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J42 — Persister l’historique des rounds

### Objectif

J42 permet de conserver durablement le résultat de chaque round d’un combat en ligne.

Avant cette évolution, l’entité `Combat` conservait uniquement le dernier round résolu. Chaque nouvelle résolution remplaçait donc les résultats précédents.

L’historique persistant constitue une base nécessaire pour :

- afficher le déroulement complet d’un combat ;
- construire les futurs rapports détaillés ;
- retrouver l’évolution des points de vie ;
- préparer les futures animations et barres de vie interactives ;
- diagnostiquer plus facilement une résolution incorrecte.

### Nouvelle entité ResultatRoundCombat

L’entité `ResultatRoundCombat` représente le résultat définitif d’un round résolu.

Elle conserve :

- le combat concerné ;
- le numéro du round ;
- les résultats détaillés au format JSON ;
- la date et l’heure de résolution.

Chaque résultat est associé à son combat par une relation Doctrine `ManyToOne`.

L’entité `Combat` possède maintenant la relation inverse `OneToMany` nommée `resultatsRounds`.

La suppression d’un combat supprime également son historique grâce à la suppression en cascade.

### Unicité des rounds

Une contrainte unique protège le couple :

- combat ;
- numéro du round.

Un même combat ne peut donc pas enregistrer deux résultats différents pour le même numéro de round.

Cette contrainte complète les protections déjà présentes dans le service de résolution :

- transaction Doctrine ;
- verrou pessimiste d’écriture ;
- contrôle du statut ;
- contrôle des deux plans ;
- résolution unique du round.

### Persistance transactionnelle

`ResolutionRoundCombatEnLigneService` crée maintenant un `ResultatRoundCombat` pendant la transaction qui résout le round.

L’enregistrement du résultat fait donc partie de la même opération atomique que :

- le calcul des dégâts ;
- la mise à jour des points de vie ;
- le passage au round suivant ;
- la détermination éventuelle du gagnant ;
- la suppression des plans devenus inutiles.

Si la transaction échoue, aucune partie du round n’est enregistrée partiellement.

La relation Doctrine utilise la persistance en cascade depuis `Combat`.

### Repository dédié

`ResultatRoundCombatRepository` fournit la méthode :

- `trouverPourCombat()`.

Cette méthode retourne tous les résultats d’un combat dans l’ordre croissant des rounds.

L’ordre est donc défini côté serveur et ne dépend pas de l’interface utilisateur.

### Migration Doctrine

La migration `Version20260823120000` crée la table :

- `resultat_round_combat`.

Elle ajoute :

- la clé primaire ;
- la relation vers `combat` ;
- le numéro du round ;
- les résultats JSON ;
- la date de résolution ;
- la contrainte unique combat/round ;
- la clé étrangère avec suppression en cascade ;
- l’index Doctrine attendu sur la relation.

La migration a été appliquée dans les environnements de développement et de test.

Les deux schémas sont synchronisés avec le mapping Doctrine.

### Exposition HTTP sécurisée

La route d’état du combat expose maintenant le champ :

- `historiqueRounds`.

Ce champ contient uniquement les rounds déjà résolus, avec :

- leur numéro ;
- leurs résultats définitifs.

Les résultats sont retournés dans l’ordre chronologique.

Les plans secrets ne sont jamais ajoutés à cet historique.

Les cibles d’attaque et de défense du round courant restent donc invisibles jusqu’à la résolution par le serveur.

Le champ historique est vide lorsqu’aucun round n’a encore été résolu.

### Compatibilité avec le dernier round

Les propriétés existantes de `Combat` sont conservées :

- `dernierRoundResolu` ;
- `derniersResultats`.

Elles continuent d’alimenter l’interface actuelle sans rupture.

Le nouvel historique complète ces données sans modifier immédiatement l’affichage du combat.

Les futures améliorations visuelles pourront ainsi être développées progressivement, sans brûler les étapes.

### Tests ajoutés ou enrichis

Les tests vérifient notamment :

- la création d’un résultat de round valide ;
- le refus d’un numéro de round inférieur à 1 ;
- le refus d’un résultat vide ;
- l’association automatique entre le résultat et son combat ;
- la création d’un historique pendant la transaction de résolution ;
- l’absence d’historique lorsqu’un seul plan est soumis ;
- l’absence de doublon lors d’une deuxième tentative de résolution ;
- la persistance de deux rounds consécutifs dans MySQL ;
- la conservation du résultat du premier round après le second ;
- l’ordre croissant des résultats retournés par le repository ;
- la relecture complète après vidage de l’EntityManager ;
- l’exposition HTTP des deux rounds résolus ;
- l’absence des cibles secrètes dans la réponse HTTP.

### Erreurs rencontrées et corrections

Après la première migration, Doctrine signalait que le schéma n’était pas synchronisé.

La seule différence concernait le nom de l’index créé sur la relation vers `Combat`.

La migration utilise maintenant directement le nom d’index attendu par Doctrine :

- `IDX_86ECD08AFC7EEDB8`.

Les index déjà créés dans les bases de développement et de test ont été renommés avec `dbal:run-sql`.

Les deux schémas sont maintenant totalement synchronisés.

Pendant l’extension du test HTTP à deux rounds, le navigateur de test conservait un ancien objet `User` en session après un vidage de l’EntityManager.

Cela provoquait une collision d’identité Doctrine.

Le test conserve maintenant les mêmes identités de session pendant les deux rounds, puis effectue une nouvelle relecture MySQL après leur résolution.

### Fichiers créés

- `migrations/Version20260823120000.php` ;
- `src/Entity/ResultatRoundCombat.php` ;
- `src/Repository/ResultatRoundCombatRepository.php` ;
- `tests/Entity/ResultatRoundCombatTest.php`.

### Fichiers modifiés

- `src/Controller/CombatEnLigneController.php` ;
- `src/Entity/Combat.php` ;
- `src/Service/ResolutionRoundCombatEnLigneService.php` ;
- `tests/Controller/CombatEnLigneControllerTest.php` ;
- `tests/Controller/ResolutionRoundCombatHttpTest.php` ;
- `tests/Service/ResolutionRoundCombatEnLigneServiceTest.php`.

### Résultats finaux

- 95 tests réussis ;
- 809 assertions ;
- syntaxe PHP valide ;
- conteneur Symfony valide ;
- mapping Doctrine valide ;
- schémas de développement et de test synchronisés ;
- 11 migrations exécutées et disponibles ;
- aucune migration restante ;
- historique de deux rounds relu depuis MySQL ;
- aucun plan secret exposé par l’API ;
- aucun commit ni push effectué avant la validation complète.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J43 — Afficher l’historique des rounds dans le combat

### Objectif

J43 rend l’historique persistant créé pendant J42 directement consultable depuis l’interface du combat en ligne.

Le joueur peut maintenant revoir les résultats des rounds précédents sans quitter le combat en cours.

### Affichage des rounds précédents

Une nouvelle section `Rounds précédents` est affichée lorsqu’au moins deux rounds ont été résolus.

Le dernier round résolu reste présenté dans le tableau principal existant.

Les rounds plus anciens sont affichés dans des panneaux repliables afin de conserver une interface compacte.

Chaque panneau indique :

- le numéro du round ;
- les dégâts infligés par le joueur connecté ;
- les dégâts reçus par le joueur connecté.

### Détail d’un round

Chaque round précédent peut être ouvert pour consulter son résultat complet.

Le tableau présente :

- la cible concernée ;
- l’attaque totale ;
- la défense totale ;
- les dégâts effectifs ;
- les points de vie restants.

Les informations sont interprétées depuis le point de vue du joueur connecté, quelle que soit sa position dans le combat.

### Compatibilité avec l’actualisation automatique

L’interface du combat continue d’interroger régulièrement le serveur afin de détecter :

- le plan de l’adversaire ;
- la résolution d’un round ;
- le passage au round suivant ;
- la fin du combat.

La première version de l’historique reconstruisait les panneaux pendant chaque actualisation automatique.

Un round ouvert se refermait donc après quelques secondes.

Le contrôleur Stimulus mémorise maintenant les numéros des panneaux ouverts avant la mise à jour, puis restaure leur état après le nouvel affichage.

L’ouverture et la fermeture d’un round persistent ainsi pendant les actualisations automatiques.

### Construction sécurisée de l’interface

Les éléments de l’historique sont construits avec les API du navigateur :

- `createElement()` ;
- `textContent` ;
- `replaceChildren()`.

Aucune donnée provenant du serveur n’est injectée avec `innerHTML`.

Les résultats affichés proviennent exclusivement de `historiqueRounds`, qui contient uniquement les rounds définitivement résolus.

Les plans secrets du round courant ne sont jamais exposés dans l’historique.

### Accessibilité et affichage responsive

Les rounds précédents utilisent les éléments HTML natifs `details` et `summary`.

Ils peuvent donc être manipulés au clavier et conservent une structure sémantique claire.

Un indicateur visuel accompagne leur ouverture.

Sur les écrans étroits :

- le résumé s’adapte à la largeur disponible ;
- le tableau reste lisible grâce au défilement horizontal ;
- la disposition des informations est réorganisée.

### Tests et validation manuelle

Les tests de l’interface vérifient la présence :

- de la section dédiée à l’historique ;
- de la cible Stimulus contenant la liste ;
- du titre accessible `Rounds précédents`.

La validation manuelle a confirmé que :

- les anciens rounds sont correctement affichés ;
- un round peut être ouvert et refermé ;
- son état reste identique après plusieurs actualisations automatiques ;
- le dernier round reste séparé des rounds précédents ;
- les dégâts sont présentés du point de vue du joueur connecté.

### Fichiers modifiés

- `assets/controllers/combat_en_ligne_controller.js` ;
- `assets/styles/combat_en_ligne.css` ;
- `templates/combat_en_ligne/index.html.twig` ;
- `tests/Controller/InterfaceCombatEnLigneControllerTest.php`.

### Résultats finaux

- 95 tests réussis ;
- 813 assertions ;
- syntaxe JavaScript valide ;
- syntaxe Twig valide ;
- conteneur Symfony valide ;
- ressources JavaScript et CSS détectées par AssetMapper ;
- historique des rounds affiché dans l’ordre chronologique ;
- état ouvert ou fermé conservé pendant l’actualisation automatique ;
- affichage responsive et accessible ;
- aucun plan secret exposé ;
- aucune erreur détectée par `git diff --check` ;
- aucun commit ni push effectué avant la validation complète.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J44 — Ajouter les barres de vie dynamiques

### Objectif

J44 améliore la lecture de l’état du combat en ajoutant une barre de vie dynamique à chaque Stickman affiché dans l’interface du combat en ligne.

Les joueurs peuvent maintenant comprendre immédiatement :

- les points de vie actuels ;
- les points de vie maximum ;
- l’état général du Stickman ;
- la gravité de ses blessures ;
- son éventuelle élimination.

### Réutilisation des données du serveur

Le serveur exposait déjà pour chaque combattant :

- `pvActuels` ;
- `pvMaximum` ;
- `vivant`.

Aucune modification du moteur de combat, de Doctrine ou de la base de données n’a donc été nécessaire.

Les barres de vie sont calculées exclusivement à partir des données validées et enregistrées par le serveur.

L’interface ne réalise aucun calcul de dégâts.

### Barre de vie proportionnelle

Chaque carte de combattant affiche maintenant :

- la valeur `PV actuels / PV maximum` ;
- une barre dont la longueur correspond au pourcentage de vie restant ;
- un libellé décrivant l’état du combattant.

Les valeurs reçues sont sécurisées côté interface :

- les PV ne peuvent pas être affichés sous zéro ;
- les PV actuels ne peuvent pas dépasser les PV maximum ;
- le pourcentage reste compris entre 0 et 100 %.

### États visuels

Quatre états sont disponibles :

- `Stable` lorsque le combattant possède plus de 50 % de ses PV ;
- `Blessé` lorsqu’il possède entre 26 et 50 % de ses PV ;
- `Critique` lorsqu’il possède au maximum 25 % de ses PV ;
- `K.O.` lorsqu’il ne possède plus aucun PV ou que le serveur le déclare éliminé.

Chaque état possède un rendu distinct :

- vert pour un état stable ;
- orange pour un combattant blessé ;
- rouge pour un état critique ;
- gris pour un combattant K.O.

Le libellé textuel permet de comprendre l’état sans dépendre uniquement de la couleur.

### Animation des dégâts

Lors de chaque actualisation, l’interface mémorise le pourcentage précédemment affiché pour chaque slot.

Si les PV ont changé, la nouvelle carte commence avec l’ancienne longueur, puis anime la barre jusqu’à sa nouvelle valeur.

Si les PV sont identiques, aucune animation inutile n’est déclenchée.

Ce fonctionnement reste compatible avec l’actualisation automatique du combat.

### État K.O.

Lorsqu’un combattant atteint zéro PV :

- sa barre devient vide ;
- son état affiche `K.O.` ;
- sa carte est désaturée ;
- son opacité est réduite ;
- il reste exclu des cibles disponibles pour les rounds suivants.

L’état visuel complète donc la protection fonctionnelle déjà appliquée par le serveur et l’interface.

### Accessibilité

Chaque barre utilise le rôle ARIA `progressbar`.

Elle fournit :

- un nom accessible contenant le nom du Stickman ;
- une valeur minimum de zéro ;
- la valeur maximum des PV ;
- la valeur actuelle ;
- une description textuelle complète de l’état.

Les utilisateurs de lecteurs d’écran peuvent ainsi connaître les points de vie sans dépendre du rendu graphique.

La préférence système `prefers-reduced-motion` désactive les transitions pour les utilisateurs sensibles aux animations.

### Tests enrichis

Le test HTTP du contrôleur vérifie maintenant également que les données nécessaires aux barres sont exposées pour l’adversaire :

- ses PV actuels ;
- son état vivant.

Ces vérifications complètent celles déjà présentes pour :

- les PV maximum ;
- les statistiques ;
- les quatre slots ;
- les deux participants.

### Validation manuelle

Un combat réel a permis de confirmer que :

- chaque Stickman possède une barre de vie ;
- la longueur correspond aux PV restants ;
- la barre diminue après les dégâts ;
- les couleurs évoluent correctement ;
- les libellés `Stable`, `Blessé`, `Critique` et `K.O.` correspondent à l’état ;
- un combattant éliminé apparaît correctement en K.O. ;
- les barres ne se réaniment pas inutilement pendant l’actualisation automatique.

### Fichiers modifiés

- `assets/controllers/combat_en_ligne_controller.js` ;
- `assets/styles/combat_en_ligne.css` ;
- `tests/Controller/CombatEnLigneControllerTest.php`.

### Résultats finaux

- 95 tests réussis ;
- 815 assertions ;
- syntaxe JavaScript valide ;
- syntaxe PHP valide ;
- syntaxe Twig valide ;
- conteneur Symfony valide ;
- ressources JavaScript et CSS détectées par AssetMapper ;
- barres proportionnelles aux PV restants ;
- quatre états visuels fonctionnels ;
- animations déclenchées uniquement lors d’un changement ;
- préférence de réduction des animations respectée ;
- informations accessibles aux lecteurs d’écran ;
- aucune modification du moteur de combat ou de la base de données ;
- aucune erreur détectée par `git diff --check` ;
- aucun commit ni push effectué avant la validation complète.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J45 — Ajouter l’historique des combats du joueur

### Objectif

J45 permet au joueur de retrouver directement depuis le salon ses derniers combats terminés ou abandonnés.

Jusqu’à présent, les combats étaient conservés en base de données, mais ils n’étaient plus accessibles visuellement après le retour à la page des combats.

### Résultat obtenu

Une nouvelle section **« Mes derniers combats »** est maintenant affichée dans le salon.

Chaque combat présente :

- son identifiant ;
- l’adresse email de l’adversaire ;
- le résultat vu depuis le compte actuellement connecté ;
- le nombre de rounds résolus ;
- la date et l’heure de fin du combat.

Les combats sont classés du plus récent au plus ancien.

### Résultats possibles

Le résultat affiché dépend du joueur connecté :

- **Victoire** lorsque le joueur a remporté normalement le combat ;
- **Défaite** lorsque l’adversaire a remporté normalement le combat ;
- **Victoire par abandon** lorsque l’adversaire a abandonné ;
- **Abandon** lorsque le joueur connecté a abandonné ;
- **Match nul** lorsqu’aucun gagnant n’a été enregistré.

Cette interprétation est effectuée côté serveur afin de conserver une source de vérité unique.

### Sélection de l’historique

Le repository des combats fournit maintenant une recherche dédiée à l’historique d’un joueur.

Cette recherche :

- sélectionne uniquement les combats auxquels le joueur participe ;
- accepte le joueur aussi bien comme joueur 1 que comme joueur 2 ;
- conserve uniquement les combats terminés ou abandonnés ;
- exclut les combats annulés pendant l’attente d’un adversaire ;
- trie les résultats par date de mise à jour décroissante ;
- utilise l’identifiant comme second critère de tri ;
- limite l’historique aux dix combats les plus récents.

Les relations avec les deux joueurs et le gagnant sont chargées dans la même requête.

### Contrat JSON du salon

L’état du salon expose maintenant une propriété `historiqueCombats`.

Chaque entrée contient :

- `id` ;
- `adversaireEmail` ;
- `statut` ;
- `resultat` ;
- `nombreRounds` ;
- `dateFin`.

La date est transmise au format ISO 8601 pour permettre un affichage fiable côté navigateur.

### Interface utilisateur

La nouvelle section est intégrée sous les zones de création et de recherche d’un combat.

Les combats sont présentés sous forme de cartes responsives :

- deux colonnes lorsque l’espace disponible le permet ;
- une seule colonne sur les écrans plus étroits ;
- badge vert pour les victoires ;
- badge rouge pour les défaites et abandons ;
- badge jaune pour les matchs nuls ;
- informations lisibles même avec une adresse email longue.

Un message spécifique est affiché lorsque le joueur ne possède encore aucun historique.

### Mise à jour automatique

L’historique utilise le même état JSON que le reste du salon.

Il est donc actualisé avec l’interface sans introduire une seconde logique de récupération ni dupliquer les règles métier dans JavaScript.

Les éléments HTML sont construits avec les API du DOM et `textContent`, afin de ne pas injecter directement les données reçues dans du HTML interprété.

### Validation avec deux comptes

La fonctionnalité a été vérifiée manuellement depuis les deux côtés des mêmes combats.

Pour le compte gagnant :

- le combat gagné normalement apparaît comme **Victoire** ;
- le combat remporté après l’abandon adverse apparaît comme **Victoire par abandon**.

Pour le compte perdant :

- le même combat apparaît comme **Défaite** ;
- son propre abandon apparaît comme **Abandon**.

L’adversaire est correctement inversé selon le compte connecté.

Les combats sont affichés du plus récent au plus ancien et les combats annulés sans adversaire ne sont pas présents dans l’historique.

### Tests ajoutés et adaptés

Les tests contrôlent notamment :

- l’absence d’historique pour un nouveau joueur ;
- la présence des combats terminés pertinents ;
- l’exclusion des combats d’autres joueurs ;
- l’exclusion des combats annulés ;
- l’ordre chronologique décroissant ;
- l’adresse de l’adversaire ;
- le statut du combat ;
- le résultat vu depuis le joueur connecté ;
- le nombre de rounds résolus ;
- la présence de la date de fin ;
- la présence des nouvelles zones dans le template.

### Validation technique

Les vérifications suivantes sont réussies :

- syntaxe JavaScript valide ;
- syntaxe PHP valide ;
- syntaxe Twig valide ;
- conteneur Symfony valide ;
- assets JavaScript et CSS reconnus par AssetMapper ;
- absence d’erreur détectée par `git diff --check` ;
- suite PHPUnit complète réussie.

Résultat final :

- **96 tests réussis** ;
- **841 assertions réussies** ;
- aucune régression détectée.

### Fichiers modifiés

- `src/Repository/CombatRepository.php`
- `src/Controller/SalonCombatEnLigneController.php`
- `templates/combat_en_ligne/index.html.twig`
- `assets/controllers/combat_en_ligne_controller.js`
- `assets/styles/combat_en_ligne.css`
- `tests/Controller/SalonCombatEnLigneControllerTest.php`
- `tests/Controller/InterfaceCombatEnLigneControllerTest.php`

### Conclusion

J45 rend les combats passés accessibles depuis le salon et présente leur résultat correctement selon le joueur connecté.

Cette base permettra ensuite d’ajouter une page de rapport détaillé pour consulter le déroulement complet d’un ancien combat.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J46 — Consulter le rapport détaillé d’un combat

### Objectif

J46 permet d’ouvrir le rapport définitif d’un ancien combat depuis l’historique du joueur.

### Fonctionnalités ajoutées

Chaque carte de la section **« Mes derniers combats »** possède maintenant un bouton **« Voir le rapport »**.

La page du rapport affiche :

- le numéro du combat ;
- le résultat vu depuis le joueur connecté ;
- l’adversaire ;
- la date de fin ;
- le nombre de rounds joués ;
- les deux équipes avec leurs statistiques et PV finaux ;
- le détail de chaque round ;
- les attaques, défenses, dégâts et PV restants ;
- un lien de retour vers les combats.

Les combats terminés par abandon sans round résolu affichent un message spécifique.

### Sécurité

La route `GET /combats/{id}/rapport` utilise le `CombatVoter`.

Seuls les participants du combat peuvent consulter le rapport. Un utilisateur extérieur reçoit une réponse HTTP 403.

Le rapport n’est disponible que pour un combat terminé ou abandonné.

### Architecture

Le rapport réutilise :

- les snapshots persistants des combattants ;
- l’historique des rounds créé pendant J42 ;
- les règles d’autorisation existantes ;
- la feuille de style des combats en ligne.

Aucune migration et aucune nouvelle règle métier n’ont été nécessaires.

### Validation

Les vérifications PHP, JavaScript, Twig, Symfony, AssetMapper et PHPUnit sont réussies.

Résultat final :

- **97 tests réussis** ;
- **857 assertions réussies** ;
- rapport vérifié manuellement depuis un ancien combat ;
- aucune régression détectée.

### Fichiers modifiés

- `src/Controller/InterfaceCombatEnLigneController.php`
- `templates/combat_en_ligne/index.html.twig`
- `templates/combat_en_ligne/rapport.html.twig`
- `assets/controllers/combat_en_ligne_controller.js`
- `assets/styles/combat_en_ligne.css`
- `tests/Controller/InterfaceCombatEnLigneControllerTest.php`

### Conclusion

Le joueur peut maintenant retrouver un combat terminé dans son historique et consulter son déroulement complet dans une page protégée en lecture seule.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J47 — Rendre le catalogue du jeu transférable

### Objectif

J47 permet de sauvegarder dans un fichier versionné le catalogue créé depuis l’administration, afin de pouvoir le réinstaller sur une nouvelle base de données ou sur le futur serveur de production.

### Fonctionnalités ajoutées

Une commande Symfony exporte maintenant le catalogue :

`php bin/console app:catalogue:exporter`

L’export génère le fichier :

`data/catalogue.json`

Ce fichier contient :

- les Stickmans ;
- leurs noms et slugs techniques ;
- leurs descriptions et images ;
- leur rareté ;
- leurs PV, attaque et défense ;
- leur statut actif ou inactif ;
- les caisses ;
- le prix et le statut des caisses ;
- le contenu de chaque caisse ;
- le poids de chaque Stickman dans chaque caisse.

Les identifiants internes de la base de données ne sont pas exportés. Les relations utilisent les slugs techniques afin que le catalogue puisse être transféré vers une autre base.

### Import du catalogue

Une seconde commande Symfony permet de restaurer le catalogue :

`php bin/console app:catalogue:importer`

L’import est idempotent :

- un Stickman absent est créé ;
- un Stickman existant est mis à jour grâce à son slug ;
- une caisse absente est créée ;
- une caisse existante est mise à jour ;
- les associations et leurs poids sont créés ou actualisés ;
- relancer plusieurs fois le même import ne crée aucun doublon ;
- aucune donnée absente du fichier n’est supprimée automatiquement.

L’import est exécuté dans une transaction Doctrine. Une erreur annule donc entièrement l’opération.

### Sécurité des données

Le format JSON possède un numéro de version.

Avant tout import, le service vérifie notamment :

- la structure générale du fichier ;
- les champs obligatoires ;
- les types des valeurs ;
- les limites de rareté et de statistiques ;
- l’unicité des slugs ;
- l’unicité du contenu d’une caisse ;
- l’existence des Stickmans référencés par les caisses ;
- la validité des poids.

Le catalogue ne contient aucune donnée personnelle, aucun compte utilisateur, aucune collection de joueur, aucune équipe et aucun historique de combat.

### Unicité des slugs

Le slug devient l’identifiant technique stable utilisé pendant les transferts.

Une contrainte unique a été ajoutée sur `stickman.slug` afin d’empêcher la création de futurs doublons.

Les noms visibles peuvent rester identiques, mais chaque Stickman doit posséder un slug technique différent.

### Catalogue sauvegardé

Le premier export versionné contient :

- **27 Stickmans** ;
- **1 caisse** ;
- **10 associations caisse/Stickman** ;
- toutes les images actuellement utilisées par le catalogue.

Les 17 nouvelles illustrations, de la Recrue au Capitaine, sont maintenant prêtes à être suivies par Git avec le catalogue.

### Validation du transfert

Le catalogue a été importé deux fois successivement dans la base de test.

Il a ensuite été réexporté et comparé au fichier source.

Résultat :

- import idempotent confirmé ;
- fichier réexporté strictement identique ;
- empreinte SHA-256 identique ;
- aucune image référencée manquante ;
- schémas des bases `dev` et `test` synchronisés.

### Validation générale

Résultat final :

- **101 tests réussis** ;
- **870 assertions réussies** ;
- conteneur Symfony valide ;
- mapping Doctrine valide ;
- bases `dev` et `test` synchronisées ;
- aucune régression détectée.

### Fichiers ajoutés

- `data/catalogue.json`
- `src/Command/ExporterCatalogueJeuCommand.php`
- `src/Command/ImporterCatalogueJeuCommand.php`
- `src/Service/CatalogueJeuService.php`
- `tests/Service/CatalogueJeuServiceTest.php`
- `migrations/Version20260829120000.php`
- les nouvelles images `public/images/stickmen/11-Recrue.png` à `public/images/stickmen/27-Capitaine.png`

### Fichier modifié

- `src/Entity/Stickman.php`
- `DEVLOG.md`

### Conclusion

Le catalogue administré localement n’est désormais plus enfermé dans la base de développement.

Les Stickmans, les caisses, les probabilités et leurs images peuvent être versionnés avec le projet puis restaurés de manière fiable sur une nouvelle installation de StickVerse.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J48 — Initialiser une nouvelle base avec le catalogue versionné

### Objectif

J48 valide la procédure complète permettant d’installer StickVerse sur une base de données entièrement vide puis d’y restaurer le catalogue versionné créé pendant J47.

### Vérification d’une installation

Une nouvelle commande Symfony en lecture seule est disponible :

`php bin/console app:catalogue:verifier`

Cette commande compare strictement :

- les Stickmans présents dans la base ;
- leurs statistiques et leurs statuts ;
- les caisses ;
- les associations caisse/Stickman ;
- les poids de tirage ;
- le fichier `data/catalogue.json` ;
- les images présentes dans les dossiers publics.

La vérification échoue si la base diffère du catalogue ou si une image référencée est absente.

### Sécurité

La commande de vérification n’écrit aucune donnée.

Les noms de fichiers sont contrôlés afin d’empêcher qu’un chemin d’image sorte du dossier public prévu.

L’import reste transactionnel et idempotent. Il peut être relancé sans créer de doublons.

### Simulation sur une base neuve

Une base MySQL temporaire dédiée a été créée pour simuler une nouvelle installation complète.

La procédure a exécuté successivement :

- la création d’une base vide ;
- les migrations Doctrine depuis la première version ;
- l’import de `data/catalogue.json` ;
- la vérification stricte de la base ;
- le comptage des données importées ;
- la suppression de la base temporaire.

Résultat obtenu :

- **12 migrations appliquées** ;
- **27 Stickmans importés** ;
- **1 caisse importée** ;
- **10 associations importées** ;
- **28 images vérifiées** ;
- aucune différence entre la base et le catalogue ;
- base temporaire supprimée après le contrôle.

Les bases habituelles de développement et de test n’ont pas été remplacées par cette simulation.

### Documentation

Le fichier `docs/INSTALLATION_CATALOGUE.md` décrit maintenant :

- les prérequis de l’installation ;
- l’application des migrations ;
- l’import du catalogue ;
- la vérification finale ;
- la mise à jour du fichier versionné ;
- les précautions à prendre avec une base existante ;
- les données concernées et celles qui restent intactes.

Aucun mot de passe ni identifiant de base de données n’est stocké dans cette documentation.

### Validation

Les vérifications PHP, Symfony, Doctrine et PHPUnit sont réussies.

Résultat final :

- **103 tests réussis** ;
- **875 assertions réussies** ;
- conteneur Symfony valide ;
- mapping Doctrine valide ;
- base de développement synchronisée ;
- installation actuelle conforme au catalogue ;
- aucune régression détectée.

### Fichiers ajoutés

- `src/Command/VerifierInstallationCatalogueJeuCommand.php`
- `docs/INSTALLATION_CATALOGUE.md`

### Fichiers modifiés

- `src/Service/CatalogueJeuService.php`
- `tests/Service/CatalogueJeuServiceTest.php`
- `DEVLOG.md`

### Conclusion

StickVerse peut maintenant être installé sur une base entièrement vide de manière reproductible.

Après les migrations et l’import, une commande en lecture seule confirme que la base, le catalogue et toutes les images correspondent exactement à la version attendue.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J49 — Sécuriser le parcours d’un nouveau joueur

### Objectif

J49 garantit qu’un nouveau joueur peut aller de la création de son compte jusqu’au salon des combats sans rester bloqué par une collection vide ou incomplète.

### Pack de départ garanti

Chaque nouveau compte reçoit automatiquement quatre Stickmans différents :

- Guerrier ;
- Archer ;
- Lancier ;
- Tank.

Ces quatre Stickmans permettent de composer immédiatement une première équipe complète avec les emplacements A, B, C et D.

Le pack repose sur les slugs stables du catalogue versionné :

- `guerrier`
- `archer`
- `lancier`
- `tank`

### Initialisation sécurisée

Le nouveau service `InitialisationNouveauJoueurService` :

- recherche les quatre Stickmans actifs dans le catalogue ;
- vérifie que le pack est complet avant toute attribution ;
- crée les quatre entrées d’inventaire ;
- conserve un ordre de départ stable ;
- reste idempotent et ne crée pas de doublons s’il est relancé ;
- refuse l’initialisation si un Stickman obligatoire est absent ou inactif.

Aucune migration Doctrine n’est nécessaire.

### Inscription améliorée

Après une inscription réussie :

- le mot de passe est haché normalement ;
- le compte reçoit son pack de départ ;
- le joueur est connecté automatiquement ;
- un message de bienvenue est enregistré ;
- le joueur est redirigé vers sa collection.

Le passage manuel par la page de connexion après l’inscription n’est donc plus nécessaire.

### Progression guidée

La page « Ma collection » affiche maintenant les prochaines étapes du joueur :

1. vérifier sa collection de départ ;
2. composer sa première équipe ;
3. accéder aux combats en ligne.

Le lien proposé évolue selon la progression réelle :

- composition de la première équipe lorsque le joueur possède quatre Stickmans ;
- modification de l’équipe lorsqu’elle existe déjà ;
- accès au salon des combats lorsque l’équipe est prête.

Après la sauvegarde d’une équipe, un lien direct vers les combats en ligne est également affiché.

### Interface d’inscription

La page d’inscription est maintenant présentée en français.

Elle indique clairement que le nouveau compte recevra quatre Stickmans différents permettant de composer sa première équipe.

### Tests automatisés

Un test unitaire vérifie :

- l’attribution des quatre Stickmans ;
- leur ordre stable ;
- l’absence de doublons lors d’une seconde initialisation ;
- le refus d’un catalogue incomplet.

Un test HTTP complet reproduit le parcours réel d’un nouveau joueur :

- ouverture de la page d’inscription ;
- création du compte ;
- connexion automatique ;
- redirection vers la collection ;
- vérification des quatre Stickmans ;
- ouverture du formulaire d’équipe ;
- sauvegarde d’une équipe complète ;
- accès au salon des combats en ligne.

### Validation manuelle

Le parcours a également été vérifié manuellement avec un nouveau compte.

Résultat :

- création du compte fonctionnelle ;
- connexion automatique fonctionnelle ;
- pack de départ correctement attribué ;
- collection accessible ;
- première équipe enregistrable ;
- salon des combats accessible ;
- aucune étape bloquante constatée.

### Validation technique

Résultat final :

- **107 tests réussis** ;
- **921 assertions réussies** ;
- syntaxe PHP valide ;
- templates Twig valides ;
- conteneur Symfony valide ;
- aucune régression détectée.

### Fichiers ajoutés

- `src/Service/InitialisationNouveauJoueurService.php`
- `tests/Service/InitialisationNouveauJoueurServiceTest.php`
- `tests/Controller/ParcoursNouveauJoueurControllerTest.php`

### Fichiers modifiés

- `src/Controller/RegistrationController.php`
- `src/Controller/CollectionController.php`
- `templates/registration/register.html.twig`
- `templates/collection/index.html.twig`
- `templates/equipe/index.html.twig`
- `DEVLOG.md`

### Conclusion

Un nouveau joueur dispose maintenant immédiatement d’une équipe potentielle complète.

Le parcours inscription → collection → équipe → combats est guidé, testé automatiquement et validé manuellement, sans dépendre de tirages aléatoires pour débloquer l’accès au jeu.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J50 — Valider le parcours complet d’un combat en ligne

### Objectif

J50 vérifie qu’un combat en ligne peut parcourir tout son cycle sans laisser le joueur bloqué : préparation, rounds successifs, fin du combat, consultation du rapport et retour au salon.

### Audit du parcours

Le cycle suivant a été contrôlé :

1. création d’un combat ;
2. arrivée du second participant ;
3. soumission des plans secrets ;
4. résolution successive des rounds ;
5. mise à jour des points de vie ;
6. détection automatique de la fin ;
7. enregistrement du résultat définitif ;
8. consultation du rapport ;
9. retour au salon ;
10. présence du combat dans l’historique.

Les mécanismes de création et de jonction étaient déjà couverts par les tests du salon. J50 complète cette couverture jusqu’au résultat définitif.

### Sortie de l’écran final

L’écran de fin du combat propose maintenant deux actions explicites :

- « Voir le rapport » ;
- « Retour aux combats ».

Le bouton de rapport utilise directement l’identifiant du combat terminé.

Le retour au salon :

- arrête l’actualisation automatique ;
- oublie le combat actif local ;
- recharge les équipes disponibles ;
- recharge les combats en attente ;
- recharge l’historique du joueur.

Un combat annulé ne propose pas de rapport, car aucun affrontement n’a eu lieu.

### Test HTTP de fin complète

Un nouveau scénario automatisé reproduit un combat jusqu’à l’élimination des deux équipes.

Il vérifie notamment :

- la résolution de plusieurs rounds ;
- le passage du statut `en_cours` au statut `termine` ;
- la réponse serveur `combat_termine` ;
- la conservation du dernier numéro de round ;
- la détection d’un match nul ;
- la disparition du combat actif ;
- la persistance des deux rounds ;
- les points de vie finaux à zéro ;
- l’état éliminé des huit combattants ;
- l’accès au rapport pour les deux participants ;
- la présence du combat dans l’historique du salon.

### Validation manuelle

Un combat réel de huit rounds a été utilisé pour vérifier le parcours.

Le rapport affiche correctement :

- le résultat « Abandon » ;
- les deux participants ;
- les compositions finales ;
- les points de vie restants ;
- les statistiques de chaque Stickman ;
- le détail des huit rounds ;
- le lien de retour vers les combats.

Le joueur peut donc quitter proprement l’écran final sans devoir actualiser entièrement la page.

### Validation technique

Résultat final :

- **108 tests réussis** ;
- **973 assertions réussies** ;
- syntaxe JavaScript valide ;
- template Twig valide ;
- conteneur Symfony valide ;
- aucune régression détectée.

### Fichiers modifiés

- `assets/controllers/combat_en_ligne_controller.js`
- `assets/styles/combat_en_ligne.css`
- `templates/combat_en_ligne/index.html.twig`
- `tests/Controller/InterfaceCombatEnLigneControllerTest.php`
- `tests/Controller/ResolutionRoundCombatHttpTest.php`
- `DEVLOG.md`

### Conclusion

Le parcours principal d’un combat en ligne est maintenant couvert jusqu’à sa conclusion réelle.

Après une victoire, une défaite, un match nul ou un abandon, le joueur peut consulter immédiatement le rapport définitif puis retourner normalement au salon et à son historique.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J51 — Sécuriser les actualisations et les doubles actions

### Objectif

J51 empêche le navigateur d’envoyer plusieurs actions simultanées pendant un combat et évite qu’une actualisation automatique entre en conflit avec une action du joueur.

### Protection des actions côté navigateur

Le contrôleur JavaScript possède maintenant un état central indiquant qu’une action est en cours.

Lorsqu’une action commence :

- l’actualisation automatique est arrêtée ;
- une éventuelle lecture HTTP encore active est annulée ;
- les boutons et listes de sélection sont temporairement désactivés ;
- l’interface reçoit l’état `aria-busy` ;
- une seconde action JavaScript est immédiatement ignorée.

Cette protection concerne notamment :

- la création d’un combat ;
- la jonction d’un combat ;
- l’envoi d’un plan ;
- l’abandon ;
- l’annulation d’un combat en attente.

### Reprise automatique

À la fin de l’action :

- les interactions retrouvent leur état précédent ;
- l’état occupé de l’interface est retiré ;
- l’actualisation automatique reprend uniquement si le combat est encore en attente ou en cours.

Elle ne redémarre donc pas après une victoire, une défaite, un match nul, un abandon ou une annulation.

### Conservation des états désactivés

Le contrôleur mémorise l’état initial de chaque bouton et de chaque liste avant de les verrouiller.

Une interaction déjà désactivée avant l’action reste désactivée après celle-ci. Cela évite de réactiver accidentellement une action qui ne devrait plus être disponible.

### Protections serveur confirmées

L’audit a confirmé que le serveur possédait déjà plusieurs protections complémentaires :

- verrou d’écriture sur le combat ;
- transaction Doctrine pendant l’enregistrement ;
- unicité d’un plan par combat, joueur et round ;
- refus d’un second plan du même joueur ;
- refus des actions incompatibles avec le statut du combat.

Aucune migration Doctrine supplémentaire n’est nécessaire.

### Test de double soumission

Un nouveau test HTTP envoie deux fois le même plan pour le même joueur et le même round.

Il vérifie que :

- la première soumission est acceptée ;
- la seconde reçoit une réponse HTTP `409 Conflict` ;
- le message indique que le plan a déjà été soumis ;
- un seul plan est conservé dans la base de données.

### Validation technique

Résultat final :

- **109 tests réussis** ;
- **982 assertions réussies** ;
- syntaxe JavaScript valide ;
- syntaxe PHP valide ;
- template Twig valide ;
- conteneur Symfony valide ;
- aucune régression détectée.

### Fichiers modifiés

- `assets/controllers/combat_en_ligne_controller.js`
- `tests/Controller/CombatEnLigneControllerTest.php`
- `DEVLOG.md`

### Conclusion

Les actions du combat sont maintenant protégées à la fois dans le navigateur et sur le serveur.

Un double clic ou une actualisation automatique ne peut plus déclencher plusieurs plans ou plusieurs changements d’état simultanés.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J52 — Rendre les erreurs réseau récupérables

### Objectif

J52 évite qu’une coupure réseau ou une indisponibilité temporaire du serveur bloque définitivement l’interface des combats en ligne.

### Réalisations

- ajout d’un bouton **Réessayer** lorsqu’une erreur réseau survient ;
- nouvelle tentative automatique après 5 secondes ;
- conservation de l’actualisation automatique après une erreur temporaire ;
- affichage de messages d’erreur compréhensibles en français ;
- détection d’une connexion internet interrompue ;
- détection d’un serveur temporairement inaccessible ;
- détection d’une session expirée redirigeant vers la connexion ;
- arrêt propre des nouvelles tentatives lorsqu’une action du joueur est déjà en cours ;
- ajout d’un test vérifiant la présence et le branchement du bouton **Réessayer**.

### Fichiers modifiés

- `assets/controllers/combat_en_ligne_controller.js`
- `assets/styles/combat_en_ligne.css`
- `templates/combat_en_ligne/index.html.twig`
- `tests/Controller/InterfaceCombatEnLigneControllerTest.php`

### Validation

- syntaxe JavaScript valide ;
- syntaxe Twig valide ;
- conteneur Symfony valide ;
- suite PHPUnit complète validée ;
- **109 tests réussis** ;
- **983 assertions réussies**.

### Résultat

Une erreur réseau temporaire n’arrête plus définitivement le salon ou le combat. Le joueur reçoit un message clair, peut relancer immédiatement le chargement et bénéficie également d’une nouvelle tentative automatique.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J53 — Déclarer le match nul après trois rounds sans dégâts

### Objectif

J53 empêche un combat de continuer indéfiniment lorsque les choix des deux joueurs ne produisent aucun dégât.

### Réalisations

- détection des rounds sans aucun dégât effectif ;
- déclaration d’un match nul après 3 rounds consécutifs sans dégâts ;
- remise à zéro de la série dès qu’un dégât est infligé ;
- conservation du match nul immédiat lors d’une élimination simultanée ;
- utilisation de l’historique persistant des rounds sans nouvelle colonne en base ;
- remplacement de l’ancienne détection immédiate limitée au dernier Stickman de chaque équipe ;
- ajout d’un test HTTP complet sur trois rounds ;
- vérification du rapport final affichant le match nul.

### Fichiers modifiés

- `src/Service/DeterminationFinCombatService.php`
- `tests/Service/DeterminationFinCombatServiceTest.php`
- `tests/Controller/ResolutionRoundCombatHttpTest.php`

### Validation

- syntaxe PHP valide ;
- conteneur Symfony valide ;
- tests ciblés validés ;
- suite PHPUnit complète validée ;
- **112 tests réussis** ;
- **1044 assertions réussies**.

### Résultat

Un combat est désormais automatiquement terminé en match nul après trois rounds consécutifs sans aucun dégât effectif. Un round produisant au moins un dégât interrompt immédiatement cette série.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J54 — Déclarer un forfait après cinq minutes d’inactivité

### Objectif

J54 empêche un combat en cours de rester bloqué lorsqu’un joueur envoie son plan mais que son adversaire ne répond jamais.

### Réalisations

- démarrage d’un délai de 5 minutes dès la soumission du premier plan du round ;
- aucun chronomètre tant qu’aucun joueur n’a envoyé son plan ;
- affichage du temps restant pour les deux participants ;
- victoire automatique du joueur ayant envoyé son plan lorsque le délai expire ;
- ajout du statut distinct `forfait` ;
- distinction entre le forfait automatique et l’abandon volontaire ;
- protection concurrente de l’expiration et de la soumission du second plan ;
- ajout du forfait dans l’historique des combats ;
- ajout des résultats « Victoire par forfait » et « Défaite par forfait » ;
- prise en charge du forfait dans le rapport définitif ;
- ajout de tests unitaires et HTTP couvrant l’expiration du délai.

### Fichiers modifiés

- `assets/controllers/combat_en_ligne_controller.js`
- `assets/styles/combat_en_ligne.css`
- `src/Controller/CombatEnLigneController.php`
- `src/Controller/InterfaceCombatEnLigneController.php`
- `src/Controller/SalonCombatEnLigneController.php`
- `src/Entity/Combat.php`
- `src/Repository/CombatRepository.php`
- `src/Service/ExpirationPlanCombatEnLigneService.php`
- `templates/combat_en_ligne/rapport.html.twig`
- `tests/Controller/CombatEnLigneControllerTest.php`
- `tests/Service/ExpirationPlanCombatEnLigneServiceTest.php`

### Validation

- syntaxe PHP valide ;
- syntaxe JavaScript valide ;
- templates Twig valides ;
- conteneur Symfony valide ;
- mapping Doctrine valide ;
- base de données synchronisée ;
- test HTTP réel de l’expiration validé ;
- suite PHPUnit complète validée ;
- **117 tests réussis** ;
- **1085 assertions réussies**.

### Test manuel restant

Le comportement visuel complet sera vérifié demain avec deux comptes en laissant volontairement expirer le délai de 5 minutes.

### Résultat

Lorsqu’un seul joueur a envoyé son plan, son adversaire dispose désormais de cinq minutes pour répondre. Une fois le délai dépassé, le joueur prêt remporte automatiquement le combat par forfait.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J55 — Rejoindre un combat avec un code d’invitation

### Objectif

J55 facilite l’organisation d’un combat entre amis en permettant au créateur de partager un code d’invitation unique.

### Réalisations

- génération automatique d’un code au format `SV-XXXXXX` pour chaque nouveau combat ;
- garantie d’unicité du code dans MySQL ;
- affichage du code dans l’écran d’attente du créateur ;
- bouton permettant de copier le code dans le presse-papiers ;
- formulaire permettant de rejoindre directement un combat avec son code ;
- acceptation des codes saisis en minuscules ou avec des espaces autour ;
- validation stricte du format côté navigateur et côté serveur ;
- conservation de la sélection de l’équipe avant la jonction ;
- réutilisation des protections existantes contre les combats expirés, complets ou déjà actifs ;
- conservation de la liste classique des combats disponibles ;
- ajout d’une migration Doctrine ;
- ajout de tests couvrant la génération, l’exposition et la jonction par code.

### Fichiers modifiés

- `assets/controllers/combat_en_ligne_controller.js`
- `assets/styles/combat_en_ligne.css`
- `migrations/Version20260829130000.php`
- `src/Controller/CombatEnLigneController.php`
- `src/Controller/SalonCombatEnLigneController.php`
- `src/Entity/Combat.php`
- `src/Repository/CombatRepository.php`
- `src/Service/CreationCombatEnLigneService.php`
- `templates/combat_en_ligne/index.html.twig`
- `tests/Controller/InterfaceCombatEnLigneControllerTest.php`
- `tests/Controller/SalonCombatEnLigneControllerTest.php`
- `tests/Service/CreationCombatEnLigneServiceTest.php`

### Validation

- syntaxes PHP et JavaScript valides ;
- template Twig valide ;
- conteneur Symfony valide ;
- route de jonction par code disponible ;
- migration appliquée aux bases normale et de test ;
- mappings Doctrine valides ;
- bases synchronisées avec les entités ;
- tests ciblés validés ;
- suite PHPUnit complète validée ;
- **117 tests réussis** ;
- **1098 assertions réussies**.

### Test manuel restant

L’affichage, la copie du code et la jonction avec deux navigateurs seront vérifiés demain.

### Résultat

Un joueur peut maintenant créer un combat, copier son code d’invitation et l’envoyer directement à un ami. Celui-ci sélectionne son équipe, saisit le code et rejoint immédiatement le bon combat.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J56 — Distinguer les combats publics et privés

### Objectif

J56 permet au joueur de choisir si son nouveau combat doit être visible publiquement dans le salon ou accessible uniquement aux amis possédant son code d’invitation.

### Réalisations

- ajout d’une visibilité publique ou privée à chaque combat ;
- conservation du mode public par défaut pour les anciens parcours ;
- ajout d’une case « Combat privé » dans le salon ;
- transmission de la visibilité choisie au serveur lors de la création ;
- affichage de la visibilité dans l’écran d’attente ;
- exclusion des combats privés de la liste publique ;
- maintien des combats publics dans la liste des combats disponibles ;
- interdiction de rejoindre un combat privé directement avec son identifiant ;
- autorisation de rejoindre un combat privé uniquement avec son code d’invitation ;
- maintien des protections existantes contre les combats expirés, complets ou déjà actifs ;
- ajout d’une migration Doctrine pour persister la visibilité ;
- ajout de tests couvrant la création, le filtrage et la jonction sécurisée.

### Fichiers modifiés

- `assets/controllers/combat_en_ligne_controller.js`
- `assets/styles/combat_en_ligne.css`
- `migrations/Version20260830120000.php`
- `src/Controller/CombatEnLigneController.php`
- `src/Controller/SalonCombatEnLigneController.php`
- `src/Entity/Combat.php`
- `src/Repository/CombatRepository.php`
- `src/Service/CreationCombatEnLigneService.php`
- `src/Service/RejoindreCombatEnLigneService.php`
- `templates/combat_en_ligne/index.html.twig`
- `tests/Controller/InterfaceCombatEnLigneControllerTest.php`
- `tests/Controller/SalonCombatEnLigneControllerTest.php`
- `tests/Entity/CombatTest.php`
- `tests/Service/CreationCombatEnLigneServiceTest.php`

### Validation

- syntaxes PHP et JavaScript valides ;
- template Twig valide ;
- conteneur Symfony valide ;
- migration appliquée aux bases normale et de test ;
- mappings Doctrine valides ;
- bases synchronisées avec les entités ;
- scénario HTTP public et privé validé ;
- suite PHPUnit complète validée ;
- **119 tests réussis** ;
- **1115 assertions réussies**.

### Test manuel restant

La création d’un combat public puis privé, leur visibilité avec un second compte et la jonction par code seront vérifiées demain avec deux navigateurs.

### Résultat

Un joueur peut désormais créer un combat public ouvert au salon ou un combat privé réservé aux amis possédant son code. Le serveur empêche également de contourner cette protection avec l’identifiant du combat.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J57 — Confirmer la préparation des deux joueurs

### Objectif

J57 ajoute une phase de préparation entre l’arrivée du second joueur et le début réel du combat. Chaque participant doit confirmer que son équipe est prête avant de pouvoir envoyer son premier plan.

### Réalisations

- ajout d’un état de préparation propre à chaque participant ;
- initialisation des deux joueurs comme non prêts lorsqu’un combat est rejoint ;
- ajout d’un bouton « Je suis prêt » dans l’interface du combat ;
- affichage de l’état de préparation du joueur et de son adversaire ;
- attente automatique lorsque seul un participant a confirmé ;
- déblocage du formulaire de plan lorsque les deux joueurs sont prêts ;
- interdiction côté serveur d’envoyer un plan avant les deux confirmations ;
- ajout d’une route POST sécurisée pour confirmer la préparation ;
- protection de la confirmation avec le voter du combat et un jeton CSRF ;
- utilisation d’une transaction et d’un verrou d’écriture pour éviter les confirmations concurrentes ;
- maintien de la compatibilité avec les anciens combats déjà enregistrés ;
- ajout d’une migration Doctrine pour persister les deux confirmations ;
- ajout de tests métier, HTTP et d’interface.

### Fichiers modifiés

- `assets/controllers/combat_en_ligne_controller.js`
- `assets/styles/combat_en_ligne.css`
- `migrations/Version20260830130000.php`
- `src/Controller/CombatEnLigneController.php`
- `src/Entity/Combat.php`
- `src/Service/PreparationCombatEnLigneService.php`
- `src/Service/RejoindreCombatEnLigneService.php`
- `src/Service/SoumissionPlanCombatService.php`
- `templates/combat_en_ligne/index.html.twig`
- `tests/Controller/CombatEnLigneControllerTest.php`
- `tests/Controller/InterfaceCombatEnLigneControllerTest.php`
- `tests/Controller/SalonCombatEnLigneControllerTest.php`
- `tests/Entity/CombatTest.php`
- `tests/Service/PreparationCombatEnLigneServiceTest.php`
- `tests/Service/RejoindreCombatEnLigneServiceTest.php`
- `tests/Service/SoumissionPlanCombatServiceTest.php`

### Validation

- syntaxes PHP et JavaScript valides ;
- template Twig valide ;
- conteneur Symfony valide ;
- route de confirmation reconnue ;
- assets du combat reconnus ;
- migration appliquée aux bases normale et de test ;
- mappings Doctrine valides ;
- bases synchronisées avec les entités ;
- tests métier de préparation validés ;
- parcours HTTP avec deux confirmations validé ;
- suite PHPUnit complète validée ;
- **123 tests réussis** ;
- **1170 assertions réussies**.

### Test manuel restant

Le comportement visuel sera vérifié avec deux comptes : affichage de la préparation, confirmation séparée des joueurs, attente du second participant et apparition du formulaire de plan après les deux confirmations.

### Résultat

Un combat rejoint ne commence plus immédiatement. Chaque joueur dispose maintenant d’une véritable salle de préparation et doit confirmer qu’il est prêt avant le lancement du premier round.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J58 — Sécuriser l’attente pendant la préparation

### Objectif

J58 empêche un combat rejoint de rester bloqué indéfiniment lorsqu’un ou deux joueurs ne confirment pas leur préparation.

### Réalisations

- ajout d’un délai maximal de 5 minutes pendant la préparation ;
- démarrage du délai lorsque le second joueur rejoint le combat ;
- redémarrage du délai après la première confirmation afin de laisser 5 minutes complètes à l’adversaire ;
- annulation automatique du combat si aucun joueur ne confirme sa préparation ;
- victoire par forfait du seul joueur prêt si son adversaire ne confirme pas dans le délai ;
- affichage dynamique du temps restant dans l’interface ;
- message adapté selon que le joueur attend une victoire, risque un forfait ou attend une annulation ;
- retour automatique au salon lorsqu’une préparation sans confirmation est annulée ;
- affichage du résultat final lorsqu’un joueur gagne pendant la préparation ;
- refus des confirmations envoyées après l’expiration du délai ;
- utilisation d’une transaction et d’un verrou d’écriture pendant l’expiration ;
- protection contre les doubles confirmations qui pourraient repousser artificiellement le délai ;
- maintien de la compatibilité avec les anciens combats ne possédant pas de phase de préparation ;
- ajout de tests métier, HTTP et d’interface couvrant les différents résultats.

### Règles appliquées

- aucun joueur prêt après 5 minutes : combat annulé sans gagnant ;
- un seul joueur prêt après 5 minutes : victoire de ce joueur par forfait ;
- deux joueurs prêts : la préparation se termine normalement et les plans deviennent disponibles ;
- nouvelle confirmation du même joueur : aucune modification du délai ;
- confirmation après expiration : action refusée par le serveur.

### Fichiers modifiés

- `assets/controllers/combat_en_ligne_controller.js`
- `src/Controller/CombatEnLigneController.php`
- `src/Entity/Combat.php`
- `src/Service/ExpirationPreparationCombatEnLigneService.php`
- `src/Service/PreparationCombatEnLigneService.php`
- `tests/Controller/CombatEnLigneControllerTest.php`
- `tests/Entity/CombatTest.php`
- `tests/Service/ExpirationPreparationCombatEnLigneServiceTest.php`
- `tests/Service/PreparationCombatEnLigneServiceTest.php`

### Validation

- syntaxes PHP et JavaScript valides ;
- conteneur Symfony valide ;
- mappings Doctrine valides ;
- bases normale et de test synchronisées ;
- aucun changement de base de données nécessaire ;
- annulation automatique sans joueur prêt validée ;
- victoire par forfait du seul joueur prêt validée ;
- refus d’une confirmation tardive validé ;
- protection contre le prolongement du délai par double confirmation validée ;
- parcours HTTP d’expiration validé ;
- suite PHPUnit complète validée ;
- **131 tests réussis** ;
- **1219 assertions réussies**.

### Test manuel restant

Le chronomètre, les messages affichés aux deux joueurs, l’annulation automatique et la victoire par forfait seront vérifiés demain avec deux comptes.

### Résultat

La salle de préparation ne peut plus bloquer un combat indéfiniment. Après 5 minutes, le serveur prend automatiquement une décision juste selon les confirmations reçues.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J59 — Garantir un seul combat actif par joueur

### Objectif

J59 renforce la règle interdisant à un joueur de participer simultanément à plusieurs combats actifs, y compris lorsque plusieurs requêtes de création ou de jonction arrivent presque au même moment.

### Problème corrigé

La vérification d’un combat actif existait déjà, mais deux requêtes concurrentes pouvaient théoriquement effectuer cette vérification avant que la première transaction soit terminée.

La protection JavaScript contre les doubles actions ne suffisait pas, car les requêtes pouvaient également provenir de plusieurs onglets, navigateurs ou appareils.

### Réalisations

- ajout d’un verrou pessimiste en écriture sur le joueur ;
- verrouillage de la ligne MySQL du joueur pendant toute la transaction ;
- application du verrou avant la recherche d’un combat actif ;
- utilisation du même mécanisme pendant la création d’un combat ;
- utilisation du même mécanisme pendant la jonction d’un combat ;
- sérialisation des créations concurrentes effectuées par un même joueur ;
- sérialisation des jonctions concurrentes effectuées par un même joueur ;
- conservation du verrou existant sur le combat rejoint ;
- relecture du combat actif après l’obtention du verrou joueur ;
- refus de la seconde opération si la première a déjà engagé le joueur ;
- maintien des validations existantes concernant l’équipe, le combat et les participants ;
- aucun changement du contrat HTTP ou de l’interface ;
- aucune modification du schéma de base de données.

### Architecture

Le trajet sécurisé est désormais :

requête HTTP → contrôleur → service transactionnel → verrou du joueur → recherche du combat actif → création ou jonction → validation Doctrine → commit MySQL

Lors d’une jonction, le combat ciblé est également verrouillé avant sa modification.

### Fichiers modifiés

- `src/Repository/UserRepository.php`
- `src/Service/CreationCombatEnLigneService.php`
- `src/Service/RejoindreCombatEnLigneService.php`
- `tests/Integration/Service/CreationEtJonctionCombatMySqlTest.php`
- `tests/Service/CreationCombatEnLigneServiceTest.php`
- `tests/Service/RejoindreCombatEnLigneServiceTest.php`

### Sécurité

Le navigateur reste uniquement responsable de l’interface. Même si la protection JavaScript est contournée, le serveur et MySQL empêchent désormais le même joueur de créer ou rejoindre plusieurs combats actifs en parallèle.

### Validation

- syntaxes PHP valides ;
- conteneur Symfony valide ;
- mappings Doctrine valides ;
- bases normale et de test synchronisées ;
- aucune migration nécessaire ;
- verrou joueur vérifié dans les tests des services ;
- création transactionnelle validée ;
- jonction transactionnelle validée ;
- parcours réel MySQL sous verrou validé ;
- contrôleur du salon validé ;
- suite PHPUnit exécutée avec affichage et échec sur les notices ;
- **131 tests réussis** ;
- **1234 assertions réussies** ;
- aucune notice PHPUnit.

### Limite restante

Le fonctionnement transactionnel réel est couvert par le test d’intégration MySQL. Un test de charge utilisant plusieurs processus simultanés pourra être ajouté plus tard avant une ouverture publique plus large, mais il n’est pas indispensable pour la bêta fermée actuelle.

### Résultat

Un joueur ne peut plus être engagé dans deux combats actifs à cause de requêtes simultanées. La règle est maintenant garantie par le serveur et le verrouillage transactionnel MySQL, indépendamment de l’interface utilisée.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J60 — Rendre la résolution des rounds auto-récupérable

### Objectif

J60 empêche un combat de rester bloqué lorsque les deux plans d’un round ont bien été enregistrés, mais que la requête s’est interrompue avant la résolution.

### Problème identifié

L’enregistrement des plans et la résolution sont deux opérations successives.

Une interruption exceptionnelle du serveur entre ces opérations pouvait laisser les deux plans enregistrés sans déclencher la résolution du round.

Les joueurs restaient alors en attente malgré la présence de leurs deux plans.

### Solution mise en place

Un service dédié détecte maintenant les rounds réunissant toutes les conditions suivantes :

- le combat est en cours ;
- les deux joueurs ont confirmé leur préparation ;
- le combat possède un identifiant valide ;
- deux plans sont enregistrés pour le round courant.

Lors de l’actualisation de l’état du combat, ce service demande automatiquement la résolution du round lorsqu’elle est nécessaire.

La réponse JSON indique cette récupération avec la propriété `resolutionAutomatique`.

### Sécurité et concurrence

La récupération réutilise le service normal de résolution des rounds.

Ce service verrouille déjà le combat en écriture avant toute modification. Plusieurs actualisations simultanées ne peuvent donc pas résoudre deux fois le même round.

Après la première résolution :

- le résultat est enregistré une seule fois ;
- les points de vie sont modifiés une seule fois ;
- le combat passe au round suivant lorsqu’il continue ;
- les actualisations suivantes ne trouvent plus deux plans pour le round courant.

La contrainte d’unicité sur l’historique des résultats reste une protection supplémentaire au niveau de MySQL.

### Tests ajoutés

Un test unitaire vérifie que la récupération :

- détecte un round possédant deux plans ;
- ignore un round qui attend encore le deuxième plan ;
- ignore un combat qui n’est pas jouable.

Un test HTTP reproduit l’interruption en enregistrant directement les deux plans sans appeler la résolution.

Il vérifie ensuite que :

- la première actualisation récupère et résout le round ;
- le numéro du round progresse ;
- l’historique contient exactement un résultat ;
- les plans du nouveau round sont vides ;
- une seconde actualisation ne résout rien ;
- les points de vie ne sont pas modifiés une seconde fois.

### Validation

- syntaxe PHP valide ;
- conteneur Symfony valide ;
- mapping Doctrine valide ;
- bases de développement et de test synchronisées ;
- 135 tests réussis ;
- 1265 assertions réussies ;
- aucune notice PHPUnit ;
- aucune migration nécessaire.

### Fichiers concernés

- `src/Controller/CombatEnLigneController.php`
- `src/Service/RecuperationRoundCombatEnLigneService.php`
- `tests/Controller/CombatEnLigneControllerTest.php`
- `tests/Controller/ResolutionRoundCombatHttpTest.php`
- `tests/Service/RecuperationRoundCombatEnLigneServiceTest.php`

### Résultat

Un incident temporaire entre l’enregistrement du deuxième plan et la résolution ne peut plus bloquer définitivement le combat.

L’actualisation automatique remet désormais le round dans son déroulement normal sans dupliquer les dégâts ni l’historique.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J61 — Limiter les tentatives abusives

### Objectif

J61 protège StickVerse contre les tentatives répétées de connexion et contre l’essai automatisé de nombreux codes d’invitation.

### Protection de la connexion

La limitation native de Symfony est maintenant activée sur le formulaire de connexion.

Pour une même combinaison d’identifiant et d’adresse réseau :

- cinq échecs sont autorisés pendant une minute ;
- la tentative suivante est temporairement refusée ;
- Symfony indique au joueur qu’il doit attendre avant de recommencer ;
- une connexion réussie réinitialise normalement la limitation concernée.

Une limite globale protège également le serveur contre les tentatives réparties sur plusieurs identifiants.

### Protection des codes d’invitation

Un limiteur spécifique protège la route permettant de rejoindre un combat privé avec un code.

Chaque joueur et adresse réseau peuvent essayer dix codes valides dans leur format pendant une minute.

Après dépassement, l’API répond avec :

- le statut HTTP `429 Too Many Requests` ;
- un message JSON compréhensible ;
- le nombre de secondes avant le prochain essai ;
- l’en-tête HTTP standard `Retry-After`.

Les rafraîchissements du salon et des combats ne consomment pas cette limite.

Les créations de combats et les jonctions publiques ne sont pas concernées.

### Confidentialité

L’adresse électronique du joueur et son adresse réseau ne sont pas enregistrées directement comme clé dans le cache.

Une empreinte SHA-256 est utilisée pour identifier la limitation sans exposer ces informations dans le stockage technique.

### Dépendance Symfony

Le composant officiel `symfony/rate-limiter` en version `8.1.*` a été ajouté.

Composer a également normalisé le classement des dépendances de développement dans `composer.lock`.

Cela explique la taille importante du diff du fichier de verrouillage. Les autres dépendances n’ont pas été mises à jour par cette opération.

### Tests ajoutés

Un test unitaire vérifie que le service :

- autorise une tentative lorsqu’un jeton reste disponible ;
- retourne la date du prochain essai après dépassement ;
- utilise une clé anonymisée.

Un test HTTP du salon effectue onze essais de code :

- les dix premiers atteignent normalement la recherche du combat ;
- le onzième reçoit le statut HTTP 429 ;
- le message JSON est présent ;
- le délai est positif ;
- l’en-tête `Retry-After` correspond au délai annoncé.

Un test HTTP de sécurité effectue six connexions incorrectes :

- les cinq premiers échecs suivent le parcours normal ;
- la sixième tentative déclenche la protection Symfony.

### Validation

- syntaxes PHP et YAML valides ;
- conteneur Symfony valide ;
- mapping Doctrine valide ;
- bases de développement et de test synchronisées ;
- fichier Composer valide ;
- installation reproductible depuis `composer.lock` ;
- aucune vulnérabilité Composer signalée ;
- 139 tests réussis ;
- 1321 assertions réussies ;
- aucune notice PHPUnit ;
- aucune migration nécessaire.

### Fichiers concernés

- `composer.json`
- `composer.lock`
- `config/packages/rate_limiter.yaml`
- `config/packages/security.yaml`
- `config/reference.php`
- `src/Controller/SalonCombatEnLigneController.php`
- `src/Service/LimitationTentativesInvitationCombatService.php`
- `tests/Controller/SalonCombatEnLigneControllerTest.php`
- `tests/Controller/SecurityControllerTest.php`
- `tests/Service/LimitationTentativesInvitationCombatServiceTest.php`

### Résultat

Les tentatives normales restent fluides pour les joueurs, tandis que les répétitions anormales de mots de passe ou de codes d’invitation sont maintenant ralenties directement par le serveur.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J62 — Centraliser l’expiration des combats abandonnés

### Objectif

J62 permet au serveur de traiter les combats expirés même lorsque les joueurs ont fermé leur navigateur et qu’aucune actualisation automatique n’est encore effectuée.

### Commande d’expiration

Une nouvelle commande Symfony est disponible :

`php bin/console app:combats:expirer`

Elle recherche tous les combats encore actifs puis applique les règles d’expiration déjà sécurisées par les services métier.

### Situations prises en charge

La commande traite automatiquement :

- un combat en attente qui n’a pas été rejoint après cinq minutes ;
- une préparation abandonnée par les deux joueurs ;
- une préparation confirmée par un seul joueur ;
- un round pour lequel un seul joueur a envoyé son plan après cinq minutes.

Selon la situation, le combat est annulé ou terminé par forfait.

### Sécurité du traitement

La commande récupère uniquement les identifiants des combats actifs.

Chaque expiration est ensuite confiée au service métier correspondant :

- `ExpirationCombatEnAttenteService` ;
- `ExpirationPreparationCombatEnLigneService` ;
- `ExpirationPlanCombatEnLigneService`.

Ces services relisent le combat avec un verrou d’écriture et vérifient à nouveau toutes les conditions dans une transaction.

La commande peut donc être relancée sans modifier un combat qui n’est pas réellement expiré.

### Résumé d’exécution

À la fin du nettoyage, la commande indique :

- le nombre de combats examinés ;
- le nombre d’attentes annulées ;
- le nombre de préparations annulées ;
- le nombre de forfaits pendant la préparation ;
- le nombre de forfaits provoqués par un plan manquant.

### Automatisation future

La commande est prête à être exécutée régulièrement par le serveur.

Sa programmation automatique avec une tâche planifiée sera configurée pendant la phase de déploiement.

J62 ne lance donc pas encore un processus permanent en arrière-plan sur l’ordinateur de développement.

### Tests ajoutés

Un test d’intégration exécute réellement la commande avec cinq combats :

- une attente expirée ;
- une attente encore valide ;
- une préparation expirée sans joueur prêt ;
- une préparation expirée avec un seul joueur prêt ;
- un round expiré avec un seul plan envoyé.

Le test vérifie que :

- les quatre combats expirés reçoivent le bon résultat ;
- le bon gagnant est enregistré pour chaque forfait ;
- le combat encore valide reste actif ;
- le résumé de la commande contient les bons compteurs.

### Validation

- syntaxes PHP valides ;
- conteneur Symfony valide ;
- commande correctement enregistrée ;
- mapping Doctrine valide ;
- bases de développement et de test synchronisées ;
- 140 tests réussis ;
- 1 335 assertions réussies ;
- aucune notice PHPUnit ;
- aucune migration nécessaire.

### Fichiers concernés

- `src/Command/ExpirerCombatsEnLigneCommand.php`
- `src/Repository/CombatRepository.php`
- `src/Service/NettoyageCombatsExpiresService.php`
- `tests/Command/ExpirerCombatsEnLigneCommandTest.php`

### Résultat

StickVerse dispose maintenant d’un point d’entrée serveur unique pour nettoyer les combats abandonnés, indépendamment des actualisations effectuées par les navigateurs des joueurs.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J63 — Empêcher la mise en cache des combats en ligne

### Objectif

J63 empêche le navigateur ou un intermédiaire réseau de conserver puis de renvoyer une ancienne réponse du salon ou d’un combat en ligne.

Les réponses concernées contiennent des informations qui évoluent rapidement :

- le statut du combat ;
- le numéro du round ;
- les points de vie ;
- la préparation des joueurs ;
- la soumission des plans ;
- les jetons CSRF.

### Architecture choisie

Un abonné aux événements Symfony intervient après le contrôleur et avant l’envoi de la réponse au navigateur.

Cette protection reste centralisée et évite de répéter les mêmes en-têtes dans chaque contrôleur.

Le moteur de combat et les règles métier ne sont pas modifiés.

### Routes protégées

La protection concerne :

- l’API du salon ;
- l’API des combats ;
- l’interface principale des combats ;
- les rapports définitifs.

Les autres pages et les sous-requêtes internes de Symfony restent inchangées.

### En-têtes appliqués

Les réponses sont déclarées privées et non stockables avec :

- `private` ;
- `no-store` ;
- `no-cache` ;
- `must-revalidate` ;
- une durée maximale de zéro ;
- une date d’expiration passée ;
- `Pragma: no-cache`.

Chaque polling doit ainsi récupérer l’état actuel fourni par le serveur.

### Sécurité

Cette protection empêche également la conservation des jetons CSRF contenus dans les réponses JSON.

Une réponse appartenant à un joueur connecté ne doit pas être réutilisée depuis un cache partagé ou pour un autre utilisateur.

### Tests ajoutés

Les tests vérifient :

- les en-têtes des routes du combat ;
- les en-têtes du salon ;
- les en-têtes de l’interface ;
- les en-têtes des rapports ;
- l’absence de modification des autres routes ;
- l’absence de modification des sous-requêtes ;
- le branchement réel dans les contrôleurs HTTP existants.

### Validation

- syntaxes PHP valides ;
- conteneur Symfony valide ;
- mapping Doctrine valide ;
- bases de développement et de test synchronisées ;
- 146 tests réussis ;
- 1 379 assertions réussies ;
- aucune notice PHPUnit ;
- aucune migration nécessaire.

### Fichiers concernés

- `src/EventSubscriber/EntetesCacheCombatEnLigneSubscriber.php`
- `tests/EventSubscriber/EntetesCacheCombatEnLigneSubscriberTest.php`
- `tests/Controller/CombatEnLigneControllerTest.php`
- `tests/Controller/InterfaceCombatEnLigneControllerTest.php`
- `tests/Controller/SalonCombatEnLigneControllerTest.php`

### Résultat

Le salon, les combats et leurs rapports utilisent maintenant des réponses explicitement non stockables. Les joueurs ne risquent plus de recevoir un ancien état conservé dans un cache.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J64 — Créer la navigation principale de StickVerse

### Objectif

J64 met en place une navigation globale simple permettant d’accéder aux principales fonctionnalités déjà présentes dans StickVerse.

Cette première version assume volontairement une apparence de prototype : fond clair, très peu de couleurs, presque aucune décoration et une structure facile à modifier lors de la future phase de design.

### Navigation publique

Un visiteur non connecté peut maintenant accéder directement aux pages suivantes :

- accueil ;
- wiki ;
- caisses ;
- connexion ;
- inscription.

Les fonctionnalités réservées aux joueurs et à l’administration ne sont pas affichées.

### Navigation du joueur

Après connexion, le menu principal donne accès aux fonctionnalités essentielles du jeu :

- accueil ;
- wiki ;
- caisses ;
- collection ;
- équipe ;
- combats en ligne ;
- déconnexion.

L’adresse électronique du compte connecté est également affichée dans l’en-tête.

La page actuellement consultée est indiquée simplement dans le menu.

### Navigation de l’administration

Les comptes possédant le rôle administrateur disposent de liens supplémentaires vers :

- la gestion des Stickmans ;
- la gestion des caisses ;
- la gestion des associations entre les caisses et les Stickmans.

Ces liens ne sont pas affichés aux visiteurs ni aux joueurs ordinaires.

### Structure commune

La navigation est intégrée au gabarit principal de l’application.

Toutes les pages qui utilisent ce gabarit bénéficient donc automatiquement du même menu sans devoir recopier sa structure dans chaque template.

L’ancien en-tête spécifique à l’interface des combats en ligne a été retiré afin d’éviter l’affichage de deux navigations différentes.

### Apparence volontairement simple

Le style global utilise uniquement :

- un fond gris clair ;
- un en-tête blanc ;
- des bordures grises simples ;
- une police standard ;
- des liens présentés comme de petits boutons basiques ;
- une mise en page sans animation ni effet graphique complexe.

Cette apparence minimale permet de conserver l’esprit d’un prototype fonctionnel avant la future création de l’identité visuelle définitive de StickVerse.

### Adaptation aux petits écrans

Le menu peut revenir automatiquement sur plusieurs lignes lorsque la largeur disponible diminue.

Sur téléphone, les différentes parties de l’en-tête occupent toute la largeur afin que les liens restent accessibles, sans introduire pour le moment de menu hamburger ou de JavaScript supplémentaire.

### Tests automatisés

Les tests vérifient notamment :

- la navigation visible pour un visiteur anonyme ;
- l’absence des pages privées pour un visiteur ;
- les liens disponibles pour un joueur connecté ;
- l’indication de la page active ;
- l’absence du menu d’administration pour un joueur normal ;
- l’apparition des outils d’administration pour un administrateur ;
- la présence du lien de déconnexion ;
- l’absence de duplication de l’ancien en-tête des combats.

La validation complète du projet donne :

- 148 tests réussis ;
- 1 402 assertions ;
- aucune erreur de syntaxe Twig ;
- conteneur Symfony valide ;
- schémas Doctrine de développement et de test synchronisés ;
- ressources CSS correctement enregistrées.

### Validation manuelle

La navigation a été vérifiée dans le navigateur avec le serveur local.

Les menus s’affichent correctement et les liens permettent d’accéder aux différentes parties déjà disponibles dans StickVerse.

### Résultat

StickVerse possède maintenant une navigation centrale cohérente reliant ses principales fonctionnalités.

Cette base reste volontairement rudimentaire, mais elle permettra de construire progressivement tous les menus du jeu avant de travailler sur leur design définitif pour ordinateur et téléphone.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J65 — Créer le tableau de bord principal du joueur

### Objectif

J65 remplace l’ancienne page d’accueil en construction par un tableau de bord fonctionnel regroupant les informations et les raccourcis utiles au joueur.

L’interface conserve volontairement l’apparence simple du prototype mise en place pendant J64.

### Accueil des visiteurs

Un visiteur qui n’est pas connecté voit maintenant :

- une présentation courte de StickVerse ;
- un lien pour créer un compte ;
- un lien pour se connecter ;
- un accès au wiki ;
- un accès à la liste des caisses.

Aucune information privée liée aux joueurs n’est chargée ou affichée.

### Tableau de bord du joueur

Lorsqu’un joueur est connecté, la page d’accueil devient son tableau de bord personnel.

Elle affiche notamment :

- l’adresse électronique du compte ;
- le nombre de Stickmans différents présents dans sa collection ;
- le nom de son équipe enregistrée ;
- un message lorsqu’aucune équipe n’est encore disponible ;
- son éventuel combat actif ;
- un bouton permettant de reprendre ce combat ;
- un bouton permettant de chercher un combat lorsqu’aucun combat n’est actif ;
- ses trois derniers combats terminés ;
- un accès au rapport détaillé de chaque ancien combat.

### Raccourcis

Le tableau de bord propose également des accès rapides vers :

- la collection ;
- la gestion de l’équipe ;
- les caisses ;
- le wiki ;
- les combats en ligne.

Le joueur peut ainsi retrouver les fonctionnalités essentielles depuis une seule page.

### Service de tableau de bord

La récupération des informations personnelles est centralisée dans le service `TableauDeBordJoueurService`.

Ce service rassemble :

- le nombre de Stickmans différents du joueur ;
- son équipe enregistrée ;
- son combat actuellement actif ;
- ses trois derniers combats terminés.

Le contrôleur de la page d’accueil reste ainsi limité à l’identification de l’utilisateur et à l’affichage du résultat.

### Apparence du prototype

Le tableau de bord utilise uniquement :

- des sections blanches ;
- des bordures grises simples ;
- une grille adaptable ;
- des titres et des liens standards ;
- aucun effet graphique complexe ;
- aucune animation.

Cette présentation reste volontairement rudimentaire afin de terminer les fonctionnalités et les différents menus avant la future phase de design.

### Adaptation aux écrans

Les blocs du tableau de bord utilisent une grille simple qui s’adapte automatiquement à la largeur disponible.

Les différentes cartes peuvent passer sur plusieurs lignes lorsque l’écran devient plus petit, ce qui permet déjà de consulter la page sur téléphone sans créer un design définitif.

### Tests automatisés

Les tests vérifient notamment :

- l’affichage de la présentation publique pour un visiteur ;
- l’absence du tableau de bord personnel pour un visiteur ;
- l’affichage du tableau de bord pour un joueur connecté ;
- le nombre de Stickmans possédés ;
- l’état de l’équipe ;
- l’absence ou la présence d’un combat actif ;
- l’historique récent des combats ;
- les raccourcis vers les principales fonctionnalités ;
- la construction des données par le service dédié.

La validation complète du projet donne :

- 150 tests réussis ;
- 1 435 assertions ;
- aucune erreur PHP ;
- template Twig valide ;
- conteneur Symfony valide ;
- schémas Doctrine de développement et de test synchronisés ;
- aucun avertissement PHPUnit.

### Validation manuelle

Le tableau de bord a été testé avec trois comptes différents, dont un compte administrateur.

Pour chaque compte :

- le nombre de Stickmans affiché correspond à la collection ;
- l’équipe affichée correspond aux données enregistrées ;
- l’état du combat actif est correct ;
- l’historique correspond aux combats du joueur ;
- les différents liens fonctionnent ;
- le menu d’administration reste réservé au compte administrateur.

### Résultat

Chaque joueur dispose maintenant d’une page d’accueil personnelle et fonctionnelle.

Le tableau de bord rassemble les informations essentielles du compte sans introduire prématurément le futur design définitif de StickVerse.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J66 — Créer le profil du joueur

### Objectif

J66 ajoute une page personnelle permettant au joueur de consulter les informations principales de son compte et sa progression dans StickVerse.

Cette page reste volontairement simple afin de conserver l’apparence prototype utilisée pour les premiers menus du jeu.

### Accès au profil

Un lien `Profil` est maintenant présent dans la zone du compte lorsque l’utilisateur est connecté.

Ce lien conduit vers la nouvelle adresse :

`/profil`

Le lien actif est indiqué dans le menu lorsque le joueur consulte cette page.

Un visiteur non connecté ne voit pas ce lien et ne peut pas accéder directement au profil. Il est redirigé vers la page de connexion.

### Informations du compte

La première section du profil affiche :

- l’adresse électronique du joueur ;
- le type du compte ;
- la mention `Joueur` pour un compte normal ;
- la mention `Administrateur` pour un compte possédant le rôle correspondant.

Les outils d’administration restent visibles uniquement pour les administrateurs.

### Progression du joueur

Le profil indique également :

- le nombre de Stickmans différents présents dans la collection ;
- le nom de l’équipe enregistrée ;
- un message lorsqu’aucune équipe n’est encore disponible ;
- un lien vers la collection ;
- un lien vers la gestion de l’équipe.

### Statistiques des combats

Une nouvelle méthode du dépôt des combats calcule les statistiques définitives du joueur.

Le profil affiche :

- le nombre total de combats joués ;
- le nombre de victoires ;
- le nombre de défaites ;
- le nombre de matchs nuls.

Les combats encore actifs et les combats annulés ne sont pas comptabilisés.

Les victoires obtenues après un abandon ou un forfait sont correctement prises en compte lorsque le joueur est enregistré comme gagnant.

### Service du profil

La construction des informations est centralisée dans le service `ProfilJoueurService`.

Il rassemble :

- le nombre de Stickmans différents ;
- l’équipe du joueur ;
- les statistiques de ses combats.

Le contrôleur reste limité à la vérification de l’utilisateur connecté et à l’affichage du profil.

### Sécurité du compte

Une section informe le joueur que la modification et la récupération du mot de passe seront ajoutées pendant la prochaine étape du développement.

Ces fonctionnalités restent volontairement séparées de J66 afin de ne pas mélanger l’affichage du profil avec la gestion sensible des identifiants.

### Apparence du prototype

La page utilise uniquement :

- des sections blanches ;
- des bordures grises ;
- des listes simples ;
- une disposition adaptable pour les statistiques ;
- aucun effet graphique complexe ;
- aucune animation.

La page reste ainsi fonctionnelle sur ordinateur et sur petit écran sans anticiper le futur design définitif.

### Tests automatisés

Les tests vérifient notamment :

- la redirection d’un visiteur vers la connexion ;
- l’affichage du profil pour un joueur ;
- l’adresse électronique affichée ;
- le type du compte ;
- le calcul des combats joués ;
- le calcul des victoires ;
- le calcul des défaites ;
- le calcul des matchs nuls ;
- l’identification d’un administrateur ;
- l’absence du lien Profil pour un visiteur ;
- l’état actif du lien Profil ;
- la construction des informations par le service dédié.

La validation complète du projet donne :

- 154 tests réussis ;
- 1 471 assertions ;
- aucune erreur PHP ;
- templates Twig valides ;
- conteneur Symfony valide ;
- schémas Doctrine de développement et de test synchronisés ;
- aucun avertissement PHPUnit.

### Validation manuelle

La page Profil a été vérifiée depuis le navigateur avec plusieurs comptes.

Les informations du compte, la collection, l’équipe et les statistiques correspondent aux données réellement enregistrées.

Le compte administrateur est correctement identifié et les liens du profil fonctionnent.

### Résultat

Chaque joueur possède maintenant un espace personnel simple permettant de consulter son identité, sa progression et ses résultats.

Cette base pourra accueillir pendant J67 les fonctionnalités de modification et de récupération du mot de passe.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J67 — Gérer la modification et la récupération du mot de passe

### Objectif

J67 permet au joueur de modifier son mot de passe depuis son profil et prépare une procédure complète de récupération lorsqu’il ne peut plus se connecter.

### Modification du mot de passe

Une nouvelle page est accessible depuis le profil à l’adresse :

`/profil/mot-de-passe`

Le joueur doit fournir :

- son mot de passe actuel ;
- son nouveau mot de passe ;
- la confirmation du nouveau mot de passe.

Le changement est refusé lorsque le mot de passe actuel est incorrect ou lorsque le nouveau mot de passe est identique à l’ancien.

Après une modification réussie, le joueur peut se reconnecter avec son nouveau mot de passe.

### Mot de passe oublié

La page de connexion contient maintenant un lien `Mot de passe oublié ?`.

La page de récupération est accessible à l’adresse :

`/mot-de-passe/oublie`

Le formulaire affiche toujours le même message de confirmation, que l’adresse saisie existe ou non. Un visiteur ne peut donc pas utiliser cette page pour découvrir les comptes enregistrés.

### Liens de réinitialisation

Lorsqu’un compte correspond à l’adresse saisie :

- un jeton aléatoire unique est créé ;
- seule son empreinte sécurisée est enregistrée dans la base ;
- les anciennes demandes du joueur sont supprimées ;
- un lien personnel de réinitialisation est préparé ;
- le lien expire après une heure ;
- le lien ne peut être utilisé qu’une seule fois.

Un lien expiré, inconnu ou déjà utilisé affiche une page d’erreur et ne permet aucune modification.

### Envoi des e-mails

Le service de récupération prépare un e-mail contenant le lien personnel de réinitialisation.

L’envoi réel reste désactivé dans l’environnement local grâce au transport nul. Les paramètres `MAILER_DSN` et `MAILER_FROM` permettront de brancher le véritable service d’envoi pendant l’hébergement.

La réception, le dossier spam et la configuration du domaine expéditeur seront vérifiés avec de vraies adresses avant l’ouverture de la bêta fermée.

### Stockage des demandes

Une nouvelle entité `ReinitialisationMotDePasse` conserve :

- le joueur concerné ;
- l’empreinte du jeton ;
- la date de création ;
- la date d’expiration ;
- la date d’utilisation éventuelle.

Une migration Doctrine crée la table correspondante et sa relation avec les utilisateurs.

### Architecture

La logique est séparée entre :

- `ModificationMotDePasseService` pour le changement depuis le profil ;
- `RecuperationMotDePasseService` pour les demandes et les réinitialisations ;
- `ReinitialisationMotDePasseRepository` pour retrouver un jeton encore valide ;
- `MotDePasseController` pour les formulaires et les réponses HTTP.

Les contrôleurs restent ainsi limités à la gestion des pages et délèguent les règles sensibles aux services.

### Sécurité

J67 garantit notamment :

- la vérification du mot de passe actuel ;
- un minimum de huit caractères pour le nouveau mot de passe ;
- le hachage des mots de passe ;
- le stockage exclusif de l’empreinte des jetons ;
- une durée de validité limitée ;
- une seule utilisation par lien ;
- l’absence de révélation des adresses enregistrées ;
- la protection CSRF des formulaires.

### Tests automatisés

Les tests vérifient notamment :

- la modification avec le bon mot de passe actuel ;
- le refus d’un mot de passe actuel incorrect ;
- la création d’une demande de récupération ;
- la préparation et le contenu de l’e-mail ;
- l’absence d’e-mail pour une adresse inconnue ;
- le message identique pour une adresse connue ou inconnue ;
- le hachage du jeton ;
- l’expiration après une heure ;
- la réinitialisation effective du mot de passe ;
- l’impossibilité de réutiliser un lien.

La validation complète du projet donne :

- 162 tests réussis ;
- 1 529 assertions ;
- aucune erreur PHP ;
- templates Twig valides ;
- conteneur Symfony valide ;
- schémas Doctrine de développement et de test synchronisés ;
- aucun avertissement PHPUnit.

### Validation manuelle

La modification du mot de passe a été testée depuis le profil.

Le joueur peut se déconnecter puis se reconnecter avec son nouveau mot de passe.

La page de demande de récupération et son message de confirmation fonctionnent correctement.

La réception d’un véritable e-mail sera validée pendant la préparation de l’hébergement.

### Résultat

Le joueur peut maintenant gérer son mot de passe sans intervention d’un administrateur.

StickVerse possède également toute la base nécessaire pour envoyer de véritables liens de récupération sécurisés dès que le service de messagerie de production sera configuré.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J68 — Ajouter les pseudos publics des joueurs

### Objectif

J68 ajoute une identité publique distincte de l’adresse électronique afin que les joueurs puissent se reconnaître sans exposer leurs informations de connexion.

### Pseudo du joueur

Chaque compte possède maintenant un pseudo public unique.

Le pseudo doit contenir :

- entre 3 et 24 caractères ;
- uniquement des lettres ;
- des chiffres ;
- des tirets ;
- des tirets bas.

Deux joueurs ne peuvent pas utiliser le même pseudo.

### Comptes existants

La migration attribue automatiquement un pseudo temporaire aux comptes déjà enregistrés.

Le format utilisé est :

`Joueur-ID`

Chaque joueur peut ensuite choisir son propre pseudo depuis son profil.

### Inscription

Le formulaire d’inscription demande maintenant :

- un pseudo public ;
- une adresse électronique privée ;
- un mot de passe ;
- l’acceptation des conditions d’utilisation.

L’inscription est refusée lorsque le pseudo est invalide ou déjà utilisé.

### Modification depuis le profil

Une nouvelle page est accessible à l’adresse :

`/profil/pseudo`

Le joueur peut y remplacer son pseudo actuel.

La modification est refusée lorsque :

- le nouveau pseudo est identique au pseudo actuel ;
- le format est invalide ;
- le pseudo appartient déjà à un autre joueur.

Après une modification réussie, le nouveau pseudo apparaît immédiatement sur le profil et dans la navigation.

### Confidentialité des adresses

Les adresses électroniques ne sont plus utilisées comme noms publics.

Elles ont été remplacées par les pseudos dans :

- la navigation du joueur ;
- le tableau de bord ;
- le salon des combats ;
- la liste des combats disponibles ;
- le combat actif ;
- l’historique des combats ;
- les rapports définitifs ;
- les noms des deux participants.

L’adresse électronique reste visible uniquement par son propriétaire dans son profil et dans les pages nécessaires à la connexion ou à la récupération du mot de passe.

### Données des combats

Les réponses du serveur utilisent maintenant :

- `pseudo` pour identifier un participant ;
- `joueur1Pseudo` pour le créateur d’un combat disponible ;
- `adversairePseudo` pour l’historique.

Les anciennes informations publiques contenant les adresses électroniques ne sont plus envoyées au navigateur.

### Migration Doctrine

Une nouvelle migration ajoute le champ `pseudo` à chaque utilisateur.

Elle :

- attribue un pseudo temporaire aux comptes existants ;
- rend le pseudo obligatoire ;
- ajoute un index unique ;
- empêche les doublons directement dans la base de données.

Les bases de développement et de test ont été migrées avec succès.

### Architecture

La modification du pseudo est séparée entre :

- `ModifierPseudoType` pour les règles du formulaire ;
- `ModificationPseudoService` pour les règles métier ;
- `PseudoController` pour la page et les réponses HTTP ;
- l’entité `User` pour le stockage du pseudo.

Le contrôleur reste limité à la gestion du formulaire et délègue les vérifications au service.

### Apparence du prototype

La nouvelle page conserve volontairement le style simple du prototype :

- fond clair ;
- formulaire classique ;
- aucun effet complexe ;
- aucune animation ;
- fonctionnement adapté aux petits écrans.

### Tests automatisés

Les tests vérifient notamment :

- la présence du pseudo à l’inscription ;
- le refus d’un pseudo déjà utilisé ;
- l’attribution et le stockage du pseudo ;
- la modification depuis le profil ;
- le refus du pseudo actuel ;
- le refus du pseudo d’un autre joueur ;
- la protection de la page pour les visiteurs ;
- l’affichage du pseudo dans la navigation ;
- l’affichage des pseudos pendant les combats ;
- l’affichage des pseudos dans les rapports ;
- l’absence des adresses électroniques dans les données publiques.

La validation complète du projet donne :

- 169 tests réussis ;
- 1 576 assertions ;
- JavaScript valide ;
- aucune erreur PHP ;
- templates Twig valides ;
- conteneur Symfony valide ;
- routes Symfony valides ;
- schémas Doctrine de développement et de test synchronisés ;
- aucun avertissement PHPUnit.

### Validation manuelle

Les pseudos temporaires des comptes existants ont été vérifiés.

La modification depuis le profil fonctionne correctement.

Un pseudo déjà utilisé par un autre compte est refusé.

Les pseudos apparaissent dans les menus, les combats et les rapports à la place des adresses électroniques.

### Résultat

Chaque joueur possède maintenant une identité publique propre à StickVerse.

Les adresses utilisées pour se connecter restent privées et ne sont plus communiquées aux adversaires.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J69 — Ajouter le portefeuille de pièces et le paiement des caisses

### Objectif

J69 introduit une monnaie virtuelle permettant aux joueurs d’acheter réellement les caisses disponibles dans la boutique.

### Fonctionnalités ajoutées

- Chaque joueur possède désormais un portefeuille de pièces.
- Les nouveaux joueurs commencent avec 1 000 pièces.
- Les comptes existants reçoivent également 1 000 pièces grâce à la migration.
- Le solde est visible dans la navigation, le tableau de bord, le profil et la page des caisses.
- Le prix enregistré dans l’administration est maintenant réellement prélevé lors de l’ouverture d’une caisse.
- Le bouton d’ouverture est désactivé lorsque le joueur ne possède pas assez de pièces.
- Le serveur vérifie également le solde afin d’empêcher tout contournement depuis le navigateur.
- Une caisse vide ou invalide ne prélève aucune pièce.
- Après une ouverture réussie, le Stickman obtenu est ajouté à la collection ou augmente la quantité déjà possédée.
- Le nouveau solde est affiché après l’achat.

### Sécurisation

Le paiement et l’ajout du Stickman sont effectués dans une seule opération protégée.

Le joueur est verrouillé pendant l’achat afin d’empêcher deux ouvertures simultanées de dépenser les mêmes pièces.

Si le paiement ou l’ajout du Stickman échoue, aucune modification partielle n’est conservée.

### Vérifications automatiques

- Syntaxe PHP valide.
- Templates Twig valides.
- Conteneur Symfony valide.
- Schémas des bases de développement et de test synchronisés.
- Migration appliquée correctement.
- Ouverture d’une caisse avec un solde suffisant testée.
- Refus d’ouverture avec un solde insuffisant testé.
- Absence de prélèvement pour une caisse vide testée.
- Ouvertures successives et augmentation de quantité testées.
- Gestion du portefeuille testée au niveau de l’entité.
- Suite complète validée : 178 tests et 1 638 assertions.

### Vérification manuelle

Le solde apparaît correctement sur les différentes pages.

L’ouverture de la Caisse Origine prélève bien son prix et fait passer le solde de 1 000 à 880 pièces.

Le Stickman obtenu est correctement ajouté à la collection ou augmente la quantité déjà possédée.

### Résultat

StickVerse possède maintenant une première économie fonctionnelle. Les prix des caisses ne sont plus seulement décoratifs et les achats sont protégés contre les doubles dépenses.

La prochaine étape pourra attribuer des récompenses en pièces à la fin des combats.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J70 — Récompenser les joueurs après les combats

### Objectif

J70 introduit les récompenses en pièces virtuelles à la fin des combats en ligne.

### Barème

- Victoire classique : 100 pièces.
- Défaite classique : 25 pièces de participation.
- Match nul : 50 pièces pour chaque joueur.
- Victoire par abandon ou forfait : 100 pièces pour le gagnant.
- Joueur ayant abandonné ou déclaré forfait : aucune récompense.

### Sécurisation

- Une récompense ne peut être attribuée qu’une seule fois.
- Le combat conserve un indicateur de distribution afin d’éviter les doubles gains après actualisation.
- Les fins normales, abandons et forfaits utilisent le même service de récompense.
- Les expirations traitées automatiquement par le serveur attribuent également les gains prévus.
- Les combats annulés ne donnent aucune pièce.
- Les états incohérents, comme un forfait sans gagnant, sont refusés.

### Interface

- Le gain est affiché dans la zone de fin du combat.
- Le gain apparaît également dans le rapport définitif.
- Les réponses JSON du combat indiquent le nombre de pièces gagnées.
- Le portefeuille est mis à jour automatiquement après la fin du combat.

### Base de données

Une migration ajoute le suivi de distribution des récompenses sur chaque combat.

### Vérifications automatiques

- Calcul des récompenses de victoire, défaite et match nul.
- Récompense des forfaits et abandons.
- Protection contre une deuxième attribution.
- Absence de gain pour un combat actif ou annulé.
- Affichage des récompenses dans l’interface et les rapports.
- Suite complète validée : 184 tests et 1 659 assertions.
- Schéma Doctrine synchronisé.
- Conteneur Symfony valide.
- Syntaxe JavaScript et Twig validée.

### Résultat

Les combats en ligne alimentent maintenant l’économie du jeu. Chaque résultat donne une récompense cohérente, visible par le joueur et protégée contre les doublons.

La prochaine étape pourra ajouter un historique des mouvements de pièces afin de détailler les gains et les dépenses du joueur.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J71 — Ajouter l’historique des mouvements de pièces

### Objectif

J71 permet au joueur de consulter l’origine et l’utilisation de ses pièces virtuelles.

### Fonctionnalités ajoutées

- Création d’une entité dédiée aux mouvements de pièces.
- Chaque mouvement conserve :
  - le joueur concerné ;
  - le montant signé ;
  - le type d’opération ;
  - un libellé lisible ;
  - la date de création.
- Les dépenses sont enregistrées avec un montant négatif.
- Les récompenses sont enregistrées avec un montant positif.
- Les mouvements nuls sont refusés.
- Les types d’opération inconnus sont refusés.
- Les mouvements sont automatiquement supprimés avec le compte du joueur.

### Opérations enregistrées

- Ouverture d’une caisse :
  - montant négatif ;
  - nom de la caisse indiqué dans le libellé.
- Récompense de combat :
  - montant positif ;
  - numéro du combat indiqué lorsque celui-ci est disponible.

### Profil du joueur

Une nouvelle section affiche les derniers mouvements de pièces :

- date ;
- libellé de l’opération ;
- montant gagné ou dépensé.

Les mouvements sont triés du plus récent au plus ancien et limités aux dernières opérations utiles.

### Sécurisation

L’écriture du mouvement est réalisée dans la même transaction que la dépense ou la récompense.

Une ouverture de caisse échouée ne crée aucun mouvement.

Une récompense déjà distribuée ne crée aucun mouvement supplémentaire.

### Base de données

Une migration crée la table `mouvement_pieces` avec une relation obligatoire vers le joueur et une suppression automatique des mouvements lors de la suppression du compte.

### Vérifications automatiques

- Entité et validation des types de mouvements.
- Enregistrement Doctrine d’un mouvement.
- Historique d’achat d’une caisse.
- Historique de récompense de combat.
- Affichage des mouvements sur le profil.
- Vérification des montants signés.
- Schéma Doctrine synchronisé.
- Conteneur Symfony valide.
- Suite complète validée : 189 tests et 1 679 assertions.

### Résultat

Le joueur peut maintenant comprendre précisément comment son solde évolue. Les achats de caisses et les récompenses de combats sont conservés dans un historique simple et vérifiable.

La prochaine étape pourra ajouter des récompenses quotidiennes ou des objectifs donnant des pièces supplémentaires.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J72 — Ajouter la récompense quotidienne

### Objectif

J72 donne au joueur une petite source régulière de pièces afin d’encourager les retours quotidiens dans StickVerse.

### Fonctionnalités ajoutées

- Une récompense quotidienne de 25 pièces.
- Une seule réclamation possible par jour calendaire.
- Une protection transactionnelle avec verrou d’écriture sur le joueur.
- Les doubles clics et requêtes simultanées ne créditent pas deux fois la récompense.
- Le joueur peut réclamer la récompense depuis son profil.
- Le profil indique si la récompense du jour est déjà récupérée.

### Historique des pièces

Chaque récompense quotidienne crée un mouvement positif de type `recompense_quotidienne` avec le libellé « Récompense quotidienne ».

L’historique distingue maintenant les achats de caisses, les récompenses de combats et les récompenses quotidiennes.

### Base de données

Une migration ajoute la date de dernière récompense quotidienne sur le compte joueur.

### Vérifications automatiques

- Réclamation une fois par jour.
- Nouvelle réclamation autorisée le jour suivant.
- Enregistrement du mouvement positif.
- Affichage du formulaire sur le profil.
- Protection CSRF et accès réservé aux joueurs connectés.
- Schéma Doctrine synchronisé.
- Conteneur Symfony valide.
- Suite complète validée : 195 tests et 1 701 assertions.

### Résultat

Le joueur dispose maintenant d’une récompense quotidienne simple, sécurisée et visible dans son historique de pièces.

La prochaine étape pourra ajouter des objectifs de progression donnant également des pièces.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J73 — Ajouter les objectifs de progression

### Objectif

J73 donne au joueur des objectifs simples à réaliser afin de suivre sa progression et de gagner quelques pièces supplémentaires.

### Objectifs ajoutés

- Premier combat : participer à un combat, récompense de 50 pièces.
- Habitué des combats : participer à cinq combats, récompense de 100 pièces.
- Première ouverture : ouvrir une première caisse payante, récompense de 50 pièces.

La progression est calculée à partir des combats terminés ou abandonnés et de l’historique des achats de caisses.

### Réclamation

- Chaque objectif ne peut être réclamé qu’une seule fois.
- Un objectif n’est réclamable que lorsque sa cible est atteinte.
- La réclamation est protégée par un verrou d’écriture et une transaction.
- Les requêtes simultanées ne peuvent pas créditer deux fois la même récompense.
- Une récompense réclamée est mémorisée sur le compte joueur.

### Historique des pièces

Chaque objectif validé crée un mouvement positif de type `recompense_objectif` avec un libellé explicite.

### Interface

Le profil affiche les objectifs, leur progression, leur récompense et leur état.

Un bouton permet de réclamer directement chaque objectif disponible.

### Base de données

Une migration ajoute la liste JSON des objectifs déjà réclamés sur le compte joueur.

### Vérifications automatiques

- Calcul des progressions.
- Réclamation d’un objectif disponible.
- Refus d’un objectif non disponible.
- Enregistrement du mouvement positif.
- Affichage et réclamation depuis le profil.
- Protection CSRF et accès réservé aux joueurs connectés.
- Schéma Doctrine synchronisé.
- Conteneur Symfony valide.
- Suite complète validée : 199 tests et 1 722 assertions.

### Résultat

Le joueur dispose maintenant de premiers objectifs clairs et de récompenses supplémentaires liées à sa progression.

La prochaine étape pourra ajouter d’autres objectifs liés à la collection, à l’équipe ou aux combats en ligne.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J74 — Étendre les objectifs à la collection et à l’équipe

### Objectif

J74 complète le système d’objectifs avec des étapes liées à la collection de Stickmen et à la préparation d’une équipe.

### Fonctionnalités ajoutées

- Ajout de l’objectif « Collection en route ».
- Ajout de l’objectif « Équipe prête ».
- Conservation des objectifs du J73 :
  - Premier combat ;
  - Habitué des combats ;
  - Première ouverture.

### Objectifs disponibles

#### Collection en route

Le joueur doit obtenir cinq Stickmen différents dans sa collection.

Récompense : 75 pièces.

#### Équipe prête

Le joueur doit créer une équipe jouable.

Récompense : 75 pièces.

### Calcul de la progression

- Le nombre de Stickmen est calculé depuis l’inventaire du joueur.
- L’objectif d’équipe est validé lorsqu’une équipe existe.
- La progression est limitée à la cible de chaque objectif.
- Les objectifs déjà réclamés restent mémorisés sur le compte.

### Réclamation des récompenses

- Un objectif ne peut être réclamé qu’une seule fois.
- La récompense n’est disponible que lorsque la cible est atteinte.
- La réclamation utilise une transaction sécurisée.
- Un verrou d’écriture empêche les doubles réclamations simultanées.
- Chaque récompense est ajoutée à l’historique des mouvements de pièces.
- Les requêtes utilisent un jeton CSRF.
- L’accès est réservé aux joueurs connectés.

### Interface

Le profil affiche maintenant les cinq objectifs disponibles avec :

- leur nom ;
- leur description ;
- leur progression ;
- leur récompense ;
- leur état ;
- un bouton « Réclamer » lorsqu’ils sont disponibles.

L’interface reste volontairement simple afin de privilégier les fonctionnalités.

### Base de données

Aucune migration supplémentaire n’est nécessaire.

La liste des objectifs réclamés ajoutée au compte joueur pendant le J73 est réutilisée.

### Vérifications automatiques

- Calcul de la progression de collection.
- Calcul de la progression d’équipe.
- Conservation des trois objectifs du J73.
- Réclamation d’un objectif disponible.
- Refus d’un objectif non disponible.
- Protection contre les doubles réclamations.
- Enregistrement des récompenses dans l’historique.
- Affichage des cinq objectifs sur le profil.
- Schéma Doctrine synchronisé.
- Conteneur Symfony valide.
- Suite complète validée : 199 tests et 1 724 assertions.

### Résultat

Le joueur peut maintenant progresser grâce à ses combats, ses ouvertures de caisses, sa collection et la création de son équipe.

Les objectifs rendent la progression plus claire tout en ajoutant de nouvelles récompenses en pièces.

La prochaine étape pourra ajouter des objectifs avancés liés aux combats en ligne.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J75 — Ajouter la console d’administration et centraliser les cartes

### Objectif

J75 ajoute une console de commandes utilisable depuis StickVerse par les administrateurs uniquement. Elle permet d’exécuter des actions de gestion simples tout en préparant l’ajout futur de commandes de recherche, de modération et d’administration avancée.

J75 centralise également la structure des cartes Stickmans afin que le wiki et la collection utilisent le même rendu.

### Console d’administration

Une nouvelle page est disponible à l’adresse `/admin`.

Cette page affiche :

- les commandes disponibles ;
- un champ de saisie ;
- un bouton d’exécution ;
- le résultat de la commande ;
- les statistiques principales du catalogue ;
- les liens vers les écrans CRUD existants.

La console reste volontairement simple afin de conserver l’esprit prototype du projet.

### Commandes disponibles

#### `help`

Affiche la liste des commandes utilisables et leur syntaxe.

#### `give <pseudo> <montant>`

Ajoute un nombre de pièces à un joueur identifié par son pseudo.

Exemple :

`give Joueur-AB12 500`

La commande :

- vérifie que le pseudo existe ;
- vérifie que le montant est un nombre entier positif ;
- crédite le portefeuille du joueur ;
- affiche le nouveau solde ;
- enregistre le mouvement dans l’historique des pièces.

#### `recherche <pseudo>`

Affiche le solde actuel d’un joueur.

Exemple :

`recherche Joueur-AB12`

La commande `find` est également acceptée comme alias.

### Historique des mouvements

Un nouveau type de mouvement `admin_credit` est utilisé pour identifier les crédits effectués depuis la console d’administration.

Chaque utilisation de la commande `give` laisse donc une trace dans l’historique des pièces du joueur.

### Sécurité

- L’accès à `/admin` reste protégé par le rôle `ROLE_ADMIN`.
- Un joueur classique reçoit une réponse 403.
- Les commandes sont exécutées uniquement après validation d’un jeton CSRF.
- Les montants négatifs, nuls ou mal formés sont refusés.
- Les pseudos inconnus provoquent un message d’erreur explicite.
- Les commandes inconnues n’exécutent aucune action.

### Centralisation des cartes Stickmans

Le rendu des cartes est maintenant partagé entre le wiki et la collection grâce à un template commun.

Les deux écrans utilisent désormais :

- la même structure HTML ;
- la même classe CSS ;
- la même classe pour les images ;
- la même hauteur d’affichage ;
- les mêmes espacements et tailles de texte.

La quantité possédée reste affichée uniquement dans la collection.

La zone d’image est fixée à une hauteur intermédiaire de 170 pixels afin de conserver des cartes lisibles sans reprendre l’ancienne hauteur excessive.

### Interface d’administration

La navigation réservée aux administrateurs contient maintenant un lien direct vers :

- la console ;
- la gestion des Stickmans ;
- la gestion des caisses ;
- la gestion du contenu des caisses.

### Vérifications automatiques

- Accès refusé à un joueur normal.
- Accès accepté pour un administrateur.
- Exécution d’un crédit avec `give`.
- Vérification du nouveau solde.
- Enregistrement du mouvement `admin_credit`.
- Validation des commandes inconnues.
- Validation des templates Twig.
- Validation du conteneur Symfony.
- Suite complète validée : 202 tests et 1 742 assertions.

### Résultat

Les administrateurs disposent maintenant d’un premier outil de gestion directement intégré au site.

La console pourra être étendue progressivement avec des commandes de recherche de joueurs, de consultation des combats, de gestion des récompenses et d’autres outils internes.

Le wiki et la collection utilisent désormais une structure de carte commune, ce qui permet de modifier leur rendu depuis un seul template partagé.

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

## J76 — Ajouter le système ELO et le classement global

### Objectif

J76 ajoute une cote ELO à chaque joueur afin de mesurer son niveau dans les combats en ligne.

Un classement global permet maintenant de consulter la liste des joueurs et leur position selon leur cote.

### Cote initiale

Chaque nouveau joueur commence avec :

- une cote ELO de 1000 ;
- une position calculée automatiquement dans le classement ;
- une cote modifiable uniquement par le système de résultats des combats.

La cote ne peut jamais devenir négative.

### Calcul ELO

Le calcul utilise un facteur K de 32.

La variation dépend :

- de la cote du joueur ;
- de la cote de son adversaire ;
- du résultat du combat ;
- de la différence de niveau attendue entre les deux joueurs.

Un joueur mieux classé gagne moins de points lorsqu’il bat un adversaire moins bien classé.

Un joueur moins bien classé gagne davantage de points lorsqu’il bat un adversaire mieux classé.

### Résultats pris en compte

Le système ELO traite les résultats suivants :

- victoire ;
- défaite ;
- match nul ;
- victoire par abandon ;
- victoire par forfait ;
- défaite par abandon ;
- défaite par forfait.

Un match nul utilise un score intermédiaire pour les deux joueurs.

Les combats annulés ou encore actifs ne modifient pas les cotes.

### Protection contre les doubles mises à jour

Chaque combat possède maintenant un indicateur permettant de savoir si son résultat ELO a déjà été appliqué.

Une seconde résolution ou une nouvelle actualisation ne peut donc pas modifier une deuxième fois les cotes des joueurs.

Cette protection s’applique également aux combats terminés par abandon ou forfait.

### Classement global

Une nouvelle page est disponible à l’adresse `/classement`.

Elle affiche :

- le rang du joueur ;
- son pseudo public ;
- sa cote ELO ;
- tous les joueurs enregistrés ;
- les joueurs classés par cote décroissante ;
- un ordre secondaire par pseudo pour départager les cotes identiques.

Les adresses électroniques et les informations privées ne sont pas affichées.

Le classement est accessible depuis la navigation principale.

### Profil du joueur

La cote ELO actuelle est maintenant affichée dans la page de profil du joueur.

Le joueur peut ainsi consulter son niveau sans ouvrir la page complète du classement.

### Intégration aux combats

La mise à jour ELO est branchée sur le service central qui attribue les récompenses de fin de combat.

Ainsi, les changements de cote sont déclenchés après :

- une victoire classique ;
- une égalité ;
- un abandon ;
- un forfait.

Les récompenses en pièces et la cote ELO restent gérées dans le même cycle de fin de combat.

### Base de données

La migration `Version20260830210000` ajoute :

- la colonne `elo` sur les comptes joueurs ;
- la colonne `elo_attribuee` sur les combats.

Aucune donnée privée supplémentaire n’est exposée dans le classement.

### Interface

L’interface reste volontairement simple et adaptée au prototype actuel :

- tableau HTML classique ;
- affichage lisible des rangs ;
- surbrillance du joueur connecté ;
- lien ajouté dans la navigation principale ;
- cote visible dans le profil.

### Vérifications automatiques

- Cote initiale à 1000.
- Victoire entre joueurs de même niveau.
- Défaite entre joueurs de même niveau.
- Match nul.
- Victoire d’un joueur mieux classé.
- Victoire d’un joueur moins bien classé.
- Gestion d’un forfait.
- Refus d’une modification sur un combat actif.
- Protection contre la double attribution.
- Affichage de tous les joueurs.
- Tri du classement par cote ELO.
- Affichage de la cote dans le profil.
- Migration Doctrine appliquée en développement et en test.
- Schéma Doctrine synchronisé.
- Conteneur Symfony valide.
- Suite complète validée : 209 tests et 1 765 assertions.

### Résultat

StickVerse possède maintenant une première base compétitive avec une cote individuelle et un classement global.

Chaque combat terminé peut faire évoluer le niveau des joueurs, tandis que la page de classement permet de consulter la position de toute la communauté.

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


