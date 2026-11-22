<?php

namespace Infiny\Boutique;

defined('ABSPATH') || exit;

const MENU = 'infiny-boutique';
const TABS = [
    'accueil' => 'Tableau de bord',
    'produits' => 'Produits',
    'categories' => 'Catégories',
    'stock' => 'Stock',
    'commandes' => 'Commandes',
];

function can_manage(): bool {
    return current_user_can('manage_options') && current_user_can('manage_woocommerce');
}

function url(string $tab = 'accueil', array $args = []): string {
    return add_query_arg(['boutique' => '1'] + ($tab === 'accueil' ? [] : ['vue' => $tab]) + $args, home_url('/'));
}

require_once __DIR__ . '/categories.php';

add_action('admin_menu', function () {
    if (can_manage()) {
        $redirect = __NAMESPACE__ . '\\redirect_to_boutique';
        $hook = add_menu_page('Gestion de la boutique', 'Boutique', 'manage_options', MENU, $redirect, 'dashicons-store', 3);
        add_action('load-' . $hook, $redirect);
    }
});

function redirect_to_boutique(): void {
    wp_safe_redirect(url(input($_GET, 'vue') ?: 'accueil', array_filter(['id' => absint(input($_GET, 'id'))])));
    exit;
}

add_action('template_redirect', function () {
    if (input($_GET, 'boutique') !== '1') {
        return;
    }
    nocache_headers();
    header('X-Robots-Tag: noindex, nofollow');
    if (!is_user_logged_in()) {
        wp_safe_redirect(wp_login_url(url(input($_GET, 'vue') ?: 'accueil', array_filter([
            'id' => absint(input($_GET, 'id')),
            'nouveau' => input($_GET, 'nouveau') === '1' ? 1 : 0,
        ]))));
        exit;
    }
    if (!can_manage()) {
        wp_die('Cette page est réservée aux administrateurs de la boutique.', 'Accès refusé', ['response' => 403]);
    }

    status_header(200);
    $css = add_query_arg('ver', filemtime(__DIR__ . '/admin.css'), content_url('mu-plugins/boutique-admin/admin.css'));
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="<?php echo esc_attr(get_bloginfo('charset')); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Gestion de la boutique</title>
        <link id="ib-styles" rel="stylesheet" href="<?php echo esc_url($css); ?>">
    </head>
    <body class="ib-page">
        <?php render(); ?>
    </body>
    </html>
    <?php
    exit;
}, 0);

function input(array $source, string $key): string {
    return isset($source[$key]) && is_string($source[$key]) ? sanitize_text_field(wp_unslash($source[$key])) : '';
}

function button(string $text, string $href, bool $primary = false): void {
    printf('<a class="ib-button%s" href="%s">%s</a>', $primary ? ' ib-primary' : '', esc_url($href), esc_html($text));
}

function money($value, array $args = []): string {
    return $value === '' || $value === null ? '—' : wp_kses_post(wc_price($value, $args));
}

function pagination(string $tab, int $pages, array $args): void {
    if ($pages <= 1) {
        return;
    }
    $base = url($tab, $args + ['numero' => 'IBPAGE']);
    echo '<nav class="ib-pages" aria-label="Pagination">' . wp_kses_post(paginate_links([
        'base' => str_replace('IBPAGE', '%#%', $base),
        'current' => max(1, absint(input($_GET, 'numero'))),
        'total' => $pages,
        'prev_text' => 'Précédent',
        'next_text' => 'Suivant',
    ])) . '</nav>';
}

function statuses(): array {
    return array_replace(wc_get_order_statuses(), [
        'wc-pending' => 'En attente de paiement',
        'wc-processing' => 'En cours',
        'wc-on-hold' => 'En attente',
        'wc-completed' => 'Terminée',
        'wc-cancelled' => 'Annulée',
        'wc-refunded' => 'Remboursée',
        'wc-failed' => 'Échouée',
    ]);
}

