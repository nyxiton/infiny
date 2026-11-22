<?php

namespace Infiny\Boutique;

defined('ABSPATH') || exit;

function dashboard(): void {
    $periods = [1 => 'Aujourd’hui', 7 => '7 jours', 30 => '30 jours'];
    $days = absint(input($_GET, 'periode'));
    $days = isset($periods[$days]) ? $days : 7;
    $now = current_datetime();
    $start = $now->setTime(0, 0)->modify('-' . ($days - 1) . ' days');
    $previous_start = $start->modify("-$days days");
    $previous_end = $now->modify("-$days days");
    $currency = get_woocommerce_currency();
    $current = dashboard_sales($start, $now, $days, $currency);
    $previous = dashboard_sales($previous_start, $previous_end, $days, $currency);
    $stock = dashboard_stock();
    $restocks = restock_summary($start, $now);
    $pending = wc_orders_count('pending');
    $hold = wc_orders_count('on-hold');
    $processing = wc_orders_count('processing');

    ?>
    <div class="ib-dashboard">
        <div class="ib-dashboard-toolbar">
            <div>
                <nav class="ib-periods" aria-label="Période des ventes">
                    <?php foreach ($periods as $value => $label) : ?>
                        <a href="<?php echo esc_url(url('accueil', ['periode' => $value])); ?>"
                            <?php if ($days === $value) echo 'aria-current="true"'; ?>><?php echo esc_html($label); ?></a>
                    <?php endforeach; ?>
                </nav>
                <p class="ib-dashboard-dates"><time datetime="<?php echo esc_attr($start->format('Y-m-d')); ?>"><?php echo esc_html(wp_date('j M Y', $start->getTimestamp())); ?></time>
                    <?php if ($start->format('Y-m-d') !== $now->format('Y-m-d')) : ?>
                        – <time datetime="<?php echo esc_attr($now->format('Y-m-d')); ?>"><?php echo esc_html(wp_date('j M Y', $now->getTimestamp())); ?></time>
                    <?php endif; ?>
                </p>
            </div>
            <div class="ib-dashboard-actions">
                <?php if (current_user_can('publish_shop_orders')) {
                    button('Nouvelle commande', url('commandes', ['nouveau' => 1]), true);
                }
                if (current_user_can('publish_products')) {
                    button('Ajouter un produit', url('produits', ['nouveau' => 1]));
                } ?>
            </div>
        </div>
        <?php
        $metrics = [
            ['Ventes nettes', 'chart-line', $current['net'], $previous['net'], true, false],
            ['Ventes payées', 'cart', $current['orders'], $previous['orders'], false, false],
            ['Panier moyen', 'money-alt', $current['average'], $previous['average'], true, false],
            ['Remboursements', 'undo', $current['refunds'], $previous['refunds'], true, true],
        ];
        ?>
        <div class="ib-dashboard-overview">
            <div class="ib-dashboard-metrics">
                <?php foreach ($metrics as [$label, $icon, $value, $last, $is_money, $lower_is_better]) :
                    $change = $last > 0 ? round(100 * ($value - $last) / $last) : null;
                    $tone = '';
                    if ($change) {
                        $tone = (($change < 0) === $lower_is_better) ? 'ib-trend-positive' : 'ib-trend-negative';
                    }
                    ?>
                    <article class="ib-dashboard-card ib-metric">
                        <span class="dashicons dashicons-<?php echo esc_attr($icon); ?>" aria-hidden="true"></span>
                        <h3><?php echo esc_html($label); ?></h3>
                        <strong><?php echo $is_money ? money($value, ['currency' => $currency]) : esc_html(number_format_i18n($value)); ?></strong>
                        <small class="<?php echo esc_attr($tone); ?>"><?php
                            if ($change !== null) {
                                echo esc_html(($change > 0 ? '+' : '') . number_format_i18n($change) . ' % vs période précédente');
                            } else {
                                echo esc_html($last < 0 ? 'Comparaison en pourcentage indisponible' : 'Période précédente à zéro');
                            }
                        ?></small>
                    </article>
                <?php endforeach; ?>
            </div>
            <section class="ib-dashboard-card ib-dashboard-main">
                <h3>Évolution des ventes nettes</h3>
                <?php dashboard_chart($current['series'], $days, $currency); ?>
                <p class="ib-dashboard-caption">Paiements WooCommerce, moins les remboursements enregistrés à leur date. Montants TTC, livraison comprise.</p>
            </section>
        </div>
        <div class="ib-dashboard-layout">
            <section class="ib-dashboard-card ib-dashboard-recent">
                <div class="ib-dashboard-section-header">
                    <h3>Dernières commandes</h3>
                    <?php button('Toutes les commandes', url('commandes')); ?>
                </div>
                <?php order_table(wc_get_orders(['limit' => 5, 'orderby' => 'date', 'order' => 'DESC'])); ?>
            </section>
            <section class="ib-dashboard-card">
                <h3>À traiter</h3>
                <div class="ib-dashboard-tasks">
                    <?php dashboard_task('En attente de paiement', $pending, url('commandes', ['statut' => 'wc-pending']));
                    dashboard_task('Commandes en attente', $hold, url('commandes', ['statut' => 'wc-on-hold']));
                    dashboard_task('Commandes en cours', $processing, url('commandes', ['statut' => 'wc-processing']));
                    dashboard_task('Produits en rupture', $stock['out'], url('stock', ['rupture' => 1]), 'critical');
                    dashboard_task('Produits en stock faible', $stock['low'], url('stock', ['faible' => 1]), 'warning'); ?>
                </div>
            </section>
            <section class="ib-dashboard-card">
                <h3>Produits les plus vendus</h3>
                <?php if ($current['products']) : ?>
                    <ol class="ib-dashboard-list">
                        <?php foreach (array_slice($current['products'], 0, 5, true) as $id => $item) : ?>
                            <li>
                                <?php if ($id && current_user_can('edit_product', $id) && get_post_type($id) === 'product') : ?>
                                    <a href="<?php echo esc_url(url('produits', ['id' => $id])); ?>"><?php echo esc_html($item['name']); ?></a>
                                <?php else : ?>
                                    <span><?php echo esc_html($item['name']); ?></span>
                                <?php endif; ?>
                                <strong><?php echo esc_html(number_format_i18n($item['units'])); ?> u.</strong>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php else : ?>
                    <p class="ib-dashboard-caption">Aucun article vendu sur cette période.</p>
                <?php endif; ?>
            </section>
            <section class="ib-dashboard-card">
                <h3>Paiements enregistrés</h3>
                <?php if ($current['payments']) : ?>
                    <ul class="ib-dashboard-list">
                        <?php foreach ($current['payments'] as $payment) : ?>
                            <li><span><?php echo esc_html($payment['label']); ?></span><strong><?php echo money($payment['total'], ['currency' => $currency]); ?></strong></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="ib-dashboard-caption">Montants avant remboursements, selon le moyen saisi sur la commande. Le panier moyen utilise aussi les montants avant remboursements.</p>
                <?php else : ?>
                    <p class="ib-dashboard-caption">Aucun paiement enregistré sur cette période.</p>
                <?php endif; ?>
            </section>
            <section class="ib-dashboard-card">
                <div class="ib-dashboard-section-header">
                    <h3>Réassorts sur la période</h3>
                    <?php button('Voir l’historique', restock_url()); ?>
                </div>
                <dl class="ib-dashboard-list ib-restock-metrics">
                    <div><dt>Réassorts</dt><dd><?php echo esc_html(number_format_i18n($restocks['count'])); ?></dd></div>
                    <div><dt>Unités ajoutées</dt><dd><?php echo esc_html(wc_format_localized_decimal($restocks['units'])); ?></dd></div>
                    <div><dt>Produits réassortis</dt><dd><?php echo esc_html(number_format_i18n($restocks['products'])); ?></dd></div>
                </dl>
                <?php if ($latest = $restocks['latest']) : ?>
                    <p class="ib-dashboard-caption">Dernier réassort : <?php echo esc_html($latest['name']); ?>
                        (+<?php echo esc_html(wc_format_localized_decimal($latest['quantity'])); ?>) · <?php echo esc_html($latest['date']); ?></p>
                <?php else : ?>
                    <p class="ib-dashboard-caption">Aucun réassort enregistré sur cette période.</p>
                <?php endif; ?>
            </section>
        </div>
        <?php if ($current['other_currency']) : ?>
            <p class="ib-dashboard-caption">Les indicateurs de ventes sont calculés en <?php echo esc_html($currency); ?>. Les transactions dans les autres devises sont exclues.</p>
        <?php endif; ?>
    </div>
    <?php
}

