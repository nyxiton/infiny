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
    register_nav_menus(array('primary' => 'Navigation principale', 'footer' => 'Navigation du pied de page'));
}
add_action('after_setup_theme', 'infiny_setup');
function infiny_assets() {
    wp_enqueue_style('infiny', get_stylesheet_uri(), array(), '1.0.1');
    wp_enqueue_script('infiny', get_template_directory_uri() . '/assets/theme.js', array(), '1.0.0', true);
    $gold = sanitize_hex_color(get_theme_mod('infiny_gold', '#c7a66a'));
    if ($gold) { wp_add_inline_style('infiny', ':root{--gold:' . $gold . ';}'); }
}
add_action('wp_enqueue_scripts', 'infiny_assets');
function infiny_customize($c) {
    $c->add_section('infiny_home', array('title' => 'Infiny — Accueil & identité', 'priority' => 30));
    $fields = array('brand' => array('Nom de la maison', 'INFINY'), 'eyebrow' => array('Surtitre', 'INFINY MADAGASCAR'), 'headline' => array('Titre principal', 'Vos envies, sans limite.'), 'intro' => array('Texte principal', 'Parfums et casques : découvrez la sélection INFINY Madagascar et trouvez ce qui vous correspond.'), 'button' => array('Texte du bouton', 'Découvrir nos produits'), 'story' => array('Texte de la maison', 'INFINY Madagascar réunit deux univers : les parfums et les casques. Découvrez notre sélection et choisissez les produits qui accompagnent votre quotidien.'));
    foreach ($fields as $key => $field) {
        $c->add_setting('infiny_' . $key, array('default' => $field[1], 'sanitize_callback' => 'sanitize_text_field'));
        $c->add_control('infiny_' . $key, array('label' => $field[0], 'section' => 'infiny_home', 'type' => 'text'));
    }
    $c->add_setting('infiny_gold', array('default' => '#c7a66a', 'sanitize_callback' => 'sanitize_hex_color'));
    $c->add_control(new WP_Customize_Color_Control($c, 'infiny_gold', array('label' => 'Couleur or', 'section' => 'infiny_home')));
    foreach (array('hero' => 'Image principale', 'collection' => 'Image parfums', 'accessory' => 'Image casques') as $key => $label) {
        $c->add_setting('infiny_' . $key, array('sanitize_callback' => 'esc_url_raw'));
        $c->add_control(new WP_Customize_Image_Control($c, 'infiny_' . $key, array('label' => $label, 'section' => 'infiny_home')));
    }
}
add_action('customize_register', 'infiny_customize');
function infiny_image($key) {
    $images = array('hero' => 'photo-1541643600914-78b084683601', 'collection' => 'photo-1541643600914-78b084683601', 'accessory' => 'photo-1546435770-a3e426bf472b');
    return get_theme_mod('infiny_' . $key, 'https://images.unsplash.com/' . $images[$key] . '?auto=format&fit=crop&w=1920&q=85');
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
    echo '<ul class="nav-links"><li><a href="' . esc_url(home_url('/#collections')) . '">Nos univers</a></li><li><a href="' . esc_url(infiny_shop_url()) . '">La boutique</a></li><li><a href="' . esc_url(home_url('/#maison')) . '">À propos</a></li></ul>';
}

function infiny_site_logo() {
    $logo_id = (int) get_theme_mod('custom_logo');
    $custom_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : false;
    $url = $custom_url ? $custom_url : get_template_directory_uri() . '/assets/logo.jpg';
    $class = $custom_url ? 'brand-logo brand-logo-custom' : 'brand-logo brand-logo-default';
    ?>
    <a class="site-branding" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_theme_mod('infiny_brand', 'INFINY') . ' Madagascar — accueil'); ?>">
        <img class="<?php echo esc_attr($class); ?>" src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr(get_theme_mod('infiny_brand', 'INFINY')); ?>" decoding="async">
        <span class="brand-location">MADAGASCAR</span>
    </a>
    <?php
}
