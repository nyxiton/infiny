<?php
if (!defined('ABSPATH')) { exit; }
function infiny_setup() {
    load_theme_textdomain('infiny-noir-or', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array('height' => 100, 'width' => 240, 'flex-height' => true, 'flex-width' => true));
    add_theme_support('automatic-feed-links');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    register_nav_menus(array('phoenix-primary' => 'Phoenix — Navigation principale', 'phoenix-footer' => 'Phoenix — Pied de page'));
}
add_action('after_setup_theme', 'infiny_setup');
function infiny_assets() {
    wp_enqueue_style('infiny', get_stylesheet_uri(), array(), '2.0.0');
    wp_enqueue_script('infiny', get_template_directory_uri() . '/assets/theme.js', array(), '1.0.0', true);
    $gold = sanitize_hex_color(get_theme_mod('phoenix_gold', '#c7a66a'));
    if ($gold) { wp_add_inline_style('infiny', ':root{--gold:' . $gold . ';}'); }
}
add_action('wp_enqueue_scripts', 'infiny_assets');
function infiny_customize($c) {
    $c->add_section('infiny_home', array('title' => 'Phoenix — Accueil & identité', 'priority' => 30));
    $fields = array(
        'brand' => array('Nom de la boutique', 'PHOENIX LUNETIER'),
        'eyebrow' => array('Surtitre', 'PHOENIX LUNETIER · MADAGASCAR'),
        'headline' => array('Titre principal', 'Un regard. Une signature.'),
        'intro' => array('Texte principal', 'Solaires affirmées, montures de caractère. Découvrez les lunettes qui révèlent votre personnalité.'),
        'button' => array('Texte du bouton', 'Explorer la collection'),
        'story' => array('Texte de présentation', 'Une monture ne se choisit pas seulement pour son style. Elle exprime votre regard sur le monde. Chez Phoenix Lunetier, découvrez un univers dédié aux lunettes de soleil et aux montures de vue à Madagascar.')
    );
    foreach ($fields as $key => $field) {
        $c->add_setting('phoenix_' . $key, array('default' => $field[1], 'sanitize_callback' => 'sanitize_text_field'));
        $c->add_control('phoenix_' . $key, array('label' => $field[0], 'section' => 'infiny_home', 'type' => 'text'));
    }
    $c->add_setting('phoenix_gold', array('default' => '#c7a66a', 'sanitize_callback' => 'sanitize_hex_color'));
    $c->add_control(new WP_Customize_Color_Control($c, 'phoenix_gold', array('label' => 'Couleur or', 'section' => 'infiny_home')));
    foreach (array('hero' => 'Visuel principal', 'collection' => 'Visuel lunettes de soleil', 'accessory' => 'Visuel lunettes de vue', 'logo' => 'Logo Phoenix') as $key => $label) {
        $c->add_setting('phoenix_' . $key, array('sanitize_callback' => 'esc_url_raw'));
        $c->add_control(new WP_Customize_Image_Control($c, 'phoenix_' . $key, array('label' => $label, 'section' => 'infiny_home')));
    }
}
add_action('customize_register', 'infiny_customize');
function infiny_image($key) {
    $images = array('hero' => 'eyewear-hero.svg', 'collection' => 'eyewear-sun.svg', 'accessory' => 'eyewear-optical.svg', 'logo' => 'logo-phoenix.jpg');
    return get_theme_mod('phoenix_' . $key) ?: get_template_directory_uri() . '/assets/' . $images[$key];
}
function infiny_shop_url() { return function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/#collections'); }
function infiny_category_url($slug) {
    if (taxonomy_exists('product_cat')) {
        $term = get_term_by('slug', $slug, 'product_cat');
        if ($term) {
            $url = get_term_link($term);
            if (!is_wp_error($url)) { return $url; }
        }
    }
    return infiny_shop_url();
}
function infiny_fallback_menu() {
    echo '<ul class="nav-links">';
    foreach (array('lunettes-de-soleil' => 'Lunettes de soleil', 'lunettes-de-vue' => 'Lunettes de vue') as $slug => $label) {
        echo '<li><a href="' . esc_url(infiny_category_url($slug)) . '">' . esc_html($label) . '</a></li>';
    }
    echo '<li><a href="' . esc_url(home_url('/#nouveautes')) . '">Nouveautés</a></li><li><a href="' . esc_url(home_url('/#maison')) . '">L’esprit Phoenix</a></li></ul>';
}
function infiny_site_logo() {
    ?>
    <a class="site-branding" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Phoenix Lunetier Madagascar — accueil">
        <img class="brand-logo <?php echo get_theme_mod('phoenix_logo') ? 'brand-logo-custom' : 'brand-logo-default'; ?>" src="<?php echo esc_url(infiny_image('logo')); ?>" alt="Phoenix Lunetier" width="1600" height="1600" decoding="async">
    </a>
    <?php
}
function phoenix_home_title($parts) {
    if (is_front_page()) { $parts['title'] = 'Phoenix Lunetier — Lunettes de soleil & de vue à Madagascar'; unset($parts['site']); }
    return $parts;
}
add_filter('document_title_parts', 'phoenix_home_title');
function phoenix_product_search($form) {
    if (!class_exists('WooCommerce')) { return $form; }
    return '<form role="search" method="get" class="search-form" action="' . esc_url(home_url('/')) . '"><label><span class="screen-reader-text">Rechercher des lunettes</span><input type="search" name="s" placeholder="Rechercher une monture…" value="' . esc_attr(get_search_query()) . '"></label><input type="hidden" name="post_type" value="product"><button type="submit">Rechercher</button></form>';
}
add_filter('get_search_form', 'phoenix_product_search');