function dashboard_task(string $label, int $count, string $link, string $tone = ''): void {
    printf(
        '<a href="%s"><span>%s</span><strong class="%s">%s</strong></a>',
        esc_url($link),
        esc_html($label),
        esc_attr($tone),
        esc_html(number_format_i18n($count))
    );
}

function dashboard_order_batches(array $query): iterable {
    $query += ['limit' => 100, 'orderby' => 'ID', 'order' => 'ASC'];
    for ($page = 1; ; ++$page) {
        $batch = wc_get_orders($query + ['paged' => $page]);
        yield from $batch;
        if (count($batch) < $query['limit']) {
            break;
        }
    }
}

function dashboard_sales(\DateTimeImmutable $start, \DateTimeImmutable $end, int $days, string $currency): array {
    $range = $start->getTimestamp() . '...' . $end->getTimestamp();
    $result = [
        'gross' => 0.0, 'refunds' => 0.0, 'orders' => 0,
        'products' => [], 'payments' => [], 'series' => [], 'other_currency' => false,
    ];

    if ($days === 1) {
        for ($hour = 0; $hour < 24; $hour += 4) {
            $result['series'][sprintf('%02d h', $hour)] = 0.0;
        }
    } else {
        for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
            $result['series'][$date->format('d/m')] = 0.0;
        }
    }

    foreach (dashboard_order_batches(['type' => 'shop_order', 'date_paid' => $range]) as $order) {
        if ($order->get_currency() !== $currency) {
            $result['other_currency'] = true;
            continue;
        }
        $total = (float) $order->get_total();
        $result['gross'] += $total;
        ++$result['orders'];
        dashboard_add_to_series($result['series'], $order->get_date_paid(), $days, $total);
        dashboard_add_units($result['products'], $order);
        $method = $order->get_payment_method();
        $result['payments'][$method] ??= ['label' => $order->get_payment_method_title() ?: ($method ?: 'Non renseigné'), 'total' => 0.0];
        $result['payments'][$method]['total'] += $total;
    }

    foreach (dashboard_order_batches(['type' => 'shop_order_refund', 'date_created' => $range]) as $refund) {
        if ($refund->get_currency() !== $currency) {
            $result['other_currency'] = true;
            continue;
        }
        $amount = (float) $refund->get_amount();
        $result['refunds'] += $amount;
        dashboard_add_to_series($result['series'], $refund->get_date_created(), $days, -$amount);
        dashboard_add_units($result['products'], $refund);
    }

    $result['net'] = $result['gross'] - $result['refunds'];
    $result['average'] = $result['orders'] ? $result['gross'] / $result['orders'] : 0.0;
    $result['products'] = array_filter($result['products'], static fn ($item) => $item['units'] > 0);
    uasort($result['products'], static fn ($a, $b) => $b['units'] <=> $a['units']);
    uasort($result['payments'], static fn ($a, $b) => $b['total'] <=> $a['total']);
    return $result;
}

