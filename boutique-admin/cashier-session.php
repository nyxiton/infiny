<?php

namespace Infiny\Boutique;

defined('ABSPATH') || exit;

const CASHIER_SESSION_TYPE = 'infiny_cash_session';
const CASHIER_SESSION_META = '_infiny_cash_session';
const CASHIER_SESSION_OPTION = 'infiny_cashier_open_session';

add_action('init', function () {
    register_post_type(CASHIER_SESSION_TYPE, ['public' => false, 'show_ui' => false]);
});

function cashier_lock_name(): string {
    global $wpdb;
    return 'infiny_pos_' . substr(hash('sha256', DB_NAME . '|' . $wpdb->prefix), 0, 48);
}

function cashier_acquire_lock(): bool {
    global $wpdb;
    return (int) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 8)', cashier_lock_name())) === 1;
}

function cashier_release_lock(): void {
    global $wpdb;
    $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', cashier_lock_name()));
}

function cashier_session_url(int $order_id = 0, array $args = []): string {
    return url('caisse', ['session' => 1] + ($order_id ? ['commande' => $order_id] : []) + $args);
}

function cashier_saved_session(int $id): ?array {
    if (!$id || get_post_type($id) !== CASHIER_SESSION_TYPE || get_post_status($id) !== 'private') {
        return null;
    }
    $session = get_post_meta($id, CASHIER_SESSION_META, true);
    return is_array($session) && isset($session['opened_at'], $session['currency'], $session['decimals'], $session['fund'])
        ? ['id' => $id] + $session
        : null;
}

function cashier_active_session(): ?array {
    $session = cashier_saved_session((int) get_option(CASHIER_SESSION_OPTION, 0));
    return $session && empty($session['closed_at']) ? $session : null;
}

function cashier_session_totals(array $session): array {
    $totals = ['count' => 0, 'methods' => array_fill_keys(array_keys(CASHIER_METHODS), 0)];
    $range = $session['opened_at'] . '...' . time();
    $scale = 10 ** $session['decimals'];
    foreach (dashboard_order_batches(['type' => 'shop_order', 'date_paid' => $range]) as $order) {
        $payment = $order->get_meta(CASHIER_PAYMENT);
        if (!is_array($payment) || (int) ($payment['session'] ?? 0) !== $session['id'] ||
            !isset(CASHIER_METHODS[$payment['method'] ?? '']) || $order->get_currency() !== $session['currency']) {
            continue;
        }
        ++$totals['count'];
        $totals['methods'][$payment['method']] += (int) round((float) ($payment['amount'] ?? $order->get_total()) * $scale);
    }
    return $totals;
}

function cashier_session_fingerprint(array $totals): string {
    return hash('sha256', wp_json_encode($totals));
}

function cashier_session_money(int $amount, array $session): string {
    return money($amount / (10 ** $session['decimals']), [
        'currency' => $session['currency'],
        'decimals' => $session['decimals'],
    ]);
}

function cashier_session_summary(array $session, array $totals): void {
    $expected = $session['fund'] + $totals['methods']['especes'];
    ?>
    <dl class="ib-dashboard-list">
        <div><dt>Fonds de caisse</dt><dd><?php echo cashier_session_money($session['fund'], $session); ?></dd></div>
        <?php foreach (CASHIER_METHODS as $key => $label) : ?>
            <div><dt><?php echo esc_html($label); ?></dt><dd><?php echo cashier_session_money($totals['methods'][$key], $session); ?></dd></div>
        <?php endforeach; ?>
        <div><dt>Encaissements</dt><dd><?php echo esc_html(number_format_i18n($totals['count'])); ?></dd></div>
        <div class="ib-session-expected"><dt>Espèces attendues</dt><dd><?php echo cashier_session_money($expected, $session); ?></dd></div>
        <?php if (isset($session['counted'])) :
            $difference = $session['counted'] - $expected;
            ?>
            <div><dt>Espèces comptées</dt><dd><?php echo cashier_session_money($session['counted'], $session); ?></dd></div>
            <div><dt>Écart de caisse</dt><dd class="<?php echo $difference ? 'ib-session-difference' : ''; ?>">
                <?php echo $difference > 0 ? '+' : ''; ?><?php echo cashier_session_money($difference, $session); ?>
            </dd></div>
        <?php endif; ?>
    </dl>
    <?php
}

