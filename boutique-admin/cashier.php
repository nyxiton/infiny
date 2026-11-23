<?php

namespace Infiny\Boutique;

defined('ABSPATH') || exit;

const CASHIER_PAYMENT = '_infiny_cashier_payment';
const CASHIER_METHODS = [
    'especes' => 'Espèces',
    'mvola' => 'MVola',
    'orange' => 'Orange Money',
    'airtel' => 'Airtel Money',
];

function cashier_can_take_payment(\WC_Order $order): bool {
    return !$order->get_date_paid() && !$order->is_paid() && $order->has_status(['pending', 'on-hold', 'failed']);
}

function cashier(): void {
    $receipt = absint(input($_GET, 'ticket'));
    if ($receipt) {
        cashier_receipt($receipt);
        return;
    }
    $order_id = absint(input($_GET, 'commande'));
    if (input($_GET, 'session') === '1') {
        cashier_sessions($order_id);
        return;
    }
    $order = $order_id ? editable_order($order_id) : null;
    if ($order_id && (!$order || !cashier_can_take_payment($order))) {
        if ($order && $order->get_meta(CASHIER_PAYMENT) && $order->get_date_paid()) {
            cashier_receipt($order_id);
            return;
        }
        echo '<p class="ib-empty">Cette commande ne peut pas être encaissée.</p>';
        button('Retour aux commandes', url('commandes'));
        return;
    }
    if (!$order && !current_user_can('publish_shop_orders')) {
        echo '<p class="ib-empty">Vous n’avez pas l’autorisation de créer une vente.</p>';
        return;
    }
    $currency = $order ? $order->get_currency() : get_woocommerce_currency();
    $session = cashier_active_session();
    if (!$session) {
        echo '<p class="ib-empty">La caisse est fermée. Ouvrez-la pour enregistrer un encaissement.</p>';
        button('Ouvrir la caisse', cashier_session_url($order_id), true);
        cashier_daily_totals($currency, $order_id);
        return;
    }
    if ($currency !== $session['currency']) {
        echo '<p class="ib-empty">La devise de cette commande diffère de celle de la caisse ouverte.</p>';
        button('Retour aux commandes', url('commandes'));
        return;
    }
    $asset = __DIR__ . '/cashier.js';
    $script = add_query_arg('ver', filemtime($asset), content_url('mu-plugins/boutique-admin/cashier.js'));
    ?>
    <div class="ib-cashier-top">
        <?php button('Clôturer la caisse', cashier_session_url($order_id)); ?>
        <?php button('Voir les encaissements', '#ib-cashier-history'); ?>
        <?php button('Commandes à encaisser', url('commandes', ['statut' => 'wc-pending'])); ?>
        <?php if ($order) {
            button('Voir la commande', url('commandes', ['id' => $order_id]));
            button('Nouvelle vente', url('caisse'));
        } ?>
    </div>
    <div id="ib-cashier" class="ib-cashier"
        data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
        data-currency="<?php echo esc_attr($currency); ?>"
        data-decimals="<?php echo esc_attr(wc_get_price_decimals()); ?>"
        data-order="<?php echo esc_attr($order_id); ?>"
        data-session="<?php echo esc_attr($session['id']); ?>"
        data-token="<?php echo esc_attr(wp_generate_uuid4()); ?>"
        data-nonce="<?php echo esc_attr(wp_create_nonce('infiny-cashier')); ?>">
        <?php if (!$order) : ?>
            <section class="ib-cashier-catalog" aria-labelledby="ib-cashier-products">
                <h2 id="ib-cashier-products">Articles</h2>
                <form id="ib-cashier-search" class="ib-cashier-search" role="search">
                    <label for="ib-cashier-query">Scanner ou chercher un article</label>
                    <div>
                        <input id="ib-cashier-query" type="search" name="q" placeholder="Nom, référence ou code-barres" autocomplete="off">
                        <button class="button">Rechercher</button>
                    </div>
                </form>
                <p id="ib-cashier-search-feedback" class="ib-cashier-muted" role="status"></p>
                <div id="ib-cashier-results" class="ib-cashier-products"></div>
            </section>
        <?php else : ?>
            <section class="ib-cashier-catalog">
                <h2>Commande #<?php echo esc_html($order->get_order_number()); ?></h2>
                <?php if ($name = order_client_name($order)) : ?><p><?php echo esc_html($name); ?></p><?php endif; ?>
                <ul class="ib-cashier-lines">
                    <?php foreach ($order->get_items() as $item) : ?>
                        <li><span><?php echo esc_html($item->get_name() . ' × ' . $item->get_quantity()); ?></span>
                            <strong><?php echo money($order->get_line_total($item, true), ['currency' => $currency]); ?></strong></li>
                    <?php endforeach; ?>
                </ul>
                <?php if (is_delivery_order($order)) : ?><?php delivery_badge($order); ?><?php endif; ?>
            </section>
        <?php endif; ?>
        <section class="ib-cashier-payment" aria-labelledby="ib-cashier-basket">
            <h2 id="ib-cashier-basket"><?php echo $order ? 'Encaissement' : 'Panier'; ?></h2>
            <?php if (!$order) : ?>
                <ul class="ib-cashier-lines" id="ib-cashier-lines"></ul>
                <p class="ib-cashier-muted" id="ib-cashier-empty">Ajoutez un article pour commencer.</p>
            <?php endif; ?>
            <p class="ib-cashier-total">
                <span>Total à payer</span>
                <strong id="ib-cashier-total" data-total="<?php echo esc_attr($order ? $order->get_total() : '0'); ?>">
                    <?php echo money($order ? $order->get_total() : 0, ['currency' => $currency]); ?>
                </strong>
            </p>
            <form id="ib-cashier-pay">
                <?php if (!$order) : ?>
                    <details class="ib-cashier-client">
                        <summary>Associer un client</summary>
                        <label for="ib-cashier-client">Client enregistré</label>
                        <?php client_select(0, 'client', 'ib-cashier-client'); ?>
                    </details>
                <?php endif; ?>
                <fieldset class="ib-cashier-methods">
                    <legend>Moyen de paiement</legend>
                    <?php foreach (CASHIER_METHODS as $key => $label) : ?>
                        <label>
                            <input type="radio" name="methode" value="<?php echo esc_attr($key); ?>" <?php checked($key, 'especes'); ?>>
                            <span><?php echo esc_html($label); ?></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <label id="ib-cashier-cash">Montant reçu
                    <input name="recu" type="text" inputmode="decimal" autocomplete="off" placeholder="Saisir le montant">
                </label>
                <button type="button" class="ib-cashier-exact button">Montant exact</button>
                <div id="ib-cashier-change" class="ib-cashier-change" aria-live="polite" hidden></div>
                <label id="ib-cashier-mobile" hidden>Référence du paiement reçu
                    <input name="transaction" type="text" maxlength="100" autocomplete="off">
                </label>
                <p id="ib-cashier-feedback" class="ib-cashier-error" role="status" aria-live="polite"></p>
                <button id="ib-cashier-confirm" class="button button-primary ib-cashier-confirm" disabled>Encaisser</button>
            </form>
        </section>
    </div>
    <?php cashier_daily_totals($currency, $order_id); ?>
    <script defer src="<?php echo esc_url($script); ?>"></script>
    <?php
}