function dashboard_add_units(array &$products, \WC_Abstract_Order $order): void {
    foreach ($order->get_items() as $item) {
        $id = $item->get_product_id();
        if (!$id) {
            continue;
        }
        $products[$id] ??= ['name' => $item->get_name(), 'units' => 0];
        $products[$id]['units'] += (int) $item->get_quantity();
    }
}

function dashboard_add_to_series(array &$series, ?\WC_DateTime $date, int $days, float $amount): void {
    if ($date) {
        $timestamp = $date->getTimestamp();
        $label = $days === 1 ? sprintf('%02d h', intdiv((int) wp_date('G', $timestamp), 4) * 4) : wp_date('d/m', $timestamp);
        if (isset($series[$label])) {
            $series[$label] += $amount;
        }
    }
}

function dashboard_stock(): array {
    if ($cached = get_transient('infiny_boutique_stock_alerts')) {
        return $cached;
    }
    $counts = ['out' => 0, 'low' => 0];
    foreach (product_batches(['status' => 'publish']) as $product) {
        if (!$product->is_in_stock()) {
            ++$counts['out'];
        } elseif (is_low_stock($product)) {
            ++$counts['low'];
        }
    }
    set_transient('infiny_boutique_stock_alerts', $counts, MINUTE_IN_SECONDS);
    return $counts;
}

function product_batches(array $args): iterable {
    $args += ['limit' => 100, 'orderby' => 'ID', 'order' => 'ASC'];
    for ($page = 1; ; ++$page) {
        $batch = wc_get_products($args + ['page' => $page]);
        yield from $batch;
        if (count($batch) < $args['limit']) {
            break;
        }
    }
}

function is_low_stock(\WC_Product $product): bool {
    $quantity = $product->get_stock_quantity();
    return $product->is_in_stock() && $product->managing_stock() && $quantity > 0 && $quantity <= wc_get_low_stock_amount($product);
}

function dashboard_chart(array $series, int $days, string $currency): void {
    $high = max(0, max($series));
    $low = abs(min(0, min($series)));
    if (!$high && !$low) {
        echo '<p class="ib-dashboard-caption">Aucun mouvement de vente sur cette période.</p>';
        return;
    }
    $total = $high + $low;
    $baseline = 100 * $high / $total;
    ?>
    <div class="ib-sales-chart" style="--chart-baseline:<?php echo esc_attr($baseline); ?>%" aria-hidden="true">
        <?php $index = 0;
        foreach ($series as $label => $value) :
            $height = $value === 0.0 ? 0 : 100 * abs($value) / $total;
            $top = $value < 0 ? $baseline : $baseline - $height;
            $show_label = $days !== 30 || $index % 5 === 0 || $index === 29;
            ++$index;
            ?>
            <div class="ib-chart-column">
                <div class="ib-chart-plot">
                    <div class="ib-chart-bar<?php echo $value < 0 ? ' ib-chart-refund' : ''; ?>"
                        style="--bar-size:<?php echo esc_attr($height); ?>%;--bar-top:<?php echo esc_attr($top); ?>%"
                        title="<?php echo esc_attr($label . ' · ' . html_entity_decode(wp_strip_all_tags(money($value, ['currency' => $currency])))); ?>"></div>
                </div>
                <span><?php echo $show_label ? esc_html($label) : ''; ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <details class="ib-chart-values">
        <summary>Voir les montants par période</summary>
        <dl class="ib-dashboard-list">
            <?php foreach ($series as $label => $value) : ?>
                <div><dt><?php echo esc_html($label); ?></dt><dd><?php echo money($value, ['currency' => $currency]); ?></dd></div>
            <?php endforeach; ?>
        </dl>
    </details>
    <?php
}
