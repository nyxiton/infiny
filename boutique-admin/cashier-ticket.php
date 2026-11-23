<?php

namespace Infiny\Boutique;

defined('ABSPATH') || exit;

const CASHIER_TICKET_PDF = '_infiny_cashier_ticket_pdf';

function cashier_ticket_url(int $id, bool $preview = false): string {
    return add_query_arg([
        'action' => 'infiny_cashier_ticket',
        'id' => $id,
        '_wpnonce' => wp_create_nonce('ib-cashier-ticket-' . $id),
    ] + ($preview ? ['apercu' => 1] : []), admin_url('admin-post.php'));
}

function cashier_ticket_preview(int $id): void {
    ?>
    <section class="ib-cashier-preview ib-no-print" aria-labelledby="ib-ticket-preview">
        <h2 id="ib-ticket-preview">Aperçu du ticket PDF</h2>
        <iframe src="<?php echo esc_url(cashier_ticket_url($id, true)); ?>" title="Ticket PDF de la commande"></iframe>
    </section>
    <?php
}

function cashier_ensure_ticket_pdf(\WC_Order $order): string {
    $stored = $order->get_meta(CASHIER_TICKET_PDF);
    $pdf = is_string($stored) ? base64_decode($stored, true) : false;
    if ($pdf && str_starts_with($pdf, '%PDF-')) {
        return $pdf;
    }

    $payment = $order->get_meta(CASHIER_PAYMENT);
    if (!$order->get_date_paid() || !is_array($payment) || !isset(CASHIER_METHODS[$payment['method'] ?? ''])) {
        return '';
    }
    try {
        $pdf = render_cashier_ticket_pdf($order, $payment);
        $order->update_meta_data(CASHIER_TICKET_PDF, base64_encode($pdf));
        $order->save_meta_data();
        return $pdf;
    } catch (\Throwable $error) {
        wc_get_logger()->error($error->getMessage(), ['source' => 'infiny-caisse-pdf', 'order_id' => $order->get_id()]);
        return '';
    }
}

function cashier_ticket_text(string $value): string {
    $value = html_entity_decode(wp_strip_all_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return iconv('UTF-8', 'Windows-1252//TRANSLIT', $value) ?: '';
}

function cashier_ticket_money($value, string $currency): string {
    return cashier_ticket_text(wc_price($value, ['currency' => $currency]));
}

function render_cashier_ticket_pdf(\WC_Order $order, array $payment): string {
    require_once __DIR__ . '/vendor/fpdf/fpdf.php';
    $pdf = make_cashier_ticket_page($order, $payment, 297);
    if ($pdf->PageNo() === 1) {
        $height = min(297, max(85, ceil($pdf->GetY() + 9)));
        $pdf = make_cashier_ticket_page($order, $payment, $height);
    }
    return $pdf->Output('S');
}

function make_cashier_ticket_page(\WC_Order $order, array $payment, float $height): FpdfVendor\FPDF {
    $pdf = new FpdfVendor\FPDF('P', 'mm', [80, $height]);
    $pdf->SetTitle('Ticket de caisse #' . $order->get_order_number(), true);
    $pdf->SetMargins(6, 7, 6);
    $pdf->SetAutoPageBreak(true, 7);
    $pdf->AddPage();
    $pdf->SetTextColor(16, 42, 58);
    $pdf->SetDrawColor(160, 177, 180);
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->MultiCell(0, 6, cashier_ticket_text(get_bloginfo('name')), 0, 'C');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->MultiCell(0, 5, cashier_ticket_text('Ticket de caisse #' . $order->get_order_number()), 0, 'C');
    $pdf->MultiCell(0, 4, cashier_ticket_text(wc_format_datetime($order->get_date_paid(), 'd/m/Y H:i')), 0, 'C');
    $reference = (string) $order->get_meta(ORDER_SMS_REFERENCE);
    if ($reference !== '') {
        $pdf->MultiCell(0, 4, cashier_ticket_text('Référence : ' . $reference), 0, 'C');
    }
    $pdf->Cell(0, 3, '', 'B', 1);
    $pdf->Ln(2);

    $currency = $order->get_currency();
    foreach ($order->get_items() as $item) {
        $pdf->MultiCell(0, 4, cashier_ticket_text($item->get_name() . ' × ' . $item->get_quantity()));
        $pdf->Cell(0, 4, cashier_ticket_money($order->get_line_total($item, true), $currency), 0, 1, 'R');
    }
    $shipping = (float) $order->get_shipping_total() + (float) $order->get_shipping_tax();
    if ($shipping !== 0.0) {
        cashier_ticket_row($pdf, 'Livraison', cashier_ticket_money($shipping, $currency));
    }
    foreach ($order->get_fees() as $fee) {
        $pdf->MultiCell(0, 4, cashier_ticket_text($fee->get_name()));
        $pdf->Cell(0, 4, cashier_ticket_money((float) $fee->get_total() + (float) $fee->get_total_tax(), $currency), 0, 1, 'R');
    }
    $pdf->Cell(0, 3, '', 'B', 1);
    $pdf->Ln(2);

    $pdf->SetFont('Helvetica', 'B', 11);
    cashier_ticket_row($pdf, 'Total payé', cashier_ticket_money($order->get_total(), $currency));
    $pdf->SetFont('Helvetica', '', 9);
    if ((float) $order->get_total_tax() !== 0.0) {
        cashier_ticket_row($pdf, 'Dont taxes', cashier_ticket_money($order->get_total_tax(), $currency));
    }
    $pdf->MultiCell(0, 4, cashier_ticket_text('Paiement : ' . CASHIER_METHODS[$payment['method']]));
    if (!empty($payment['transaction'])) {
        $pdf->MultiCell(0, 4, cashier_ticket_text('Réf. paiement : ' . $payment['transaction']));
    }
    if ($payment['method'] === 'especes') {
        cashier_ticket_row($pdf, 'Montant reçu', cashier_ticket_money($payment['received'], $currency));
        cashier_ticket_row($pdf, 'Monnaie rendue', cashier_ticket_money($payment['change'], $currency));
    }
    return $pdf;
}

function cashier_ticket_row(FpdfVendor\FPDF $pdf, string $label, string $amount): void {
    $pdf->Cell(32, 5, cashier_ticket_text($label));
    $pdf->Cell(0, 5, $amount, 0, 1, 'R');
}

add_action('admin_post_infiny_cashier_ticket', function () {
    $id = absint(input($_GET, 'id'));
    check_admin_referer('ib-cashier-ticket-' . $id);
    $order = editable_order($id);
    if (!$order) {
        wp_die('Accès indisponible.', '', ['response' => 403]);
    }
    $pdf = cashier_ensure_ticket_pdf($order);
    if ($pdf === '') {
        wp_die('Ce ticket PDF est indisponible.', '', ['response' => 404]);
    }

    nocache_headers();
    header('Content-Type: application/pdf');
    header('X-Content-Type-Options: nosniff');
    $disposition = input($_GET, 'apercu') === '1' ? 'inline' : 'attachment';
    header('Content-Disposition: ' . $disposition . '; filename="ticket-caisse-' . $id . '.pdf"');
    echo $pdf;
    exit;
});
