# Infiny — WordPress avec MariaDB

Environnement Docker local : WordPress (Apache / PHP 8.3) et MariaDB 11.4.
WordPress utilise directement MariaDB, sans adaptateur PostgreSQL.

## Démarrer

Installer et démarrer Docker Desktop ou Docker Engine avec Docker Compose v2.
Un fichier `.env` local contient les mots de passe générés et est exclu de Git.
Pour un nouveau clone, copier `.env.example` vers `.env` et remplacer les deux mots de passe.

```sh
docker compose config --quiet
docker compose up -d --build --remove-orphans
```

Ouvrir http://localhost:8080 et suivre l'assistant WordPress pour créer le site
et le compte administrateur. Le port est configurable avec `WORDPRESS_PORT` dans `.env`.
Le site est accessible uniquement depuis la machine locale. MariaDB n'expose aucun port
sur la machine et reste accessible aux conteneurs sur `mariadb:3306`.

## Remplacement de PostgreSQL

Cette configuration utilise de nouveaux volumes :

- `infiny_wordpress_mariadb_data` : fichiers de la nouvelle installation WordPress ;
- `infiny_mariadb_data` : nouvelle base MariaDB.

L'ancienne installation n'est pas migrée automatiquement. Les volumes
`infiny_wordpress_data` et `infiny_postgres_data` sont conservés par la commande
de démarrage ci-dessus, ainsi que les anciens identifiants PostgreSQL dans `.env`.
Le conteneur PostgreSQL devenu orphelin est retiré, sans supprimer son volume.
Le nouveau WordPress ne charge ni PG4WP ni l'ancien `wp-config.php`.
Pour récupérer des données, prévoir un export/import adapté avant de supprimer les anciens volumes.

## Commandes utiles

```sh
docker compose ps
docker compose logs -f wordpress mariadb
docker compose down
docker compose up -d
```

Les volumes conservent la base, les médias, les thèmes, les extensions et la configuration
après un arrêt ou une recréation des conteneurs. Changer les identifiants MariaDB dans `.env`
ne modifie pas les comptes d'une base déjà initialisée.

## Sauvegarde

```sh
mkdir -p backups
docker compose exec -T mariadb sh -c 'MYSQL_PWD="$MARIADB_PASSWORD" mariadb-dump --single-transaction -u "$MARIADB_USER" "$MARIADB_DATABASE"' > backups/wordpress-mariadb.sql
```

Sauvegarder aussi le volume `infiny_wordpress_mariadb_data` pour conserver les médias,
les thèmes et les extensions. Le dossier `backups/` est exclu de Git.

## Vérifier le démarrage

```sh
docker compose ps
docker compose logs --tail=100 wordpress mariadb
curl -I http://localhost:8080/wp-admin/install.php
```

Après l'installation, vérifier l'accès à l'administration et à la page des mises à jour.
Cette configuration est destinée au développement local.
