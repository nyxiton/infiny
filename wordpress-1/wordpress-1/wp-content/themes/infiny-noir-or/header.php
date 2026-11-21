<?php if (!defined('ABSPATH')) { exit; } ?><!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="skip-link" href="#main">Aller au contenu</a>
<header class="site-header">
<button class="menu-toggle" aria-expanded="false" aria-controls="primary-nav"><span aria-hidden="true">☰</span> Menu</button>
<?php infiny_site_logo(); ?>
<div class="header-actions"><button class="search-toggle" aria-expanded="false" aria-controls="search-panel" aria-label="Ouvrir la recherche"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg></button><?php if (function_exists('wc_get_cart_url')) : ?><a href="<?php echo esc_url(wc_get_cart_url()); ?>" aria-label="Voir le panier"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14l1 14H4L5 7Zm3 0V5a4 4 0 0 1 8 0v2"/></svg></a><?php endif; ?></div>
<nav id="primary-nav" class="primary-nav" aria-label="Navigation principale"><?php wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'menu_class' => 'nav-links', 'fallback_cb' => 'infiny_fallback_menu')); ?></nav>
<div id="search-panel" hidden><?php get_search_form(); ?></div>
</header>
