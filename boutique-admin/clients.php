<?php

namespace Infiny\Boutique;

defined('ABSPATH') || exit;

const CLIENT_TYPE = 'infiny_client';
const CLIENT_META = '_infiny_client_details';
const CLIENT_PHONE = '_infiny_client_phone';
const ORDER_CLIENT = '_infiny_boutique_client_id';

add_action('init', function () {
    register_post_type(CLIENT_TYPE, ['public' => false, 'show_ui' => false, 'supports' => ['title']]);
});

function client(int $id): ?\WP_Post {
    $post = $id ? get_post($id) : null;
    return $post && $post->post_type === CLIENT_TYPE && $post->post_status === 'private' ? $post : null;
}

function client_details(int $id): array {
    $details = get_post_meta($id, CLIENT_META, true);
    return is_array($details) ? $details : [];
}

function client_phone_digits(string $phone): string {
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) === 12 && str_starts_with($digits, '261')) {
        return '0' . substr($digits, 3);
    }
    return $digits;
}

function clients(int $id): void {
    $record = client($id);
    if ($id && !$record) {
        echo '<p class="ib-empty">Client introuvable.</p>';
        button('Retour aux clients', url('clients'));
        return;
    }
    $context = input($_GET, 'pour_commande');
    $details = $record ? client_details($id) : [];
    if ($record || $context !== '') {
        button('Retour', $context === 'nouvelle'
            ? url('commandes', ['nouveau' => 1])
            : ($context ? url('commandes', ['id' => absint($context)]) : url('clients')));
    }
    ?>
    <form class="ib-panel" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <h3><?php echo $id ? 'Modifier le client' : 'Nouveau client'; ?></h3>
        <input type="hidden" name="action" value="infiny_boutique_client">
        <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">
        <input type="hidden" name="pour_commande" value="<?php echo esc_attr($context); ?>">
        <?php wp_nonce_field('ib-client-' . $id); ?>
        <div class="ib-fields">
            <label>Nom <input name="nom" required maxlength="100" autocomplete="family-name" value="<?php echo esc_attr($details['last'] ?? ''); ?>"></label>
            <label>Prénom <input name="prenom" required maxlength="100" autocomplete="given-name" value="<?php echo esc_attr($details['first'] ?? ''); ?>"></label>
            <label>Téléphone <input name="telephone" type="tel" required maxlength="25" autocomplete="tel" value="<?php echo esc_attr($details['phone'] ?? ''); ?>"></label>
        </div>
        <button class="button button-primary"><?php echo $context !== '' && $context !== 'nouvelle' ? 'Enregistrer et lier à la commande' : 'Enregistrer le client'; ?></button>
    </form>
    <?php
    if (!$id && $context === '') {
        client_list();
    }
}

