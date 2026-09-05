# Déploiement automatique

Le workflow .github/workflows/deploy.yml déploie automatiquement la branche
main sur le VPS après chaque push. Il peut aussi être lancé manuellement
depuis l’onglet Actions de GitHub.

## Secrets GitHub à configurer

Dans Settings → Secrets and variables → Actions, ajouter les secrets suivants :

- DEPLOY_HOST : 54.36.102.166
- DEPLOY_USER : ubuntu
- DEPLOY_SSH_KEY : la clé privée dédiée au déploiement
- DEPLOY_KNOWN_HOSTS : la ligne produite par
  ssh-keyscan -H 54.36.102.166

Ne jamais ajouter la clé privée, le mot de passe SSH ou les secrets de
production dans le dépôt.

## Clé SSH dédiée

Créer une paire Ed25519 dédiée sur le poste de développement, puis ajouter
uniquement la clé publique dans
/home/ubuntu/.ssh/authorized_keys du VPS. La clé privée reste dans le secret
DEPLOY_SSH_KEY.

Le workflow se connecte au dossier /var/www/stickverse, récupère la branche
main, installe les dépendances, applique les migrations, installe les assets
Importmap, compile les assets, vide le cache et recharge PHP-FPM.

## Envoi des e-mails

La confirmation d’adresse et la réinitialisation du mot de passe utilisent
MAILER_DSN et MAILER_FROM définis dans le fichier .env.local du VPS. Remplacer
le transport null://null par le SMTP du fournisseur choisi avant d’ouvrir les
inscriptions au public. Ces valeurs restent uniquement sur le serveur.
