<?php

namespace UkrSolution\BarcodeScanner\API\classes;

use UkrSolution\BarcodeScanner\features\settings\Settings;

class Emails
{
    static public function sendLowStock($productId, $qty, $productName, $thershold)
    {
        try {

            $settings = new Settings();
            $notifyUsersStock = $settings->getSettings("notifyUsersStock");
            $usersIds = $notifyUsersStock === null ? "" : $notifyUsersStock->value;
            $usersIds = $usersIds ? explode(",", $usersIds) : array();

            if ($usersIds) {
                $subject = __("Low stock inventory", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders") . "({$qty}) - {$productName}";

                $linkFront = '<a href="' . get_permalink($productId) . '">' . $productName . '</a>';
                $message = sprintf(
                    /* translators: %s: product link */
                    __('Product "%s" has reached', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'),
                    $linkFront
                );

                $urlBack = get_edit_post_link($productId);
                $message .= sprintf(
                    ' <a href="%1$s">%2$s</a>',
                    esc_url($urlBack),
                    sprintf(
                        /* translators: %s: stock quantity */
                        __('stock quantity %s', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'),
                        esc_html($qty)
                    )
                );

                $message .= sprintf(
                    /* translators: %s: stock threshold value */
                    __('<br/><i>The stock threshold for this product is %s</i>', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'),
                    esc_html($thershold)
                );

                foreach ($usersIds as $userId) {
                    if ($userId) {
                        $user = get_userdata($userId);
                        if ($user) {

                            $headers = array('Content-Type: text/html; charset=UTF-8');
                            wp_mail($user->user_email, $subject, $message, $headers);
                        }
                    }
                }
            }
        } catch (\Throwable $th) {
        }
    }

    static public function mailerTrigger($orderId, $status)
    {
        return;
        try {
            $mailer = \WC()->mailer();
            $emails = $mailer->get_emails();

            foreach ($emails as $trigger => $email) {
                $statusToCheck = str_replace("wc-", "", $status);
                $statusToCheck = str_replace("-", "_", $statusToCheck);

                if (stripos($trigger, $statusToCheck) !== false) {
                    if ($email->trigger($orderId)) {
                    } else {
                    }
                }
            }
        } catch (\Throwable $th) {
        }
    }
}
