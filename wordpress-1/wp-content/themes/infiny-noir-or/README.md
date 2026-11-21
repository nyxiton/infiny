# Phoenix Lunetier — Noir & Or

Thème du second site, conservé dans `wordpress-1/wp-content/themes/infiny-noir-or`
pour rester compatible avec le thème déjà actif dans la copie WordPress.
Le nom affiché dans l’administration est Phoenix Lunetier — Noir & Or.

## Utilisation locale

Le montage Compose relie `wordpress-1/wp-content` au service `wordpress-copy`.
Ouvrir http://localhost:8081 et rafraîchir le navigateur. Si nécessaire :

```sh
docker compose up -d wordpress-copy
docker compose run --rm -T wordpress-copy-cli --skip-plugins --skip-themes theme activate infiny-noir-or
```

## Personnalisation

Apparence → Personnaliser → Phoenix — Accueil & identité : textes, couleur or,
logo et visuels. Le logo fourni est intégré au thème. Les réglages Phoenix ont
leurs propres clés, afin que les textes et images INFINY copiés en base ne
remplacent pas les nouveaux réglages. Les menus disposent également de nouveaux
emplacements Phoenix. Affecter les pages de service et informations légales au
menu Phoenix — Pied de page.

Les illustrations SVG de lunettes sont des visuels éditoriaux originaux inclus
localement. Elles ne représentent pas des produits réels du catalogue.
Remplacer les visuels par vos propres photographies via le personnalisateur.

## Catalogue WooCommerce

Créer les catégories de slugs `lunettes-de-soleil` et `lunettes-de-vue`, puis y
associer les produits lunettes. L’accueil présente les quatre derniers produits
publiés et visibles au catalogue de ces catégories. Sans produits correspondants,
un encart invite à découvrir la boutique. Les catégories absentes redirigent vers
la boutique. Les anciens produits copiés depuis INFINY ne sont pas supprimés :
ils restent disponibles dans le catalogue général jusqu’à leur modification par
l’administrateur. Aucun prix, marque partenaire ou service non fourni n’est inventé.

La recherche cible les produits. Le compte, le panier et la commande utilisent
WooCommerce. Les pages, fiches et archives gardent les templates du thème.

## Installation distante

Le fichier `phoenix-lunetier.zip` contient ce thème avec le dossier racine
`infiny-noir-or`. L’installer en remplacement du thème du second site uniquement,
ou envoyer le dossier via SFTP/rsync. L’archive ne contient ni base ni catalogue.

## Vérification

JavaScript : `node --check assets/theme.js`. Vérifier en WordPress l’accueil,
les variantes, la recherche, le menu mobile, le panier et la commande. Le rendu
PHP/WordPress n’a pas pu être testé dans l’environnement de création, où l’accès
à Docker est refusé et PHP n’est pas installé sur l’hôte.