function render(): void {
    $tab = input($_GET, 'vue');
    $tab = isset(TABS[$tab]) ? $tab : 'accueil';
    echo '<div class="ib"><header class="ib-header"><h1>Gestion de la boutique</h1><div>';
    button('Voir le site', home_url('/'));
    button('Se déconnecter', wp_logout_url(url()));
    echo '</div></header><nav class="ib-nav" aria-label="Navigation de la boutique">';
    foreach (TABS as $key => $label) {
        printf('<a href="%s"%s>%s</a>', esc_url(url($key)), $key === $tab ? ' aria-current="page"' : '', esc_html($label));
    }
    $new_order = $tab === 'commandes' && input($_GET, 'nouveau') === '1';
    echo '</nav><main><h2>' . esc_html($new_order ? 'Nouvelle commande' : TABS[$tab]) . '</h2>';

    if (!class_exists('WooCommerce')) {
        echo '<div class="notice notice-warning inline"><p>Activez WooCommerce pour gérer votre boutique.</p></div>';
        if (current_user_can('activate_plugins')) {
            button('Ouvrir les extensions', admin_url('plugins.php'));
        }
    } else {
        $error_key = 'ib_error_' . get_current_user_id();
        $error = get_transient($error_key);
        if ($error) {
            delete_transient($error_key);
            echo '<div class="notice notice-error inline" role="alert"><p>' . esc_html($error) . '</p></div>';
        }
        if (input($_GET, 'enregistre') === '1') {
            echo '<div class="notice notice-success inline" role="status"><p>Modifications enregistrées.</p></div>';
        }
        $id = absint(input($_GET, 'id'));
        switch ($tab) {
            case 'produits':
            case 'stock':
                if ($id || input($_GET, 'nouveau') === '1') {
                    product_form($tab, $id);
                } else {
                    products($tab);
                }
                break;
            case 'categories':
                categories($id);
                break;
            case 'commandes':
                if ($new_order) {
                    new_order_form();
                } elseif ($id) {
                    order_detail($id);
                } else {
                    orders();
                }
                break;
            default:
                dashboard();
        }
    }
    echo '</main></div>';
}

function dashboard(): void {
    $out = wc_get_products(['stock_status' => 'outofstock', 'status' => 'publish', 'limit' => 1, 'paginate' => true]);
    $today = current_datetime()->format('Y-m-d');
    $today_orders = wc_get_orders([
        'date_created' => $today,
        'limit' => 1,
        'paginate' => true,
    ]);
    $cards = [
        ['Commandes du jour', $today_orders->total, url('commandes', ['jour' => $today])],
        ['Commandes en cours', wc_orders_count('processing'), url('commandes', ['statut' => 'wc-processing'])],
        ['Produits en rupture', $out->total, url('stock', ['rupture' => 1])],
    ];
    echo '<div class="ib-cards">';
    foreach ($cards as [$label, $total, $href]) {
        printf('<a href="%s"><span>%s</span><strong>%s</strong></a>', esc_url($href), esc_html($label), esc_html(number_format_i18n($total)));
    }
    echo '</div><div class="ib-toolbar">';
    if (current_user_can('publish_products')) {
        button('Ajouter un produit', url('produits', ['nouveau' => 1]), true);
    }
    button('Voir les commandes', url('commandes'));
    echo '</div><h3>Dernières commandes</h3>';
    order_table(wc_get_orders(['limit' => 5, 'orderby' => 'date', 'order' => 'DESC']));
}

