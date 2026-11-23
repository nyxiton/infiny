<?php

namespace Infiny\Boutique;

defined('ABSPATH') || exit;

function order_board_url(): string {
    return url('commandes', ['affichage' => 'statuts']);
}

function order_board(): void {
    $labels = editable_statuses();
    $icons = [
        'wc-pending' => 'money-alt',
        'wc-processing' => 'update',
        'wc-on-hold' => 'clock',
        'wc-completed' => 'yes-alt',
        'wc-cancelled' => 'dismiss',
    ];
    $limit = 15;
    $script = add_query_arg('ver', filemtime(__DIR__ . '/order-board.js'), content_url('mu-plugins/boutique-admin/order-board.js'));
    ?>
    <div class="ib-toolbar">
        <?php button('Voir la liste', url('commandes')); ?>
        <?php if (current_user_can('publish_shop_orders')) {
            button('Nouvelle commande', url('commandes', ['nouveau' => 1]), true);
        } ?>
    </div>
    <p class="ib-board-hint">Glissez une commande vers une autre colonne, ou choisissez son nouveau statut sur la carte.</p>
    <p id="ib-board-feedback" class="ib-board-feedback" role="status" aria-live="polite"></p>
    <div id="ib-order-board" class="ib-board" tabindex="0" aria-label="Commandes par statut">
        <?php foreach ($labels as $status => $label) :
            $result = wc_get_orders([
                'type' => 'shop_order',
                'status' => $status,
                'limit' => $limit,
                'paginate' => true,
                'orderby' => 'date',
                'order' => 'DESC',
            ]);
            ?>
            <section class="ib-lane" data-status="<?php echo esc_attr($status); ?>" aria-labelledby="ib-lane-<?php echo esc_attr($status); ?>">
                <div class="ib-lane-header">
                    <span class="ib-lane-icon dashicons dashicons-<?php echo esc_attr($icons[$status]); ?>" aria-hidden="true"></span>
                    <h3 id="ib-lane-<?php echo esc_attr($status); ?>"><?php echo esc_html($label); ?></h3>
                    <span class="ib-lane-count" data-count="<?php echo esc_attr($result->total); ?>"><?php echo esc_html(number_format_i18n($result->total)); ?></span>
                </div>
                <div class="ib-lane-cards">
                    <?php foreach ($result->orders as $order) {
                        order_board_card($order, $status, $labels);
                    } ?>
                </div>
                <p class="ib-lane-empty" <?php if ($result->total) echo 'hidden'; ?>>Aucune commande</p>
                <a class="ib-lane-more" href="<?php echo esc_url(url('commandes', ['statut' => $status])); ?>"
                    <?php if ($result->total <= count($result->orders)) echo 'hidden'; ?>>Voir toutes les commandes</a>
            </section>
        <?php endforeach; ?>
    </div>
    <script defer src="<?php echo esc_url($script); ?>"></script>
    <?php
}

function order_board_card(\WC_Order $order, string $status, array $labels): void {
    $id = $order->get_id();
    $number = '#' . $order->get_order_number();
    $editable = current_user_can('edit_shop_order', $id);
    $date = $order->get_date_created();
    ?>
    <article class="ib-order-card" <?php if ($editable) echo 'draggable="true"'; ?>>
        <div class="ib-order-card-top">
            <a href="<?php echo esc_url(url('commandes', ['id' => $id])); ?>"><?php echo esc_html($number); ?></a>
            <strong><?php echo money($order->get_total(), ['currency' => $order->get_currency()]); ?></strong>
        </div>
        <div><?php echo esc_html(order_client_name($order) ?: 'Client non renseigné'); ?></div>
        <?php delivery_badge($order); ?>
        <?php if ($date) : ?><small><?php echo esc_html(wc_format_datetime($date, 'd/m/Y H:i')); ?></small><?php endif; ?>
        <?php if ($editable) : ?>
            <form class="ib-order-move" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="infiny_boutique_order">
                <input type="hidden" name="affichage" value="statuts">
                <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">
                <input type="hidden" name="statut_initial" value="<?php echo esc_attr($status); ?>">
                <?php wp_nonce_field('ib-order-' . $id, '_wpnonce', false); ?>
                <select name="statut" aria-label="<?php echo esc_attr('Statut de la commande ' . $number); ?>">
                    <?php foreach ($labels as $code => $label) : ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($status, $code); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button" aria-label="<?php echo esc_attr('Déplacer la commande ' . $number); ?>">Déplacer</button>
            </form>
        <?php endif; ?>
    </article>
    <?php
}