function cashier_daily_totals(string $currency, int $order_id = 0): void {
    $now = current_datetime();
    $date = input($_GET, 'date_caisse');
    $day = expense_date_is_valid($date) ? new \DateTimeImmutable($date, wp_timezone()) : $now->setTime(0, 0);
    $range = $day->getTimestamp() . '...' . $day->modify('+1 day')->modify('-1 second')->getTimestamp();
    $totals = [];
    $entries = [];
    foreach (dashboard_order_batches(['type' => 'shop_order', 'date_paid' => $range]) as $order) {
        $payment = $order->get_meta(CASHIER_PAYMENT);
        if (is_array($payment) && isset(CASHIER_METHODS[$payment['method'] ?? '']) && $order->get_currency() === $currency) {
            $key = $payment['method'];
            $totals[$key] = ($totals[$key] ?? 0) + (float) $order->get_total();
            if (current_user_can('edit_shop_order', $order->get_id())) {
                $entries[] = $order;
            }
        }
    }
    usort($entries, static fn ($a, $b) => [$b->get_date_paid()->getTimestamp(), $b->get_id()] <=> [$a->get_date_paid()->getTimestamp(), $a->get_id()]);
    ?>
    <section id="ib-cashier-history" class="ib-dashboard-card ib-cashier-daily">
        <h2><?php echo $day->format('Y-m-d') === $now->format('Y-m-d') ? 'Encaissements du jour' : 'Encaissements du ' . esc_html($day->format('d/m/Y')); ?></h2>
        <div class="ib-toolbar">
            <form method="get" action="<?php echo esc_url(home_url('/') . '#ib-cashier-history'); ?>">
                <input type="hidden" name="boutique" value="1">
                <input type="hidden" name="vue" value="caisse">
                <?php if ($order_id) : ?><input type="hidden" name="commande" value="<?php echo (int) $order_id; ?>"><?php endif; ?>
                <label for="ib-cashier-date">Date des encaissements</label>
                <input id="ib-cashier-date" name="date_caisse" type="date" value="<?php echo esc_attr($day->format('Y-m-d')); ?>">
                <button class="button">Afficher</button>
            </form>
        </div>
        <?php if ($totals) : ?>
            <dl class="ib-dashboard-list">
                <?php foreach ($totals as $method => $total) : ?>
                    <div><dt><?php echo esc_html(CASHIER_METHODS[$method]); ?></dt><dd><?php echo money($total, ['currency' => $currency]); ?></dd></div>
                <?php endforeach; ?>
                <div><dt><strong>Total encaissé en caisse</strong></dt><dd><strong><?php echo money(array_sum($totals), ['currency' => $currency]); ?></strong></dd></div>
            </dl>
            <?php if ($entries) : ?>
                <div class="ib-table ib-cashier-history-table">
                    <table>
                        <thead><tr><th>Heure</th><th>Commande</th><th>Paiement</th><th>Montant</th><th>Ticket</th></tr></thead>
                        <tbody>
                            <?php foreach ($entries as $entry) :
                                $ticket = url('caisse', ['ticket' => $entry->get_id()]);
                                $payment = $entry->get_meta(CASHIER_PAYMENT);
                                ?>
                                <tr>
                                    <td><?php echo esc_html(wc_format_datetime($entry->get_date_paid(), 'H:i')); ?></td>
                                    <td><a href="<?php echo esc_url($ticket); ?>">#<?php echo esc_html($entry->get_order_number()); ?></a></td>
                                    <td><?php echo esc_html(CASHIER_METHODS[$payment['method']]); ?></td>
                                    <td><?php echo money($entry->get_total(), ['currency' => $currency]); ?></td>
                                    <td><?php button('Voir le ticket', $ticket); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <p class="ib-cashier-muted">Aucun encaissement enregistré à cette date.</p>
        <?php endif; ?>
    </section>
    <?php
}