function products(string $tab): void {
    $search = input($_GET, 'recherche');
    $rupture = $tab === 'stock' && input($_GET, 'rupture') === '1';
    $args = [
        'status' => ['publish', 'private', 'draft', 'pending', 'future'],
        'limit' => 20,
        'page' => max(1, absint(input($_GET, 'numero'))),
        'paginate' => true,
        'orderby' => 'name',
        'order' => 'ASC',
    ];
    if ($search !== '') {
        $exact = wc_get_product_id_by_sku($search);
        if ($exact) {
            $args['include'] = [$exact];
        } else {
            $args['s'] = $search;
        }
    }
    if ($rupture) {
        $args['status'] = 'publish';
        $args['stock_status'] = 'outofstock';
    }
    $result = wc_get_products($args);
    ?>
    <div class="ib-toolbar">
        <form method="get" action="<?php echo esc_url(home_url('/')); ?>">
            <input type="hidden" name="boutique" value="1">
            <input type="hidden" name="vue" value="<?php echo esc_attr($tab); ?>">
            <?php if ($rupture) : ?><input type="hidden" name="rupture" value="1"><?php endif; ?>
            <label for="ib-search">Nom ou référence exacte</label>
            <input type="search" name="recherche" id="ib-search" value="<?php echo esc_attr($search); ?>">
            <button class="button">Rechercher</button>
        </form>
        <?php
        if ($tab === 'produits' && current_user_can('publish_products')) {
            button('Ajouter un produit', url($tab, ['nouveau' => 1]), true);
        }
        if ($tab === 'stock') {
            button($rupture ? 'Voir tout le stock' : 'Voir les ruptures', url('stock', $rupture ? [] : ['rupture' => 1]));
        }
        ?>
    </div>
    <?php
    if (!$result->products) {
        echo '<p class="ib-empty">Aucun produit trouvé.</p>';
        return;
    }
    echo '<div class="ib-table"><table><thead><tr><th>Produit</th><th>Référence</th><th>Prix de vente</th><th>Stock</th><th>Action</th></tr></thead><tbody>';
    foreach ($result->products as $product) {
        $quantity = $product->get_stock_quantity();
        $stock = $quantity ?? ($product->is_in_stock() ? 'Non suivi' : 'Rupture');
        $href = url($tab, ['id' => $product->get_id()]);
        echo '<tr><td><div class="ib-product">';
        if ($product->get_image_id()) {
            echo wp_get_attachment_image($product->get_image_id(), [48, 48], false, ['alt' => '']);
        }
        echo '<strong>' . esc_html($product->get_name()) . '</strong></div></td>';
        echo '<td>' . esc_html($product->get_sku() ?: '—') . '</td><td>' . money($product->get_price()) . '</td>';
        printf('<td><span class="ib-pill%s">%s</span></td><td>', !$product->is_in_stock() ? ' ib-alert' : '', esc_html((string) $stock));
        if (current_user_can('edit_product', $product->get_id())) {
            button($tab === 'stock' ? 'Ajuster' : 'Modifier', $href);
        }
        echo '</td></tr>';
    }
    echo '</tbody></table></div>';
    pagination($tab, (int) $result->max_num_pages, ['recherche' => $search, 'rupture' => (int) $rupture]);
}

function product_form(string $tab, int $id): void {
    $product = $id ? wc_get_product($id) : new \WC_Product_Simple();
    if (!$product || !($id ? current_user_can('edit_product', $id) : current_user_can('publish_products'))) {
        echo '<p class="ib-empty">Produit introuvable ou accès indisponible.</p>';
        return;
    }
    if (!$product->is_type('simple')) {
        echo '<p>Ce produit utilise des options, comme des tailles ou des couleurs. Gérez ses options dans WooCommerce.</p>';
        button('Ouvrir dans WooCommerce', get_edit_post_link($id, 'raw'));
        return;
    }
    $stock_only = $tab === 'stock' && $id;
    $photos = array_filter(array_merge([$product->get_image_id()], $product->get_gallery_image_ids()));
    button('Retour à la liste', url($tab));
    ?>
    <form class="ib-panel" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
        <h3><?php echo esc_html($id ? $product->get_name() : 'Nouveau produit'); ?></h3>
        <input type="hidden" name="action" value="infiny_boutique_product">
        <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">
        <input type="hidden" name="vue" value="<?php echo esc_attr($tab); ?>">
        <?php wp_nonce_field('ib-product-' . $id); ?>
        <div class="ib-fields">
            <?php if (!$stock_only) : ?>
                <label>Nom <input name="nom" required value="<?php echo esc_attr($product->get_name('edit')); ?>"></label>
                <label>Référence <input name="reference" value="<?php echo esc_attr($product->get_sku('edit')); ?>"></label>
                <label>Prix d’achat (<?php echo esc_html(get_woocommerce_currency()); ?>)
                    <input name="achat" inputmode="decimal" value="<?php echo esc_attr($product->meta_exists('_infiny_purchase_price') ? $product->get_meta('_infiny_purchase_price') : ($product->get_cogs_value() ?? '')); ?>">
                </label>
                <label>Prix de vente (<?php echo esc_html(get_woocommerce_currency()); ?>)
                    <input name="vente" inputmode="decimal" required value="<?php echo esc_attr($product->get_regular_price('edit')); ?>">
                </label>
            <?php endif; ?>
            <label>Stock
                <input name="stock" type="number" step="1" <?php echo $id && !$stock_only ? '' : 'required'; ?>
                    value="<?php echo esc_attr($product->get_stock_quantity('edit') ?? ''); ?>">
                <?php if ($id && !$product->managing_stock()) : ?><small>Saisissez une quantité pour activer le suivi.</small><?php endif; ?>
            </label>
        </div>
        <?php if (!$stock_only && current_user_can('assign_product_terms')) {
            product_categories($product);
        } ?>
        <?php if (!$stock_only && current_user_can('upload_files')) : ?>
            <fieldset><legend>Photos</legend>
                <?php if ($photos) : ?>
                    <div class="ib-photos">
                        <?php foreach ($photos as $photo) : ?>
                            <label><?php echo wp_get_attachment_image($photo, 'thumbnail', false, ['alt' => '']); ?>
                                <span><input type="checkbox" name="retirer[]" value="<?php echo esc_attr($photo); ?>"> Retirer</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <label>Ajouter des photos <input type="file" name="photos[]" accept="image/*" multiple></label>
            </fieldset>
        <?php endif; ?>
        <button class="button button-primary">Enregistrer</button>
    </form>
    <?php
}

