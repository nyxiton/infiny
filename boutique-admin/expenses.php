<?php

namespace Infiny\Boutique;

defined('ABSPATH') || exit;

const EXPENSE_TYPE = 'infiny_expense';
const EXPENSE_META = '_infiny_expense_details';
const EXPENSE_CATEGORIES = [
    'stock' => 'Achat de marchandises',
    'loyer' => 'Loyer',
    'charges' => 'Charges et fournitures',
    'transport' => 'Transport et livraison',
    'personnel' => 'Personnel',
    'autre' => 'Autre',
];
const EXPENSE_PAYMENTS = [
    'especes' => 'Espèces',
    'mobile' => 'Mobile Money',
    'virement' => 'Virement',
    'carte' => 'Carte',
    'autre' => 'Autre',
];

add_action('init', function () {
    register_post_type(EXPENSE_TYPE, ['public' => false, 'show_ui' => false, 'supports' => ['title']]);
});

function expense_post(int $id): ?\WP_Post {
    $post = $id ? get_post($id) : null;
    return $post && $post->post_type === EXPENSE_TYPE && $post->post_status === 'private' ? $post : null;
}

function expense_details(int $id): array {
    $details = get_post_meta($id, EXPENSE_META, true);
    return is_array($details) ? $details : [];
}

function expense_date_is_valid(string $date): bool {
    $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date && (int) $parsed->format('Y') >= 1000;
}

function expenses(int $id): void {
    $month = input($_GET, 'mois');
    if (!expense_date_is_valid($month . '-01')) {
        $month = current_datetime()->format('Y-m');
    }
    $list = url('depenses', ['mois' => $month]);
    $expense = expense_post($id);
    if ($id && !$expense) {
        echo '<p class="ib-empty">Dépense introuvable.</p>';
        button('Retour aux dépenses', $list);
        return;
    }

    expense_form($expense, $month);
    if (!$expense) {
        expense_history($month);
    }
}