function cashier_receipt(int $id): void {
    $order = editable_order($id);
    $payment = $order ? $order->get_meta(CASHIER_PAYMENT) : null;
    if (!$order || !$order->get_date_paid() || !is_array($payment)) {
        echo '<p class="ib-empty">Ticket introuvable.</p>';
        button('Retour à la caisse', url('caisse'));
        return;
    }
    $currency = ['currency' => $order->get_currency()];
    ?>
    <div class="ib-cashier-top ib-no-print">
        <?php button('Nouvelle vente', url('caisse'), true); ?>
        <?php button('Télécharger le ticket PDF', cashier_ticket_url($id)); ?>
        <?php button('Voir la commande', url('commandes', ['id' => $id])); ?>
        <button type="button" class="button" onclick="window.print()">Imprimer le ticket</button>
    </div>
    <div class="ib-cashier-receipt-layout">
        <section class="ib-cashier-receipt">
            <h2><?php echo esc_html(get_bloginfo('name')); ?></h2>
            <p>Ticket #<?php echo esc_html($order->get_order_number()); ?> · <?php echo esc_html(wc_format_datetime($order->get_date_paid(), 'd/m/Y H:i')); ?></p>
            <ul class="ib-cashier-lines">
                <?php foreach ($order->get_items() as $item) : ?>
                    <li><span><?php echo esc_html($item->get_name() . ' × ' . $item->get_quantity()); ?></span>
                        <strong><?php echo money($order->get_line_total($item, true), $currency); ?></strong></li>
                <?php endforeach; ?>
            </ul>
            <p class="ib-cashier-total"><span>Total payé</span><strong><?php echo money($order->get_total(), $currency); ?></strong></p>
            <p><?php echo esc_html(CASHIER_METHODS[$payment['method']] ?? $order->get_payment_method_title()); ?>
                <?php if (!empty($payment['transaction'])) : ?> · <?php echo esc_html($payment['transaction']); ?><?php endif; ?></p>
            <?php if (($payment['method'] ?? '') === 'especes') : ?>
                <p>Montant reçu <?php echo money($payment['received'], $currency); ?> · Monnaie <?php echo money($payment['change'], $currency); ?></p>
            <?php endif; ?>
        </section>
        <?php cashier_ticket_preview($id); ?>
    </div>
    <?php
}