function finish(string $destination, string $error = ''): void {
    if ($error !== '') {
        set_transient('ib_error_' . get_current_user_id(), $error, MINUTE_IN_SECONDS);
    } else {
        $destination = add_query_arg('enregistre', 1, $destination);
    }
    wp_safe_redirect($destination);
    exit;
}

function price(string $input): ?string {
    $normalized = str_replace(',', '.', trim($input));
    return preg_match('/^\d+(?:\.\d+)?$/D', $normalized) ? wc_format_decimal($normalized) : null;
}

add_action('admin_post_infiny_boutique_product', function () {
    $id = absint(input($_POST, 'id'));
    check_admin_referer('ib-product-' . $id);
    if (!class_exists('WooCommerce') || !can_manage() ||
        !($id ? current_user_can('edit_product', $id) : current_user_can('publish_products'))) {
        wp_die('Accès indisponible.', '', ['response' => 403]);
    }
    $stock_only = $id && input($_POST, 'vue') === 'stock';
    $tab = $stock_only ? 'stock' : 'produits';
    $destination = url($tab, $id ? ['id' => $id] : ['nouveau' => 1]);
    $product = $id ? wc_get_product($id) : new \WC_Product_Simple();
    if (!$product || !$product->is_type('simple')) {
        finish($destination, 'Ce produit se gère dans WooCommerce.');
    }
    $stock = input($_POST, 'stock');
    if (($stock === '' && ($stock_only || !$id)) || ($stock !== '' && filter_var($stock, FILTER_VALIDATE_INT) === false)) {
        finish($destination, 'Saisissez un stock en nombre entier.');
    }

    try {
        if (!$stock_only) {
            $name = input($_POST, 'nom');
            $sku = input($_POST, 'reference');
            $retail = price(input($_POST, 'vente'));
            $buy_input = input($_POST, 'achat');
            $buy = price($buy_input);
            if ($name === '' || $retail === null || ($buy_input !== '' && $buy === null)) {
                finish($destination, 'Saisissez le nom et des prix valides, par exemple 15000 ou 15000,50.');
            }
            if ($name !== $product->get_name('edit')) {
                $product->set_name($name);
            }
            if ($sku !== $product->get_sku('edit')) {
                $product->set_sku($sku);
            }
            $product->set_regular_price($retail);
            if ($buy === null) {
                $product->delete_meta_data('_infiny_purchase_price');
            } else {
                $product->update_meta_data('_infiny_purchase_price', $buy);
            }
            $product->set_cogs_value($buy === null ? null : (float) $buy);
            if (current_user_can('assign_product_terms') && input($_POST, 'categories_presentes') === '1') {
                $ids = submitted_categories();
                if (is_wp_error($ids)) {
                    finish($destination, 'La catégorie n’a pas pu être créée. Vérifiez son nom.');
                }
                $product->set_category_ids($ids);
            }
        }
        if ($stock !== '') {
            $product->set_manage_stock(true);
            $product->set_stock_quantity((int) $stock);
        }
        if (!$id) {
            $product->set_status('publish');
        }
        $id = $product->save();
    } catch (\WC_Data_Exception $error) {
        finish($destination, $error->getErrorCode() === 'product_invalid_sku'
            ? 'Cette référence est déjà utilisée par un autre produit.'
            : 'Vérifiez les informations du produit et réessayez.');
    }

    $destination = url($tab, ['id' => $id]);
    if (!$stock_only && current_user_can('upload_files')) {
        $photo_error = save_photos($product);
        if ($photo_error) {
            finish($destination, 'Le produit est enregistré. Certaines photos n’ont pas pu être ajoutées.');
        }
    }
    finish($destination);
});

