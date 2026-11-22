<?php

namespace Infiny\Boutique;

defined('ABSPATH') || exit;

const RESTOCK_TYPE = 'infiny_restock';
const RESTOCK_META = '_infiny_restock_details';

add_action('init', function () {
    register_post_type(RESTOCK_TYPE, ['public' => false, 'show_ui' => false]);
});

function restock_url(int $product_id = 0): string {
    return url('stock', ['historique' => 'reassorts'] + ($product_id ? ['produit' => $product_id] : []));
}

function record_restock(\WC_Product $product, $before): bool {
    $id = wp_insert_post(wp_slash([
        'post_type' => RESTOCK_TYPE,
        'post_status' => 'private',
        'post_title' => $product->get_name(),
        'post_parent' => $product->get_id(),
        'post_author' => get_current_user_id(),
        'meta_input' => [RESTOCK_META => [
            'before' => $before,
            'after' => $product->get_stock_quantity('edit'),
            'sku' => $product->get_sku(),
        ]],
    ]), true);
    return !is_wp_error($id) && $id > 0;
}

function restock_details(int $entry_id): ?array {
    $details = get_post_meta($entry_id, RESTOCK_META, true);
    if (!is_array($details) || !is_numeric($details['before'] ?? null) ||
        !is_numeric($details['after'] ?? null) || $details['after'] <= $details['before']) {
        return null;
    }
    return $details;
}

function restock_summary(\DateTimeImmutable $start, \DateTimeImmutable $end): array {
    $entries = get_posts([
        'post_type' => RESTOCK_TYPE,
        'post_status' => 'private',
        'posts_per_page' => -1,
        'orderby' => ['date' => 'DESC', 'ID' => 'DESC'],
        'date_query' => [[
            'after' => $start->format('Y-m-d H:i:s'),
            'before' => $end->format('Y-m-d H:i:s'),
            'inclusive' => true,
        ]],
    ]);
    $summary = ['count' => 0, 'units' => 0, 'products' => 0, 'latest' => null];
    $products = [];
    foreach ($entries as $entry) {
        $details = restock_details($entry->ID);
        if (!$details) {
            continue;
        }
        $quantity = $details['after'] - $details['before'];
        ++$summary['count'];
        $summary['units'] += $quantity;
        if ($entry->post_parent) {
            $products[$entry->post_parent] = true;
        }
        $summary['latest'] ??= [
            'name' => $entry->post_title,
            'quantity' => $quantity,
            'date' => get_post_time('d/m/Y H:i', false, $entry),
        ];
    }
    $summary['products'] = count($products);
    return $summary;
}

function restock_history(int $product_id): void {
    $args = [
        'post_type' => RESTOCK_TYPE,
        'post_status' => 'private',
        'posts_per_page' => 25,
        'paged' => max(1, absint(input($_GET, 'numero'))),
        'orderby' => ['date' => 'DESC', 'ID' => 'DESC'],
    ];
    if ($product_id) {
        $args['post_parent'] = $product_id;
    }
    $query = new \WP_Query($args);
    $product = $product_id ? wc_get_product($product_id) : null;
    echo '<div class="ib-toolbar">';
    button('Retour au stock', url('stock'));
    if ($product_id) {
        button('Voir tous les réassorts', restock_url());
    }
    echo '</div>';
    if ($product) {
        echo '<h2>' . esc_html($product->get_name()) . '</h2>';
    }
    if (!$query->posts) {
        echo '<p class="ib-empty">Aucun réassort enregistré.</p>';
        return;
    }
    ?>
    <div class="ib-table">
        <table>
            <thead><tr><th>Date</th><th>Produit</th><th>Référence</th><th>Ajouté</th><th>Avant</th><th>Après</th><th>Par</th></tr></thead>
            <tbody>
                <?php foreach ($query->posts as $entry) :
                    $details = restock_details($entry->ID);
                    if (!$details) {
                        continue;
                    }
                    $author = get_userdata($entry->post_author);
                    ?>
                    <tr>
                        <td><?php echo esc_html(get_post_time('d/m/Y H:i', false, $entry)); ?></td>
                        <td><?php echo esc_html($entry->post_title); ?></td>
                        <td><?php echo esc_html(($details['sku'] ?? '') ?: '—'); ?></td>
                        <td><strong class="ib-pill">+<?php echo esc_html(wc_format_localized_decimal($details['after'] - $details['before'])); ?></strong></td>
                        <td><?php echo esc_html(wc_format_localized_decimal($details['before'])); ?></td>
                        <td><?php echo esc_html(wc_format_localized_decimal($details['after'])); ?></td>
                        <td><?php echo esc_html($author ? $author->display_name : '—'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    pagination('stock', (int) $query->max_num_pages, ['historique' => 'reassorts', 'produit' => $product_id]);
}