function cashier_access(): void {
    if (!can_manage() || !class_exists('WooCommerce') || !check_ajax_referer('infiny-cashier', 'nonce', false)) {
        wp_send_json_error(['message' => 'Accès à la caisse indisponible. Rechargez la page.'], 403);
    }
}

function cashier_sells_product($product): bool {
    return $product instanceof \WC_Product && $product->is_type(['simple', 'variation'])
        && $product->get_status() === 'publish' && $product->get_price() !== ''
        && (!$product->get_parent_id() || get_post_status($product->get_parent_id()) === 'publish');
}

function cashier_available(\WC_Product $product, int $exclude_order = 0): ?float {
    $managed = $product->get_stock_managed_by_id();
    $owner = $managed === $product->get_id() ? $product : wc_get_product($managed);
    if (!$product->is_in_stock()) {
        return 0;
    }
    if (!$owner || !$owner->managing_stock()) {
        return null;
    }
    return max(0, (float) $owner->get_stock_quantity() - wc_get_held_stock_quantity($owner, $exclude_order));
}

function cashier_product_data(\WC_Product $product): array {
    return [
        'id' => $product->get_id(),
        'name' => $product->get_name(),
        'sku' => $product->get_sku(),
        'price' => (float) wc_get_price_including_tax($product),
        'stock' => cashier_available($product),
        'image' => $product->get_image_id() ? (wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') ?: '') : '',
    ];
}

add_action('wp_ajax_infiny_cashier_products', function () {
    cashier_access();
    if (!current_user_can('publish_shop_orders')) {
        wp_send_json_error(['message' => 'Accès aux articles indisponible.'], 403);
    }
    $query = input($_POST, 'q');
    $exact = $query !== '' ? (wc_get_product_id_by_sku($query) ?: wc_get_product_id_by_global_unique_id($query)) : 0;
    $match = $exact ? wc_get_product($exact) : null;
    if (cashier_sells_product($match)) {
        wp_send_json_success(['exact' => true, 'products' => [cashier_product_data($match)]]);
    }
    $args = ['status' => 'publish', 'type' => ['simple', 'variation'], 'limit' => 24, 'orderby' => 'name', 'order' => 'ASC'];
    if ($query !== '') {
        $args['s'] = $query;
    }
    $products = array_values(array_filter(wc_get_products($args), __NAMESPACE__ . '\\cashier_sells_product'));
    wp_send_json_success(['exact' => false, 'products' => array_map(__NAMESPACE__ . '\\cashier_product_data', $products)]);
});

function cashier_int_amount(string $value, ?int $decimals = null): ?int {
    $clean = preg_replace('/[\p{Z}\s]+/u', '', $value);
    if (!is_string($clean) || !preg_match('/^\d{1,12}(?:[.,]\d{1,6})?$/D', $clean)) {
        return null;
    }
    return (int) round((float) str_replace(',', '.', $clean) * (10 ** ($decimals ?? wc_get_price_decimals())));
}

function cashier_order_from_cart(array $lines, int $client_id): \WC_Order {
    $order = new \WC_Order();
    $order->set_status('auto-draft');
    $order->set_created_via('infiny-caisse');
    $order->set_currency(get_woocommerce_currency());
    $order->set_prices_include_tax(wc_prices_include_tax());
    if ($client_id) {
        $details = client_details($client_id);
        $order->update_meta_data(ORDER_CLIENT, $client_id);
        $order->set_billing_first_name($details['first'] ?? '');
        $order->set_billing_last_name($details['last'] ?? '');
        $order->set_billing_phone($details['phone'] ?? '');
    }
    foreach ($lines as $line) {
        if (!is_array($line) || !is_int($line['id'] ?? null) || !is_int($line['quantity'] ?? null)
            || $line['id'] <= 0 || $line['quantity'] < 1 || $line['quantity'] > 999) {
            throw new \InvalidArgumentException('Vérifiez les articles et les quantités du panier.');
        }
        $product = wc_get_product($line['id']);
        if (!cashier_sells_product($product)) {
            throw new \InvalidArgumentException('Un article du panier est indisponible.');
        }
        $item = new \WC_Order_Item_Product();
        $item->set_product($product);
        $item->set_quantity($line['quantity']);
        $subtotal = wc_get_price_excluding_tax($product, ['qty' => $line['quantity'], 'order' => $order]);
        $item->set_subtotal($subtotal);
        $item->set_total($subtotal);
        $order->add_item($item);
    }
    return $order;
}

function cashier_assert_stock(\WC_Order $order): void {
    if ($order->get_id() && $order->get_data_store()->get_stock_reduced($order->get_id())) {
        return;
    }
    $quantities = [];
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if (!$product) {
            throw new \InvalidArgumentException('Un produit de cette commande est introuvable.');
        }
        $stock = cashier_available($product, $order->get_id());
        if ($stock === null) {
            continue;
        }
        $owner = $product->get_stock_managed_by_id();
        $quantities[$owner] = ($quantities[$owner] ?? 0) + $item->get_quantity();
        if ($quantities[$owner] > $stock) {
            throw new \InvalidArgumentException('Stock insuffisant pour « ' . $item->get_name() . ' ».');
        }
    }
}

