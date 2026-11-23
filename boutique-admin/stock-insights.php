<?php

namespace Infiny\Boutique;

defined('ABSPATH') || exit;

function stock_rankings(iterable $catalogue, array $sales): array {
    $best = $slow = $out = [];
    foreach ($catalogue as $product) {
        if (!$product->is_type(['simple', 'variable'])) {
            continue;
        }
        $id = $product->get_id();
        $units = $sales[$id]['units'] ?? 0;
        $entry = ['id' => $id, 'name' => $product->get_name(), 'units' => $units, 'stock' => stock_label($product)];
        if ($units > 0) {
            $best[] = $entry;
        }
        if (!$product->is_in_stock()) {
            $out[] = $entry;
        } elseif ($product->get_stock_status() === 'instock') {
            $slow[] = $entry;
        }
    }

    $descending = static fn ($a, $b) => ($b['units'] <=> $a['units']) ?: strnatcasecmp($a['name'], $b['name']);
    usort($best, $descending);
    usort($out, $descending);
    usort($slow, static fn ($a, $b) => ($a['units'] <=> $b['units']) ?: strnatcasecmp($a['name'], $b['name']));
    return ['best' => $best, 'slow' => $slow, 'out' => $out];
}

function stock_insights(): void {
    $periods = [7 => '7 jours', 30 => '30 jours', 90 => '90 jours'];
    $days = absint(input($_GET, 'periode_stock'));
    $days = isset($periods[$days]) ? $days : 30;
    $now = current_datetime();
    $start = $now->setTime(0, 0)->modify('-' . ($days - 1) . ' days');
    $sales = dashboard_sales($start, $now, $days, get_woocommerce_currency());
    $rankings = stock_rankings(product_batches(['status' => 'publish']), $sales['products']);
    $out_count = count($rankings['out']);
    ?>
    <section id="ib-stock-insights" class="ib-stock-insights" aria-labelledby="ib-stock-insights-heading">
        <div class="ib-dashboard-section-header">
            <h2 id="ib-stock-insights-heading">Indicateurs de stock</h2>
            <nav class="ib-periods" aria-label="Période des ventes">
                <?php foreach ($periods as $period => $label) : ?>
                    <a href="<?php echo esc_url(url('stock', ['periode_stock' => $period]) . '#ib-stock-insights'); ?>"
                        <?php if ($period === $days) echo 'aria-current="true"'; ?>><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
        <p class="ib-dashboard-caption">Quantités vendues nettes des retours sur la période. Le stock affiché est celui d’aujourd’hui.</p>
        <div class="ib-stock-insights-grid">
            <section class="ib-dashboard-card">
                <h3>Meilleures ventes</h3>
                <?php stock_ranking_list($rankings['best'], 'Aucune vente sur cette période.'); ?>
            </section>
            <section class="ib-dashboard-card">
                <h3>Produits les moins vendus</h3>
                <p class="ib-dashboard-caption">Produits en stock, y compris ceux sans vente.</p>
                <?php stock_ranking_list($rankings['slow'], 'Aucun produit en stock.'); ?>
            </section>
            <section class="ib-dashboard-card">
                <h3>Ruptures de stock <span class="ib-pill<?php echo $out_count ? ' ib-alert' : ''; ?>"><?php echo esc_html(number_format_i18n($out_count)); ?></span></h3>
                <?php stock_ranking_list($rankings['out'], 'Aucune rupture actuellement.'); ?>
                <?php if ($out_count) : ?>
                    <p><?php button('Voir toutes les ruptures', url('stock', ['rupture' => 1])); ?></p>
                <?php endif; ?>
            </section>
        </div>
    </section>
    <?php
}

function stock_ranking_list(array $entries, string $empty): void {
    if (!$entries) {
        echo '<p class="ib-dashboard-caption">' . esc_html($empty) . '</p>';
        return;
    }
    ?>
    <ol class="ib-dashboard-list ib-stock-ranking">
        <?php foreach (array_slice($entries, 0, 5) as $entry) : ?>
            <li>
                <div class="ib-stock-ranking-product">
                    <?php if (current_user_can('edit_product', $entry['id'])) : ?>
                        <a href="<?php echo esc_url(url('stock', ['id' => $entry['id']])); ?>"><?php echo esc_html($entry['name']); ?></a>
                    <?php else : ?>
                        <span><?php echo esc_html($entry['name']); ?></span>
                    <?php endif; ?>
                    <small>Stock <?php echo esc_html($entry['stock']); ?></small>
                </div>
                <strong><?php echo esc_html(number_format_i18n($entry['units']) . ' u.'); ?></strong>
            </li>
        <?php endforeach; ?>
    </ol>
    <?php
}