function save_photos(\WC_Product $product): bool {
    $removed = isset($_POST['retirer']) && is_array($_POST['retirer']) ? array_map('absint', $_POST['retirer']) : [];
    $photos = array_filter(array_merge([$product->get_image_id()], $product->get_gallery_image_ids()));
    $photos = array_values(array_diff($photos, $removed));
    $upload = $_FILES['photos'] ?? [];
    $failed = false;
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    $types = array_filter(get_allowed_mime_types(), static function ($mime) {
        return strpos($mime, 'image/') === 0;
    });
    foreach ($upload['error'] ?? [] as $index => $error) {
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $key) {
            $_FILES['ib_photo'][$key] = $upload[$key][$index];
        }
        $photo = media_handle_upload('ib_photo', $product->get_id(), [], ['test_form' => false, 'mimes' => $types]);
        if (is_wp_error($photo)) {
            $failed = true;
        } else {
            $photos[] = $photo;
        }
    }
    $product->set_image_id(array_shift($photos) ?: 0);
    $product->set_gallery_image_ids($photos);
    $product->save();
    return $failed;
}

function orders(): void {
    $status = input($_GET, 'statut');
    $labels = statuses();
    $status = isset($labels[$status]) ? $status : '';
    $day = input($_GET, 'jour');
    $args = ['limit' => 20, 'paged' => max(1, absint(input($_GET, 'numero'))), 'paginate' => true, 'orderby' => 'date', 'order' => 'DESC'];
    if ($status !== '') {
        $args['status'] = $status;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/D', $day)) {
        $args['date_created'] = $day;
    }
    $result = wc_get_orders($args);
    ?>
    <div class="ib-toolbar">
        <form method="get" action="<?php echo esc_url(home_url('/')); ?>">
            <input type="hidden" name="boutique" value="1">
            <input type="hidden" name="vue" value="commandes">
            <label for="ib-status">Statut</label>
            <select name="statut" id="ib-status"><option value="">Tous les statuts</option>
                <?php foreach ($labels as $code => $label) : ?>
                    <option value="<?php echo esc_attr($code); ?>" <?php selected($status, $code); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="ib-day">Date</label>
            <input type="date" name="jour" id="ib-day" value="<?php echo esc_attr($day); ?>">
            <button class="button">Filtrer</button>
        </form>
        <?php if (current_user_can('publish_shop_orders')) {
            button('Nouvelle commande', url('commandes', ['nouveau' => 1]), true);
        } ?>
    </div>
    <?php
    order_table($result->orders);
    pagination('commandes', (int) $result->max_num_pages, ['statut' => $status, 'jour' => $day]);
}

function new_order_form(): void {
    if (!current_user_can('publish_shop_orders')) {
        echo '<p class="ib-empty" role="alert">Vous n’avez pas l’autorisation de créer une commande.</p>';
        return;
    }

    $controller = \wc_get_container()->get(\Automattic\WooCommerce\Internal\Admin\Orders\PageController::class);
    $editor = $controller->get_new_page_url();
    $script = add_query_arg('ver', filemtime(__DIR__ . '/order-editor.js'), content_url('mu-plugins/boutique-admin/order-editor.js'));
    ?>
    <div class="ib-toolbar">
        <?php button('Retour aux commandes', url('commandes')); ?>
    </div>
    <iframe id="ib-order-editor" class="ib-order-editor" title="Formulaire de commande WooCommerce"
        src="<?php echo esc_url($editor); ?>"></iframe>
    <p id="ib-order-editor-fallback" hidden>
        Le formulaire intégré est indisponible.
        <a href="<?php echo esc_url($editor); ?>">Ouvrir le formulaire dans WooCommerce</a>
    </p>
    <script src="<?php echo esc_url($script); ?>" defer></script>
    <?php
}

