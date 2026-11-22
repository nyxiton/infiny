<?php

namespace Infiny\Boutique;

defined('ABSPATH') || exit;

const FINANCE_COST_META = '_infiny_unit_purchase_cost';

add_action('woocommerce_before_order_item_object_save', function ($item) {
    if (!$item instanceof \WC_Order_Item_Product || $item->get_id() || $item->get_quantity() <= 0) {
        return;
    }
    $cost = catalogue_unit_cost($item);
    if ($cost !== null) {
        $item->update_meta_data(FINANCE_COST_META, ['amount' => $cost, 'currency' => get_woocommerce_currency()]);
    }
});

function catalogue_unit_cost(\WC_Order_Item_Product $item): ?float {
    $product = $item->get_product();
    if (!$product) {
        return null;
    }
    $cost = purchase_price($product);
    if ($product->is_type('variation') && !$product->meta_exists('_infiny_purchase_price')) {
        $variation_cost = $product->get_cogs_total_value();
        $parent = wc_get_product($product->get_parent_id());
        $cost = $variation_cost > 0 ? $variation_cost : ($parent ? purchase_price($parent) : $cost);
    }
    return is_numeric($cost) && (float) $cost >= 0 ? (float) $cost : null;
}

function finance_line_cost(\WC_Order_Item_Product $original, string $currency): array {
    $snapshot = $original->get_meta(FINANCE_COST_META);
    if (is_array($snapshot) && ($snapshot['currency'] ?? '') === $currency && is_numeric($snapshot['amount'] ?? null)) {
        return [(float) $snapshot['amount'], false];
    }
    $units = (float) $original->get_quantity();
    $native = $original->get_cogs_value();
    if ($units > 0 && $native > 0) {
        return [$native / $units, false];
    }
    $estimate = catalogue_unit_cost($original);
    return [$estimate, $estimate !== null];
}

function finance_cost_row(array &$report, \WC_Order_Item_Product $item, string $currency, ?\WC_Order_Item_Product $original = null): void {
    $quantity = (float) $item->get_quantity();
    if ($quantity === 0.0) {
        return;
    }
    [$unit, $estimated] = finance_line_cost($original ?? $item, $currency);
    if ($unit === null) {
        ++$report['missing'];
        return;
    }
    $report['cost'] += $unit * $quantity;
    $report['estimated'] += (int) $estimated;
}

function finance_refund_revenue(\WC_Order_Refund $refund): float {
    $amount = (float) $refund->get_amount()
        - abs((float) $refund->get_total_tax())
        - abs((float) $refund->get_shipping_total());
    foreach ($refund->get_items('fee') as $fee) {
        $amount -= abs((float) $fee->get_total());
    }
    return max(0.0, $amount);
}

function finance_report(\DateTimeImmutable $selected, string $currency): array {
    $first = $selected->modify('-11 months');
    $last = $selected->modify('+1 month')->modify('-1 second');
    $range = $first->getTimestamp() . '...' . $last->getTimestamp();
    $month = $selected->format('Y-m');
    $report = ['revenue' => [], 'cost' => 0.0, 'estimated' => 0, 'missing' => 0, 'expenses' => 0.0, 'reinvestment' => 0.0, 'other_currency' => false];

    for ($date = $first; $date <= $selected; $date = $date->modify('+1 month')) {
        $report['revenue'][$date->format('Y-m')] = 0.0;
    }

    foreach (dashboard_order_batches(['type' => 'shop_order', 'date_paid' => $range]) as $order) {
        $date = $order->get_date_paid();
        if (!$date) {
            continue;
        }
        $key = wp_date('Y-m', $date->getTimestamp());
        if ($order->get_currency() !== $currency) {
            $report['other_currency'] = true;
            continue;
        }
        foreach ($order->get_items() as $item) {
            $report['revenue'][$key] += (float) $item->get_total();
            if ($key === $month) {
                finance_cost_row($report, $item, $currency);
            }
        }
    }

    foreach (dashboard_order_batches(['type' => 'shop_order_refund', 'date_created' => $range]) as $refund) {
        if ($refund->get_currency() !== $currency) {
            $report['other_currency'] = true;
            continue;
        }
        $key = wp_date('Y-m', $refund->get_date_created()->getTimestamp());
        $report['revenue'][$key] -= finance_refund_revenue($refund);
        if ($key !== $month) {
            continue;
        }
        $parent = wc_get_order($refund->get_parent_id());
        foreach ($refund->get_items() as $item) {
            $original_id = absint($item->get_meta('_refunded_item_id'));
            $original = $original_id && $parent ? $parent->get_item($original_id) : null;
            finance_cost_row($report, $item, $currency, $original instanceof \WC_Order_Item_Product ? $original : null);
        }
    }

    foreach (expense_posts($month) as $expense) {
        $details = expense_details($expense->ID);
        if (!isset($details['amount'], $details['currency'])) {
            continue;
        }
        if ($details['currency'] !== $currency) {
            $report['other_currency'] = true;
            continue;
        }
        $key = !empty($details['reinvestment']) ? 'reinvestment' : 'expenses';
        $report[$key] += (float) $details['amount'];
    }
    return $report;
}