function expense_form(?\WP_Post $expense, string $month): void {
    $id = $expense->ID ?? 0;
    $details = $id ? expense_details($id) : [];
    $currency = $details['currency'] ?? get_woocommerce_currency();
    $date = $expense ? substr($expense->post_date, 0, 10) : current_datetime()->format('Y-m-d');
    if ($expense) {
        button('Retour aux dépenses', url('depenses', ['mois' => $month]));
    }
    ?>
    <form class="ib-panel" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <h3><?php echo $id ? 'Modifier la dépense' : 'Nouvelle dépense'; ?></h3>
        <input type="hidden" name="action" value="infiny_boutique_expense">
        <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">
        <input type="hidden" name="mois" value="<?php echo esc_attr($month); ?>">
        <?php wp_nonce_field('ib-expense-' . $id); ?>
        <div class="ib-fields">
            <label>Date <input name="date_depense" type="date" required value="<?php echo esc_attr($date); ?>"></label>
            <label>Montant (<?php echo esc_html($currency); ?>)
                <input name="montant" inputmode="decimal" required value="<?php echo esc_attr($details['amount'] ?? ''); ?>">
            </label>
            <label>Motif <input name="motif" required maxlength="160" value="<?php echo esc_attr($expense->post_title ?? ''); ?>"></label>
            <label>Catégorie
                <select name="categorie" required>
                    <option value="">Choisir une catégorie</option>
                    <?php foreach (EXPENSE_CATEGORIES as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($details['category'] ?? '', $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Moyen de paiement
                <select name="paiement">
                    <option value="">Non renseigné</option>
                    <?php foreach (EXPENSE_PAYMENTS as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($details['payment'] ?? '', $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label class="ib-expense-reinvestment">
            <input type="checkbox" name="reinvestissement" value="1" <?php checked(!empty($details['reinvestment'])); ?>>
            Réinvestissement
        </label>
        <button class="button button-primary">Enregistrer la dépense</button>
    </form>
    <?php
}

function expense_posts(string $month): array {
    [$year, $number] = array_map('intval', explode('-', $month));
    return get_posts([
        'post_type' => EXPENSE_TYPE,
        'post_status' => 'private',
        'posts_per_page' => -1,
        'orderby' => ['date' => 'DESC', 'ID' => 'DESC'],
        'date_query' => [['year' => $year, 'monthnum' => $number]],
    ]);
}

function expense_history(string $month): void {
    $expenses = expense_posts($month);
    $totals = [];
    foreach ($expenses as $expense) {
        $details = expense_details($expense->ID);
        if (isset($details['currency'], $details['amount'])) {
            $currency = $details['currency'];
            $totals[$currency] = ($totals[$currency] ?? 0) + (float) $details['amount'];
        }
    }
    $totals = $totals ?: [get_woocommerce_currency() => 0];
    $page = max(1, absint(input($_GET, 'numero')));
    $per_page = 20;
    ?>
    <h3>Historique des dépenses</h3>
    <div class="ib-toolbar">
        <form method="get" action="<?php echo esc_url(home_url('/')); ?>">
            <input type="hidden" name="boutique" value="1">
            <input type="hidden" name="vue" value="depenses">
            <label for="ib-expense-month">Mois</label>
            <input id="ib-expense-month" name="mois" type="month" value="<?php echo esc_attr($month); ?>">
            <button class="button">Afficher</button>
        </form>
        <div class="ib-expense-total">
            <span>Total du mois</span>
            <?php foreach ($totals as $currency => $amount) : ?>
                <strong><?php echo money($amount, ['currency' => $currency]); ?></strong>
            <?php endforeach; ?>
        </div>
    </div>
    <?php if (!$expenses) : ?>
        <p class="ib-empty">Aucune dépense enregistrée pour ce mois.</p>
        <?php return; ?>
    <?php endif; ?>
    <div class="ib-table">
        <table>
            <thead><tr><th>Date</th><th>Motif</th><th>Catégorie</th><th>Paiement</th><th>Montant</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach (array_slice($expenses, ($page - 1) * $per_page, $per_page) as $expense) :
                    $details = expense_details($expense->ID);
                    ?>
                    <tr>
                        <td><?php echo esc_html(get_post_time('d/m/Y', false, $expense)); ?></td>
                        <td><?php echo esc_html($expense->post_title); ?>
                            <?php if (!empty($details['reinvestment'])) : ?>
                                <span class="ib-pill">Réinvestissement</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(EXPENSE_CATEGORIES[$details['category'] ?? ''] ?? '—'); ?></td>
                        <td><?php echo esc_html(EXPENSE_PAYMENTS[$details['payment'] ?? ''] ?? '—'); ?></td>
                        <td><?php echo money($details['amount'] ?? '', ['currency' => $details['currency'] ?? get_woocommerce_currency()]); ?></td>
                        <td><?php button('Modifier', url('depenses', ['id' => $expense->ID, 'mois' => $month])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    pagination('depenses', (int) ceil(count($expenses) / $per_page), ['mois' => $month]);
}

add_action('admin_post_infiny_boutique_expense', function () {
    $id = absint(input($_POST, 'id'));
    check_admin_referer('ib-expense-' . $id);
    if (!can_manage() || !class_exists('WooCommerce')) {
        wp_die('Accès indisponible.', '', ['response' => 403]);
    }
    $expense = expense_post($id);
    if ($id && !$expense) {
        finish(url('depenses'), 'Dépense introuvable.');
    }
    $month = input($_POST, 'mois');
    $destination = url('depenses', array_filter(['id' => $id, 'mois' => $month]));
    $date = input($_POST, 'date_depense');
    $label = input($_POST, 'motif');
    $category = input($_POST, 'categorie');
    $payment = input($_POST, 'paiement');
    $amount = preg_replace('/[\p{Z}\s]+/u', '', input($_POST, 'montant'));
    if (!expense_date_is_valid($date) || $label === '' || !isset(EXPENSE_CATEGORIES[$category])) {
        finish($destination, 'Renseignez la date, le motif et la catégorie.');
    }
    if (!preg_match('/^\d{1,12}(?:[.,]\d{1,2})?$/D', $amount) || (float) str_replace(',', '.', $amount) <= 0) {
        finish($destination, 'Saisissez un montant supérieur à zéro, par exemple 12 500.');
    }
    if ($payment !== '' && !isset(EXPENSE_PAYMENTS[$payment])) {
        finish($destination, 'Choisissez un moyen de paiement dans la liste.');
    }
    $details = $id ? expense_details($id) : [];
    $saved = wp_insert_post(wp_slash([
        'ID' => $id,
        'post_type' => EXPENSE_TYPE,
        'post_status' => 'private',
        'post_author' => $expense->post_author ?? get_current_user_id(),
        'post_title' => $label,
        'post_date' => $date . ' 00:00:00',
        'post_date_gmt' => get_gmt_from_date($date . ' 00:00:00'),
        'meta_input' => [EXPENSE_META => [
            'amount' => str_replace(',', '.', $amount),
            'currency' => $details['currency'] ?? get_woocommerce_currency(),
            'category' => $category,
            'payment' => $payment,
            'reinvestment' => input($_POST, 'reinvestissement') === '1',
        ]],
    ]), true);
    if (is_wp_error($saved) || !$saved) {
        finish($destination, 'La dépense n’a pas pu être enregistrée. Réessayez.');
    }
    finish(url('depenses', ['mois' => substr($date, 0, 7)]));
});