function cashier_sessions(int $order_id): void {
    $closed_id = absint(input($_GET, 'cloture'));
    $closed = $closed_id ? cashier_saved_session($closed_id) : null;
    if ($closed_id && (!$closed || !isset($closed['closed_at'], $closed['totals']))) {
        echo '<p class="ib-empty">Clôture introuvable.</p>';
        button('Voir les sessions', cashier_session_url());
        return;
    }
    echo '<div class="ib-cashier-top">';
    button('Retour à la caisse', url('caisse', $order_id ? ['commande' => $order_id] : []));
    if ($closed) {
        button('Voir les sessions', cashier_session_url());
    }
    echo '</div>';
    if ($closed) {
        $opened_by = get_userdata($closed['opened_by']);
        $closed_by = get_userdata($closed['closed_by']);
        ?>
        <section class="ib-panel">
            <h2>Clôture #<?php echo esc_html($closed_id); ?></h2>
            <p>Ouverte le <?php echo esc_html(wp_date('d/m/Y à H:i', $closed['opened_at'])); ?>
                <?php if ($opened_by) : ?> par <?php echo esc_html($opened_by->display_name); ?><?php endif; ?></p>
            <p>Clôturée le <?php echo esc_html(wp_date('d/m/Y à H:i', $closed['closed_at'])); ?>
                <?php if ($closed_by) : ?> par <?php echo esc_html($closed_by->display_name); ?><?php endif; ?></p>
            <?php cashier_session_summary($closed, $closed['totals']); ?>
        </section>
        <?php
        return;
    }

    $session = cashier_active_session();
    $totals = $session ? cashier_session_totals($session) : [];
    $currency = $session['currency'] ?? get_woocommerce_currency();
    ?>
    <section class="ib-panel">
        <?php if ($session) : ?>
            <h2>Clôturer la caisse</h2>
            <p>Ouverte le <?php echo esc_html(wp_date('d/m/Y à H:i', $session['opened_at'])); ?></p>
            <?php cashier_session_summary($session, $totals); ?>
        <?php else : ?>
            <h2>Ouvrir la caisse</h2>
        <?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="infiny_cashier_session">
            <input type="hidden" name="operation" value="<?php echo $session ? 'cloturer' : 'ouvrir'; ?>">
            <input type="hidden" name="session_id" value="<?php echo esc_attr($session['id'] ?? 0); ?>">
            <?php if ($order_id) : ?><input type="hidden" name="commande" value="<?php echo esc_attr($order_id); ?>"><?php endif; ?>
            <?php if ($session) : ?><input type="hidden" name="bilan" value="<?php echo esc_attr(cashier_session_fingerprint($totals)); ?>"><?php endif; ?>
            <?php wp_nonce_field('ib-cashier-session-' . ($session['id'] ?? 0)); ?>
            <div class="ib-fields">
                <label><?php echo $session ? 'Espèces comptées' : 'Fonds de caisse'; ?> (<?php echo esc_html($currency); ?>)
                    <input name="montant" inputmode="decimal" placeholder="0" required>
                </label>
            </div>
            <button class="button button-primary"><?php echo $session ? 'Enregistrer la clôture' : 'Ouvrir la caisse'; ?></button>
        </form>
    </section>
    <?php cashier_session_history(); ?>
    <?php
}