function finance(): void {
    $requested = input($_GET, 'mois');
    $selected = expense_date_is_valid($requested . '-01')
        ? new \DateTimeImmutable($requested . '-01 00:00:00', wp_timezone())
        : current_datetime()->modify('first day of this month')->setTime(0, 0);
    $month = $selected->format('Y-m');
    $currency = get_woocommerce_currency();
    $report = finance_report($selected, $currency);
    $revenue = $report['revenue'][$month];
    $product_profit = $report['missing'] ? null : $revenue - $report['cost'];
    $net_profit = $product_profit === null ? null : $product_profit - $report['expenses'];
    $metrics = [
        ['Chiffre d’affaires', $revenue],
        ['Prix d’achat des produits vendus', $report['missing'] ? null : $report['cost']],
        ['Bénéfice produit', $product_profit],
        ['Dépenses hors réinvestissement', $report['expenses']],
        ['Réinvestissements', $report['reinvestment']],
        ['Bénéfice net', $net_profit],
    ];
    ?>
    <div class="ib-dashboard ib-finance">
        <div class="ib-toolbar">
            <form method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <input type="hidden" name="boutique" value="1">
                <input type="hidden" name="vue" value="finances">
                <label for="ib-finance-month">Mois à afficher</label>
                <input id="ib-finance-month" type="month" name="mois" required value="<?php echo esc_attr($month); ?>">
                <button class="button button-primary">Afficher</button>
            </form>
            <?php button('Voir les dépenses', url('depenses', ['mois' => $month])); ?>
        </div>

        <div class="ib-dashboard-overview">
            <div class="ib-dashboard-metrics">
                <?php foreach ($metrics as [$label, $amount]) : ?>
                    <article class="ib-dashboard-card ib-metric<?php echo $label === 'Bénéfice net' ? ' ib-finance-net' : ''; ?>">
                        <h3><?php echo esc_html($label); ?></h3>
                        <strong><?php echo money($amount, ['currency' => $currency]); ?></strong>
                    </article>
                <?php endforeach; ?>
            </div>
            <section class="ib-dashboard-card">
                <h3>Chiffre d’affaires sur 12 mois</h3>
                <?php
                $series = [];
                foreach ($report['revenue'] as $key => $value) {
                    $series[substr($key, 5, 2) . '/' . substr($key, 2, 2)] = $value;
                }
                dashboard_chart($series, 12, $currency);
                ?>
                <p class="ib-dashboard-caption">Ventes de produits payées, hors taxes et livraison, moins les remboursements sur leur mois d’enregistrement.</p>
            </section>
        </div>
        <p class="ib-dashboard-caption">Bénéfice produit = chiffre d’affaires − prix d’achat des produits vendus. Bénéfice net = bénéfice produit − dépenses hors réinvestissement.</p>
        <?php if ($report['estimated']) : ?>
            <p class="ib-dashboard-caption">Le coût de certaines anciennes ventes ou de certains retours est estimé avec les prix d’achat actuels du catalogue.</p>
        <?php endif; ?>
        <?php if ($report['missing']) : ?>
            <p class="ib-dashboard-caption">Prix d’achat manquant pour <?php echo esc_html(number_format_i18n($report['missing'])); ?> ligne(s). Les coûts et bénéfices seront affichés quand ces prix seront renseignés.</p>
        <?php endif; ?>
        <?php if ($report['other_currency']) : ?>
            <p class="ib-dashboard-caption">Seuls les montants en <?php echo esc_html($currency); ?> sont comptabilisés.</p>
        <?php endif; ?>
    </div>
    <?php
}
