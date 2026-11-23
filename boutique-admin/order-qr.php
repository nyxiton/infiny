<?php

namespace Infiny\Boutique;

defined('ABSPATH') || exit;

const SMS_NUMBER_OPTION = 'infiny_boutique_sms_number';
const SMS_SETTINGS_PAGE = 'infiny-boutique-sms';
const ORDER_SMS_REFERENCE = '_infiny_sms_reference';
const ORDER_SMS_QR = '_infiny_sms_qr';

function sms_settings_url(): string {
    return admin_url('admin.php?page=' . SMS_SETTINGS_PAGE);
}

function normalize_sms_number(string $number): ?string {
    $number = preg_replace('/[\p{Z}\s().-]+/u', '', trim($number));
    if ($number === '') {
        return '';
    }
    if (preg_match('/^0\d{9}$/D', $number)) {
        $number = '+261' . substr($number, 1);
    }
    return preg_match('/^\+[1-9]\d{7,14}$/D', $number) ? $number : null;
}

add_action('admin_init', function () {
    register_setting(SMS_SETTINGS_PAGE, SMS_NUMBER_OPTION, [
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => static function ($value): string {
            $number = is_string($value) ? normalize_sms_number($value) : null;
            if ($number === null) {
                add_settings_error(SMS_NUMBER_OPTION, 'numero_invalide', 'Saisissez un numéro international, par exemple +261 34 12 345 67.');
                return (string) get_option(SMS_NUMBER_OPTION, '');
            }
            return $number;
        },
    ]);
});

add_action('admin_menu', function () {
    if (can_manage()) {
        add_submenu_page('woocommerce', 'SMS de la boutique', 'SMS de la boutique', 'manage_options', SMS_SETTINGS_PAGE, __NAMESPACE__ . '\\sms_settings');
    }
});

function sms_settings(): void {
    if (!can_manage()) {
        wp_die('Accès indisponible.', '', ['response' => 403]);
    }
    $number = get_option(SMS_NUMBER_OPTION, '');
    ?>
    <div class="wrap">
        <h1>SMS de la boutique</h1>
        <?php settings_errors(); ?>
        <p>Le QR code de chaque commande prépare un SMS avec sa référence et ce numéro comme destinataire.</p>
        <form method="post" action="options.php">
            <?php settings_fields(SMS_SETTINGS_PAGE); ?>
            <label for="ib-sms-number">Numéro de la boutique</label>
            <input id="ib-sms-number" name="<?php echo esc_attr(SMS_NUMBER_OPTION); ?>" type="tel"
                value="<?php echo esc_attr($number); ?>" placeholder="+261 34 12 345 67">
            <?php submit_button('Enregistrer le numéro'); ?>
        </form>
    </div>
    <?php
}

function order_sms_data(\WC_Order $order): array {
    $id = $order->get_id();
    if (!$id || $order->get_type() !== 'shop_order' || $order->has_status(['auto-draft', 'draft', 'checkout-draft'])) {
        return [];
    }
    $reference = (string) $order->get_meta(ORDER_SMS_REFERENCE);
    $stored = $order->get_meta(ORDER_SMS_QR);
    $number = normalize_sms_number((string) get_option(SMS_NUMBER_OPTION, ''));
    $changed = false;
    $serial = strtoupper(base_convert((string) $id, 10, 36));
    if (!preg_match('/^INF-' . $serial . '-[0-9A-F]{8}$/D', $reference)) {
        $suffix = strtoupper(substr(hash_hmac('sha256', 'infiny-order-' . $id, wp_salt('nonce')), 0, 8));
        $reference = "INF-$serial-$suffix";
        $order->update_meta_data(ORDER_SMS_REFERENCE, $reference);
        $changed = true;
    }

    $data = ['reference' => $reference, 'number' => '', 'png' => ''];
    if ($number) {
        // SMSTO is the SMS format understood by QR-code readers such as ZXing.
        $payload = 'SMSTO:' . $number . ':' . $reference;
        $png = is_array($stored) && ($stored['payload'] ?? '') === $payload ? ($stored['png'] ?? '') : '';
        if ($png === '') {
            try {
                $png = order_qr_png($payload);
            } catch (\Throwable $error) {
                wc_get_logger()->error('Le QR code de commande n’a pas pu être généré.', [
                    'source' => 'infiny-boutique',
                    'order_id' => $id,
                ]);
            }
            if ($png !== '') {
                $order->update_meta_data(ORDER_SMS_QR, ['payload' => $payload, 'png' => $png]);
                $changed = true;
            }
        }
        $data = ['reference' => $reference, 'number' => $number, 'png' => $png];
    }
    if ($changed) {
        $order->save_meta_data();
    }
    return $data;
}

function order_qr_png(string $payload): string {
    if (!function_exists('imagepng')) {
        return '';
    }
    require_once __DIR__ . '/vendor/qr-code/qrcode.php';
    $level = QrVendor\QR_ERROR_CORRECT_LEVEL_M;
    $qr = QrVendor\QRCode::getMinimumQRCode($payload, $level);
    // Four modules of white margin make small printed codes easier to scan.
    $image = $qr->createImage(8, 32);
    ob_start();
    imagepng($image);
    $png = ob_get_clean();
    return $png ? base64_encode($png) : '';
}

add_action('woocommerce_new_order', function ($id, $order) {
    if ($order instanceof \WC_Order) {
        order_sms_data($order);
    }
}, 10, 2);

function order_by_sms_reference(string $reference): ?\WC_Order {
    if (!preg_match('/^INF-([0-9A-Z]{1,13})-[0-9A-F]{8}$/D', $reference, $matches)) {
        return null;
    }
    $id = (int) base_convert($matches[1], 36, 10);
    $order = $id ? wc_get_order($id) : null;
    return $order instanceof \WC_Order && $order->get_meta(ORDER_SMS_REFERENCE) === $reference ? $order : null;
}

function render_order_sms(\WC_Order $order): void {
    $data = order_sms_data($order);
    if (!$data) {
        return;
    }

    $reference = $data['reference'];
    $number = $data['number'];
    ?>
    <section class="ib-order-sms">
        <h4>Référence SMS <code><?php echo esc_html($reference); ?></code></h4>
        <?php if ($data['png']) :
            $image = 'data:image/png;base64,' . $data['png'];
            ?>
            <img class="ib-order-qr" width="180" height="180" src="<?php echo esc_attr($image); ?>" alt="QR code SMS de la commande">
            <p>Scannez le QR code pour préparer le SMS, puis confirmez son envoi sur le téléphone.</p>
            <p><a href="<?php echo esc_url('sms:' . $number . '?body=' . rawurlencode($reference), ['sms']); ?>">Ouvrir le SMS</a> ·
                <a href="<?php echo esc_attr($image); ?>" download="<?php echo esc_attr($reference . '.png'); ?>">Télécharger le QR code</a></p>
        <?php elseif (!$number && can_manage()) : ?>
            <p><a href="<?php echo esc_url(sms_settings_url()); ?>">Configurer le numéro de la boutique</a> pour générer le QR code.</p>
        <?php else : ?>
            <p>Le QR code est indisponible.</p>
        <?php endif; ?>
    </section>
    <?php
}

add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
    if ($order instanceof \WC_Order && can_manage() && current_user_can('edit_shop_order', $order->get_id())) {
        render_order_sms($order);
    }
}, 20);