function client_list(): void {
    $search = input($_GET, 'recherche');
    $args = [
        'post_type' => CLIENT_TYPE,
        'post_status' => 'private',
        'posts_per_page' => 20,
        'paged' => max(1, absint(input($_GET, 'numero'))),
        'orderby' => ['title' => 'ASC', 'ID' => 'ASC'],
    ];
    if ($search !== '') {
        if (preg_match('/^[0-9+().\s-]+$/uD', $search) && client_phone_digits($search) !== '') {
            $args['meta_query'] = [['key' => CLIENT_PHONE, 'value' => client_phone_digits($search), 'compare' => 'LIKE']];
        } else {
            $args['s'] = $search;
            $args['search_columns'] = ['post_title'];
        }
    }
    $query = new \WP_Query($args);
    ?>
    <h3>Clients enregistrés</h3>
    <div class="ib-toolbar">
        <form method="get" action="<?php echo esc_url(home_url('/')); ?>">
            <input type="hidden" name="boutique" value="1">
            <input type="hidden" name="vue" value="clients">
            <label for="ib-client-search">Nom, prénom ou téléphone</label>
            <input type="search" name="recherche" id="ib-client-search" value="<?php echo esc_attr($search); ?>">
            <button class="button">Rechercher</button>
        </form>
    </div>
    <?php if (!$query->posts) : ?>
        <p class="ib-empty">Aucun client trouvé.</p>
    <?php else : ?>
        <div class="ib-table"><table>
            <thead><tr><th>Nom</th><th>Prénom</th><th>Téléphone</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($query->posts as $record) :
                    $details = client_details($record->ID);
                    ?>
                    <tr>
                        <td><?php echo esc_html($details['last'] ?? ''); ?></td>
                        <td><?php echo esc_html($details['first'] ?? ''); ?></td>
                        <td><?php echo esc_html($details['phone'] ?? ''); ?></td>
                        <td><?php button('Modifier', url('clients', ['id' => $record->ID])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php pagination('clients', (int) $query->max_num_pages, ['recherche' => $search]); ?>
    <?php endif;
}

function editable_client_order(int $id): ?\WC_Order {
    $order = $id && function_exists('wc_get_order') && can_manage() && current_user_can('edit_shop_order', $id)
        ? wc_get_order($id)
        : null;
    return $order instanceof \WC_Order ? $order : null;
}

function order_client(\WC_Order $order): ?\WP_Post {
    return client((int) $order->get_meta(ORDER_CLIENT));
}

function order_client_name(\WC_Order $order): string {
    $linked = order_client($order);
    return $linked ? $linked->post_title : $order->get_formatted_billing_full_name();
}

function set_order_client(\WC_Order $order, int $client_id, bool $fill_billing = false): void {
    if (!$client_id) {
        $order->delete_meta_data(ORDER_CLIENT);
    } else {
        $order->update_meta_data(ORDER_CLIENT, $client_id);
        if ($fill_billing && !$order->get_customer_id()) {
            $details = client_details($client_id);
            foreach (['first_name' => 'first', 'last_name' => 'last', 'phone' => 'phone'] as $field => $key) {
                $getter = 'get_billing_' . $field;
                $setter = 'set_billing_' . $field;
                if ($order->$getter() === '') {
                    $order->$setter($details[$key] ?? '');
                }
            }
        }
    }
    $order->save();
}

function client_select(int $selected, string $field, string $id): void {
    $records = get_posts([
        'post_type' => CLIENT_TYPE, 'post_status' => 'private', 'posts_per_page' => -1,
        'orderby' => ['title' => 'ASC', 'ID' => 'ASC'],
    ]);
    ?>
    <select name="<?php echo esc_attr($field); ?>" id="<?php echo esc_attr($id); ?>">
        <option value="0">Aucun client lié</option>
        <?php foreach ($records as $record) :
            $details = client_details($record->ID);
            ?>
            <option value="<?php echo (int) $record->ID; ?>" <?php selected($selected, $record->ID); ?>>
                <?php echo esc_html($record->post_title . ' · ' . ($details['phone'] ?? '')); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

function order_client_section(\WC_Order $order): void {
    $linked = order_client($order);
    $name = order_client_name($order);
    $phone = $linked ? (client_details($linked->ID)['phone'] ?? '') : $order->get_billing_phone();
    ?>
    <p><strong><?php echo esc_html($name ?: 'Client non renseigné'); ?></strong>
        <?php if ($phone !== '') : ?> · <?php echo esc_html($phone); ?><?php endif; ?>
    </p>
    <?php if (current_user_can('edit_shop_order', $order->get_id())) : ?>
        <div class="ib-toolbar">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="infiny_boutique_order_client">
                <input type="hidden" name="id" value="<?php echo (int) $order->get_id(); ?>">
                <?php wp_nonce_field('ib-order-client-' . $order->get_id()); ?>
                <label for="ib-order-client">Client Boutique</label>
                <?php client_select($linked->ID ?? 0, 'client', 'ib-order-client'); ?>
                <button class="button">Enregistrer le lien</button>
            </form>
            <?php button('Créer un client', url('clients', ['pour_commande' => $order->get_id()])); ?>
        </div>
    <?php endif;
}

add_action('admin_post_infiny_boutique_client', function () {
    $id = absint(input($_POST, 'id'));
    check_admin_referer('ib-client-' . $id);
    if (!can_manage()) {
        wp_die('Accès indisponible.', '', ['response' => 403]);
    }
    if ($id && !client($id)) {
        finish(url('clients'), 'Client introuvable.');
    }
    $context = input($_POST, 'pour_commande');
    $order = $context !== '' && $context !== 'nouvelle' ? editable_client_order(absint($context)) : null;
    if (($context !== '' && $context !== 'nouvelle' && !$order) ||
        ($context === 'nouvelle' && !current_user_can('publish_shop_orders'))) {
        wp_die('Accès indisponible.', '', ['response' => 403]);
    }
    $destination = url('clients', array_filter(['id' => $id, 'pour_commande' => $context]));
    $last = input($_POST, 'nom');
    $first = input($_POST, 'prenom');
    $phone = input($_POST, 'telephone');
    $digits = client_phone_digits($phone);
    if ($first === '' || $last === '' || mb_strlen($first) > 100 || mb_strlen($last) > 100) {
        finish($destination, 'Renseignez le nom et le prénom.');
    }
    if (!preg_match('/^\+?[0-9().\s-]+$/uD', $phone) || strlen($digits) < 7 || strlen($digits) > 15 || strlen($phone) > 25) {
        finish($destination, 'Saisissez un numéro de téléphone valide, par exemple 034 12 345 67.');
    }
    $saved = wp_insert_post(wp_slash([
        'ID' => $id,
        'post_type' => CLIENT_TYPE,
        'post_status' => 'private',
        'post_title' => $first . ' ' . $last,
        'meta_input' => [CLIENT_META => compact('first', 'last', 'phone'), CLIENT_PHONE => $digits],
    ]), true);
    if (!$saved || is_wp_error($saved)) {
        finish($destination, 'Le client n’a pas pu être enregistré.');
    }
    if ($order) {
        set_order_client($order, $saved);
        finish(url('commandes', ['id' => $order->get_id()]));
    }
    if ($context === 'nouvelle') {
        finish(url('commandes', ['nouveau' => 1, 'client' => $saved]));
    }
    finish(url('clients'));
});

add_action('admin_post_infiny_boutique_order_client', function () {
    $id = absint(input($_POST, 'id'));
    check_admin_referer('ib-order-client-' . $id);
    $order = editable_client_order($id);
    if (!$order) {
        wp_die('Accès indisponible.', '', ['response' => 403]);
    }
    $client_id = absint(input($_POST, 'client'));
    $destination = url('commandes', ['id' => $id]);
    if ($client_id && !client($client_id)) {
        finish($destination, 'Client introuvable.');
    }
    set_order_client($order, $client_id);
    finish($destination);
});

add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
    if (input($_GET, 'ib_boutique') !== '1' || !editable_client_order($order->get_id())) {
        return;
    }
    $selected = order_client($order) ?: client(absint(input($_GET, 'ib_client')));
    echo '<p><label for="ib-native-order-client">Client Boutique</label><br>';
    wp_nonce_field('ib-native-order-client-' . $order->get_id(), 'ib_client_nonce', false);
    client_select($selected->ID ?? 0, 'ib_client_id', 'ib-native-order-client');
    echo '</p>';
});

add_action('woocommerce_process_shop_order_meta', function ($id) {
    $nonce = input($_POST, 'ib_client_nonce');
    if ($nonce === '' || !wp_verify_nonce($nonce, 'ib-native-order-client-' . $id)) {
        return;
    }
    $order = editable_client_order($id);
    $client_id = absint(input($_POST, 'ib_client_id'));
    if ($order && (!$client_id || client($client_id))) {
        set_order_client($order, $client_id, true);
    }
}, 45);
