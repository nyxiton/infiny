# Infiny — Noir & Or

Thème WordPress classique, compatible WooCommerce, sans outil de compilation.

## Installation

Dans WordPress : Apparence → Thèmes → Ajouter un thème → Téléverser, sélectionner `infiny-noir-or.zip`, puis activer. Le thème est également déjà présent dans le dossier local `wordpress/wp-content/themes/infiny-noir-or`.

L’accueil éditorial est affiché automatiquement par `front-page.php`. Dans Réglages → Lecture, choisir une page d’accueil statique et une page des articles distincte pour conserver une page de blog.

Apparence → Personnaliser → Infiny — Accueil & identité : modifier le nom, les textes, la nuance d’or et les trois images. Les images par défaut sont des photographies de démonstration hébergées sur Unsplash ; elles demandent une connexion Internet. Remplacer ces images par vos propres médias pour un site autonome. Les images comportent un fond de secours si le service est indisponible.

Apparence → Menus : affecter vos menus aux emplacements Navigation principale et Navigation du pied de page. Ajouter vos pages contact, conditions et informations légales au menu du pied de page. Le nom de la maison se règle dans la section Infiny ; le réglage natif du titre du site reste utilisé dans les métadonnées WordPress.

WooCommerce : les quatre derniers produits publiés et visibles apparaissent sur l’accueil. Les cartes Parfums et Casques ouvrent les catégories WooCommerce de slugs `parfums` et `casques` si elles existent, sinon la boutique. Le panier et la recherche utilisent les fonctionnalités réelles de WordPress/WooCommerce. Sans WooCommerce, les liens ouvrent la section des collections et le panier est masqué.

## Docker de ce projet

Le service WordPress utilise un volume Docker et ne monte pas le dossier source local. Installer le ZIP par l’administration du site qui tourne réellement, ou copier le thème dans le conteneur :

```sh
docker compose cp wordpress/wp-content/themes/infiny-noir-or wordpress:/var/www/html/wp-content/themes/
```

Puis activer dans Apparence → Thèmes. Aucun produit, page ou réglage existant n’est modifié par les fichiers du thème.

## Vérification manuelle après activation

Contrôler l’accueil sur mobile et ordinateur, le menu mobile, la recherche, les produits, les variantes, le panier, la commande et le compte client. Tester également les changements de textes et images dans le personnalisateur. Les styles des extensions de paiement peuvent demander des ajustements selon les extensions installées.

L’identité affichée est INFINY Madagascar. Les visuels distants de démonstration représentent un parfum et un casque audio ; remplacez-les dans le personnalisateur par vos photos de produits.

Le fichier `assets/logo.jpg` est le logo par défaut de l’en-tête et du pied de page. Pour le remplacer : Apparence → Personnaliser → Identité du site → Logo. Le fichier original est conservé ; ses marges sont cadrées en CSS pour le logo par défaut.