function cashier_checkout(): array|\WP_Error {
    $id = absint(input($_POST, 'commande'));
    $token = input($_POST, 'token');
    $method = input($_POST, 'methode');
    $transaction = input($_POST, 'transaction');
    $expected = cashier_int_amount(input($_POST, 'total_attendu'));
    if (!isset(CASHIER_METHODS[$method]) || $expected === null || (!$id && !wp_is_uuid($token, 4))) {
        return new \WP_Error('invalid', 'Vérifiez le moyen de paiement et le total.');
    }
    if ($method !== 'especes' && ($transaction === '' || strlen($transaction) > 100)) {
        return new \WP_Error('reference', 'Saisissez la référence du paiement reçu.');
    }
    if (!$id && !current_user_can('publish_shop_orders')) {
        return new \WP_Error('forbidden', 'Vous n’avez pas l’autorisation de créer une vente.');
    }

    if (!cashier_acquire_lock()) {
        return new \WP_Error('busy', 'La caisse est occupée. Réessayez dans quelques secondes.');
    }
    $order = null;
    $discard_draft = false;
    $mapping = 'infiny_cashier_sale_' . $token;

    try {
        $mapped_id = $id ? 0 : absint(get_option($mapping, 0));
        if ($id || $mapped_id) {
            $order = editable_order($id ?: $mapped_id);
            if (!$order) {
                return new \WP_Error('missing', 'Cette commande est introuvable ou inaccessible.');
            }
            if ($order->get_date_paid() && $order->get_meta(CASHIER_PAYMENT)) {
                cashier_ensure_ticket_pdf($order);
                return ['ticket' => url('caisse', ['ticket' => $order->get_id()])];
            }
            $retry_draft = $mapped_id && $order->has_status('auto-draft');
            if (!$retry_draft && !cashier_can_take_payment($order)) {
                return new \WP_Error('paid', 'Cette commande a déjà été réglée ou ne peut pas être encaissée.');
            }
        }
        $session = cashier_active_session();
        if (!$session || $session['id'] !== absint(input($_POST, 'session_id'))) {
            return new \WP_Error('session', 'La session de caisse a changé. Rechargez la page avant d’encaisser.');
        }
        if (($order ? $order->get_currency() : get_woocommerce_currency()) !== $session['currency']
            || wc_get_price_decimals() !== $session['decimals']) {
            return new \WP_Error('currency', 'La devise ou la précision des montants ne correspond pas à la caisse ouverte.');
        }
        if (!$order) {
            $lines = json_decode(input($_POST, 'panier'), true);
            if (!is_array($lines) || !$lines || count($lines) > 100) {
                return new \WP_Error('cart', 'Ajoutez les articles à encaisser.');
            }
            $client_id = absint(input($_POST, 'client'));
            if ($client_id && !client($client_id)) {
                return new \WP_Error('client', 'Le client sélectionné est introuvable.');
            }
            $order = cashier_order_from_cart($lines, $client_id);
            $discard_draft = true;
            cashier_assert_stock($order);
            $order->calculate_totals();
        }

        $total = (int) round(wc_add_number_precision((float) $order->get_total()));
        if ($total < 0 || !$order->get_items()) {
            return new \WP_Error('empty', 'La commande doit contenir des articles et avoir un total positif ou nul.');
        }
        if ($expected !== $total) {
            return new \WP_Error('price', 'Le total a changé. Vérifiez le nouveau montant avant de confirmer.', [
                'total' => wc_remove_number_precision($total),
            ]);
        }

        $received = $method === 'especes' ? cashier_int_amount(input($_POST, 'recu')) : null;
        if ($method === 'especes' && ($received === null || $received < $total)) {
            return new \WP_Error('cash', 'Le montant reçu doit couvrir le total à payer.');
        }
        if (!$order->has_status('auto-draft')) {
            cashier_assert_stock($order);
        }
        if (!$order->get_data_store()->get_stock_reduced($order->get_id())) {
            wc_reserve_stock_for_order($order);
        }

        if ($discard_draft) {
            if (!add_option($mapping, $order->get_id(), '', false)) {
                return new \WP_Error('retry', 'La vente est déjà en cours. Réessayez.');
            }
            $discard_draft = false;
        }
        $order->set_payment_method('infiny_caisse_' . $method);
        $order->set_payment_method_title(CASHIER_METHODS[$method]);
        $order->update_meta_data(CASHIER_PAYMENT, [
            'method' => $method,
            'amount' => wc_remove_number_precision($total),
            'transaction' => $method === 'especes' ? '' : $transaction,
            'received' => $received === null ? null : wc_remove_number_precision($received),
            'change' => $received === null ? null : wc_remove_number_precision($received - $total),
            'user' => get_current_user_id(),
            'session' => $session['id'],
        ]);
        if ($order->has_status('auto-draft')) {
            $order->set_status('pending');
            $order->save();
        }
        if (!$order->payment_complete($method === 'especes' ? '' : $transaction) || !$order->get_date_paid()) {
            return new \WP_Error('payment', 'L’encaissement n’a pas pu être confirmé. Réessayez depuis cet écran.');
        }

        cashier_ensure_ticket_pdf($order);
        return ['ticket' => url('caisse', ['ticket' => $order->get_id()])];
    } catch (\InvalidArgumentException | \Automattic\WooCommerce\Checkout\Helpers\ReserveStockException $error) {
        return new \WP_Error('checkout', $error->getMessage());
    } catch (\Throwable $error) {
        wc_get_logger()->error($error->getMessage(), ['source' => 'infiny-caisse']);
        return new \WP_Error('error', 'L’encaissement n’a pas pu être confirmé. Réessayez depuis cet écran.');
    } finally {
        try {
            if ($discard_draft && $order && $order->get_id()) {
                wc_release_stock_for_order($order);
                $order->delete(true);
            }
        } finally {
            cashier_release_lock();
        }
    }
}

add_action('wp_ajax_infiny_cashier_checkout', function () {
    cashier_access();
    $result = cashier_checkout();
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()] + (array) $result->get_error_data(), 422);
    }
    wp_send_json_success($result);
});