function order_table(array $orders): void {
    if (!$orders) {
        echo '<p class="ib-empty">Aucune commande trouvée.</p>';
        return;
    }
    $labels = statuses();
    echo '<div class="ib-table"><table><thead><tr><th>Commande</th><th>Date</th><th>Client</th><th>Total</th><th>Statut</th></tr></thead><tbody>';
    foreach ($orders as $order) {
        $date = $order->get_date_created();
        echo '<tr><td>';
        button('#' . $order->get_order_number(), url('commandes', ['id' => $order->get_id()]));
        echo '</td><td>' . esc_html($date ? wc_format_datetime($date, 'd/m/Y H:i') : '—') . '</td>';
        echo '<td>' . esc_html($order->get_formatted_billing_full_name() ?: '—') . '</td>';
        echo '<td>' . money($order->get_total(), ['currency' => $order->get_currency()]) . '</td>';
        echo '<td><span class="ib-pill">' . esc_html($labels['wc-' . $order->get_status()] ?? $order->get_status()) . '</span></td></tr>';
    }
    echo '</tbody></table></div>';
}

function editable_statuses(): array {
    return array_intersect_key(statuses(), array_flip(['wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled']));
}

function order_detail(int $id): void {
    $order = wc_get_order($id);
    if (!$order instanceof \WC_Order) {
        echo '<p class="ib-empty">Commande introuvable.</p>';
        return;
    }
    button('Retour aux commandes', url('commandes'));
    $labels = statuses();
    $status = 'wc-' . $order->get_status();
    ?>
    <section class="ib-panel">
        <h3>Commande #<?php echo esc_html($order->get_order_number()); ?></h3>
        <p><?php echo esc_html($order->get_formatted_billing_full_name() ?: 'Client non renseigné'); ?></p>
        <p>Statut actuel <strong><?php echo esc_html($labels[$status] ?? $status); ?></strong></p>
        <div class="ib-table"><table><thead><tr><th>Produit</th><th>Quantité</th><th>Montant</th></tr></thead><tbody>
            <?php foreach ($order->get_items() as $item) : ?>
                <tr><td><?php echo esc_html($item->get_name()); ?></td>
                    <td><?php echo esc_html($item->get_quantity()); ?></td>
                    <td><?php echo money($order->get_line_total($item, true), ['currency' => $order->get_currency()]); ?></td></tr>
            <?php endforeach; ?>
        </tbody></table></div>
        <p>Total de la commande <strong><?php echo money($order->get_total(), ['currency' => $order->get_currency()]); ?></strong></p>
        <?php if (current_user_can('edit_shop_order', $id) && $status !== 'wc-refunded') : ?>
            <form class="ib-toolbar" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <input type="hidden" name="action" value="infiny_boutique_order">
                <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">
                <?php wp_nonce_field('ib-order-' . $id); ?>
                <label for="ib-order-status">Changer le statut</label>
                <select id="ib-order-status" name="statut">
                    <?php foreach ([$status => $labels[$status] ?? $status] + editable_statuses() as $code => $label) : ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($status, $code); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button button-primary">Enregistrer le statut</button>
            </form>
            <?php button('Autres actions dans WooCommerce', $order->get_edit_order_url()); ?>
        <?php endif; ?>
    </section>
    <?php
}

add_action('admin_post_infiny_boutique_order', function () {
    $id = absint(input($_POST, 'id'));
    check_admin_referer('ib-order-' . $id);
    if (!class_exists('WooCommerce') || !can_manage() || !current_user_can('edit_shop_order', $id)) {
        wp_die('Accès indisponible.', '', ['response' => 403]);
    }
    $destination = url('commandes', ['id' => $id]);
    $order = wc_get_order($id);
    $status = input($_POST, 'statut');
    if (!$order instanceof \WC_Order || $order->has_status('refunded')) {
        finish($destination, 'Le statut de cette commande ne peut pas être modifié ici.');
    }
    if (!$order->has_status(substr($status, 3))) {
        if (!isset(editable_statuses()[$status]) || !$order->update_status(substr($status, 3), '', true)) {
            finish($destination, 'Le statut n’a pas pu être enregistré.');
        }
    }
    finish($destination);
});
