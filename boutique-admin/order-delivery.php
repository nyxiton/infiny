<?php

namespace Infiny\Boutique;

defined('ABSPATH') || exit;

const ORDER_DELIVERY = '_infiny_delivery';

function is_delivery_order(\WC_Order $order): bool {
    return $order->get_meta(ORDER_DELIVERY) === 'yes';
}

function delivery_badge(\WC_Order $order): void {
    if (is_delivery_order($order)) {
        echo '<span class="ib-pill ib-delivery-badge">Livraison</span>';
    }
}

function set_order_delivery(\WC_Order $order, bool $delivery): void {
    if ($delivery) {
        $order->update_meta_data(ORDER_DELIVERY, 'yes');
    } else {
        $order->delete_meta_data(ORDER_DELIVERY);
    }
    $order->save_meta_data();
}

function delivery_checkbox(\WC_Order $order): void {
    ?>
    <label class="ib-delivery-checkbox">
        <input type="checkbox" name="infiny_livraison" value="1"<?php checked(is_delivery_order($order)); ?>>
        Livraison
    </label>
    <?php
}

function order_delivery_form(\WC_Order $order): void {
    if (!current_user_can('edit_shop_order', $order->get_id())) {
        delivery_badge($order);
        return;
    }
    ?>
    <form class="ib-toolbar" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="infiny_boutique_order_delivery">
        <input type="hidden" name="id" value="<?php echo esc_attr($order->get_id()); ?>">
        <?php wp_nonce_field('ib-order-delivery-' . $order->get_id()); ?>
        <?php delivery_checkbox($order); ?>
        <button class="button">Enregistrer la livraison</button>
    </form>
    <?php
}

add_action('admin_post_infiny_boutique_order_delivery', function () {
    $id = absint(input($_POST, 'id'));
    check_admin_referer('ib-order-delivery-' . $id);
    $order = editable_order($id);
    if (!$order) {
        wp_die('Accès indisponible.', '', ['response' => 403]);
    }

    set_order_delivery($order, input($_POST, 'infiny_livraison') === '1');
    finish(url('commandes', ['id' => $id]));
});

add_action('woocommerce_admin_order_data_after_shipping_address', function ($order) {
    if (!$order instanceof \WC_Order || !can_manage() || !current_user_can('edit_shop_order', $order->get_id())) {
        return;
    }
    echo '<p>';
    wp_nonce_field('ib-native-order-delivery-' . $order->get_id(), 'ib_delivery_nonce', false);
    delivery_checkbox($order);
    echo '</p>';
});

add_action('woocommerce_process_shop_order_meta', function ($id) {
    $nonce = input($_POST, 'ib_delivery_nonce');
    if ($nonce !== '' && wp_verify_nonce($nonce, 'ib-native-order-delivery-' . $id)) {
        $order = editable_order($id);
        if ($order) {
            set_order_delivery($order, input($_POST, 'infiny_livraison') === '1');
        }
    }
}, 46);