function cashier_session_history(): void {
    $query = new \WP_Query([
        'post_type' => CASHIER_SESSION_TYPE,
        'post_status' => 'private',
        'posts_per_page' => 15,
        'paged' => max(1, absint(input($_GET, 'numero'))),
        'orderby' => ['date' => 'DESC', 'ID' => 'DESC'],
    ]);
    if (!$query->posts) {
        return;
    }
    ?>
    <h3>Historique des sessions</h3>
    <div class="ib-table"><table>
        <thead><tr><th>Session</th><th>Ouverture</th><th>Clôture</th><th>Écart</th><th>Action</th></tr></thead>
        <tbody>
            <?php foreach ($query->posts as $post) :
                $session = cashier_saved_session($post->ID);
                if (!$session) continue;
                $closed = !empty($session['closed_at']);
                ?>
                <tr>
                    <td>#<?php echo esc_html($session['id']); ?></td>
                    <td><?php echo esc_html(wp_date('d/m/Y H:i', $session['opened_at'])); ?></td>
                    <td><?php echo $closed ? esc_html(wp_date('d/m/Y H:i', $session['closed_at'])) : 'En cours'; ?></td>
                    <td><?php if ($closed) {
                        echo cashier_session_money($session['counted'] - $session['fund'] - $session['totals']['methods']['especes'], $session);
                    } else {
                        echo '—';
                    } ?></td>
                    <td><?php if ($closed) button('Voir la clôture', cashier_session_url(0, ['cloture' => $session['id']])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php
    pagination('caisse', (int) $query->max_num_pages, ['session' => 1]);
}

function cashier_open_session(int $fund): int|\WP_Error {
    if (cashier_active_session()) {
        return new \WP_Error('open', 'La caisse est déjà ouverte.');
    }
    $date = current_datetime();
    $id = wp_insert_post([
        'post_type' => CASHIER_SESSION_TYPE,
        'post_status' => 'private',
        'post_title' => 'Caisse · ' . $date->format('d/m/Y H:i'),
        'post_author' => get_current_user_id(),
        'meta_input' => [CASHIER_SESSION_META => [
            'opened_at' => $date->getTimestamp(),
            'opened_by' => get_current_user_id(),
            'currency' => get_woocommerce_currency(),
            'decimals' => wc_get_price_decimals(),
            'fund' => $fund,
        ]],
    ], true);
    if (!$id || is_wp_error($id)) {
        return new \WP_Error('save', 'La caisse n’a pas pu être ouverte.');
    }
    if (!update_option(CASHIER_SESSION_OPTION, $id, false)) {
        wp_delete_post($id, true);
        return new \WP_Error('save', 'La caisse n’a pas pu être ouverte.');
    }
    return $id;
}

function cashier_close_session(int $id, int $counted, string $fingerprint): int|\WP_Error {
    $session = cashier_active_session();
    if (!$session || $session['id'] !== $id) {
        return new \WP_Error('closed', 'Cette caisse a déjà été clôturée. Rechargez la page.');
    }
    $totals = cashier_session_totals($session);
    if (!hash_equals(cashier_session_fingerprint($totals), $fingerprint)) {
        return new \WP_Error('changed', 'Un encaissement a été ajouté. Vérifiez le montant compté avant de clôturer.');
    }
    $session['closed_at'] = time();
    $session['closed_by'] = get_current_user_id();
    $session['counted'] = $counted;
    $session['totals'] = $totals;
    unset($session['id']);
    if (!update_post_meta($id, CASHIER_SESSION_META, $session)) {
        return new \WP_Error('save', 'La clôture n’a pas pu être enregistrée.');
    }
    delete_option(CASHIER_SESSION_OPTION);
    return $id;
}

add_action('admin_post_infiny_cashier_session', function () {
    $id = absint(input($_POST, 'session_id'));
    check_admin_referer('ib-cashier-session-' . $id);
    if (!can_manage() || !class_exists('WooCommerce')) {
        wp_die('Accès indisponible.', '', ['response' => 403]);
    }
    $order_id = absint(input($_POST, 'commande'));
    $back = cashier_session_url($order_id);
    $operation = input($_POST, 'operation');
    $raw = input($_POST, 'montant');
    $session = $id ? cashier_saved_session($id) : null;
    $amount = cashier_int_amount($raw, $session['decimals'] ?? null);
    if ($amount === null) {
        finish($back, 'Saisissez un montant positif ou nul.');
    }
    if (!cashier_acquire_lock()) {
        finish($back, 'La caisse est occupée. Réessayez dans quelques secondes.');
    }
    try {
        $result = match ($operation) {
            'ouvrir' => $id ? new \WP_Error('invalid', 'Cette opération est indisponible.') : cashier_open_session($amount),
            'cloturer' => cashier_close_session($id, $amount, input($_POST, 'bilan')),
            default => new \WP_Error('invalid', 'Cette opération est indisponible.'),
        };
    } finally {
        cashier_release_lock();
    }
    if (is_wp_error($result)) {
        finish($back, $result->get_error_message());
    }
    finish($operation === 'ouvrir'
        ? url('caisse', $order_id ? ['commande' => $order_id] : [])
        : cashier_session_url(0, ['cloture' => $result]));
});
