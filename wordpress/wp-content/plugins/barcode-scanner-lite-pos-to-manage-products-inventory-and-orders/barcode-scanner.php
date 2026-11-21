<?php
/**
 * Plugin Name: Barcode Scanner with Inventory & Order Manager - (free)
 * Description: Scan barcodes to find & manage inventory and orders.
 * Text Domain: barcode-scanner-lite-pos-to-manage-products-inventory-and-orders
 * Version: 1.13.6
 * Build: 1791555750224
 * Author: UkrSolution
 * Plugin URI: https://www.ukrsolution.com/Wordpress/WooCommerce-Barcode-QRCode-Scanner-Reader
 * Author URI: http://www.ukrsolution.com
 * License: GPL2
 * WC requires at least: 5.3
 * -WC tested up to: 11.2.*
 */

use UkrSolution\BarcodeScanner\Database;
use UkrSolution\BarcodeScanner\features\mobile\MobileRouter;
use UkrSolution\BarcodeScanner\features\settings\Settings;

if (!defined('ABSPATH')) {
    exit;
}



include_once ABSPATH . 'wp-admin/includes/plugin.php';

if (file_exists(__DIR__ . '/src/PaymentCashCashier.php')) {
    include_once __DIR__ . '/src/PaymentCashCashier.php';
}




define('USBS_PLUGIN_BASE_URL', plugin_dir_url(__FILE__));
define('USBS_PLUGIN_BASE_PATH', plugin_dir_path(__FILE__));
define('USBS_PLUGIN_BASE_NAME', plugin_basename(__FILE__));


require_once __DIR__ . '/vendor/autoload.php';

register_activation_hook(__FILE__, function ($networkWide) {
    Database::setupTables($networkWide);
});

add_action('wpmu_new_blog', function ($blogId, $userId, $domain, $path, $siteId, $meta) {
    if (is_plugin_active_for_network(plugin_basename(__FILE__))) {
        switch_to_blog($blogId);
        Database::createTables();
        restore_current_blog();
    }
}, 10, 6);



add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $url = get_admin_url() . "admin.php?page=barcode-scanner-settings";
    $settings_link = '<a href="' . $url . '">Settings</a>';
    $links[] = $settings_link;

    return $links;
});


if(function_exists("wp_get_upload_dir")) {
    try {
        $usbs_dir = wp_get_upload_dir();
        $usbs_pathUpload = $usbs_dir["basedir"]  . '/barcode-scanner/';
        $usbs_pathApi = $usbs_dir["basedir"]  . '/barcode-scanner/api.php';
        $usbs_pathExample = $usbs_dir["basedir"]  . '/barcode-scanner/api-example.php';

        if (!file_exists($usbs_pathUpload)) {
            wp_mkdir_p($usbs_pathUpload);
        }

        if (file_exists($usbs_pathApi)) {
            include_once($usbs_pathApi);
        } 
        else if(function_exists("copy") && file_exists(__DIR__ . "/api-example.php") && !file_exists($usbs_pathExample)){
        }
    } catch (\Throwable $th) {
    }
}

if (file_exists(get_template_directory() . '/barcode-scanner/api.php')) {
    include_once(get_template_directory() . '/barcode-scanner/api.php');
}

new UkrSolution\BarcodeScanner\Core();