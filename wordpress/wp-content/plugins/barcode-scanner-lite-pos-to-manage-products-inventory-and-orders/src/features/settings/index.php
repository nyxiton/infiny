<?php

if (!defined('ABSPATH')) {
    exit;
}

$usbs_tab = "search";
$usbs_url = "";
$usbs_actualLink = "";
$usbs_nonce = "";

try {

    // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

    if (isset($_POST["tab"])) {
        $usbs_tab = sanitize_text_field(wp_unslash($_POST["tab"]));
    } else if (isset($_GET["tab"])) {
        $usbs_tab = sanitize_text_field(wp_unslash($_GET["tab"]));
    } else if ($settings->activeTab) {
        $usbs_tab = $settings->activeTab;
    }

    // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

    $usbs_url = isset($_SERVER['REQUEST_URI']) ? sanitize_url(wp_unslash($_SERVER['REQUEST_URI'])) : '';
    $usbs_url = preg_replace('/(\&tab=.*)/', "", $usbs_url);
    $usbs_serverHost = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
    $usbs_actualLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $usbs_serverHost . $usbs_url . "-update";
    $usbs_nonce = wp_create_nonce(USBS_PLUGIN_BASE_NAME . "-settings");
} catch (\Throwable $th) {
}

$usbs_isProductLabelsPrinting = class_exists('UkrSolution\ProductLabelsPrinting\Helpers\Variables');
?>
<a href="#barcode-scanner-settings"></a>
<div id="bs-settings-page">
    <h2><?php echo esc_html__("Barcode Scanner settings", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></h2>
    <div>
        <?php ?>
        <div class="notice notice-warning" style="margin: 20px 0 10px;">
            <p>
                Upgrade to full version to get more features:
                <u>Order fulfillment</u>
                <span class="dashicons dashicons-info usbs-info-icon" style="cursor: pointer; vertical-align: middle;" title="Fulfill orders by picking the product and scanning its barcode, system tells if product from this order or not.">
                </span>,

                <u>Mobile app</u>
                <span class="dashicons dashicons-info usbs-info-icon" style="cursor: pointer; vertical-align: middle;" title="Manage inventory and orders from your iOS or Android device">
                </span>,

                <u>Customization of product/order view</u>
                <span class="dashicons dashicons-info usbs-info-icon" style="cursor: pointer; vertical-align: middle;" title="Add more custom product/order fields to the barcode scanner.">
                </span>,

                <u>Barcode auto-actions</u>
                <span class="dashicons dashicons-info usbs-info-icon" style="cursor: pointer; vertical-align: middle;" title="Setup auto action (like quantity increase or decrease) each time you scan the barcode, so you even don't have to touch a keyboard or mouse to manage inventory.">
                </span>,

                <u>Order Receipts</u>
                <span class="dashicons dashicons-info usbs-info-icon" style="cursor: pointer; vertical-align: middle;" title="Each order will have its own receipt ready to print.">
                </span>,

                <u>Permissions</u>
                <span class="dashicons dashicons-info usbs-info-icon" style="cursor: pointer; vertical-align: middle;" title="Grant different user roles different access to barcode scanner features.">
                </span>

                and

                <u>"Direct DB" mode to increase speed</u>
                <span class="dashicons dashicons-info usbs-info-icon" style="cursor: pointer; vertical-align: middle;" title="Dramatically increases barcode scanner performance, reducing server delays and preloader times.">
                </span>.

                <br />
                <a href="https://www.ukrsolution.com/Wordpress/WooCommerce-Barcode-QRCode-Scanner-Reader" target="_blank">Read more about all features.</a>
            </p>
        </div>
        <?php ?>
        <nav class="nav-tab-wrapper">
            <a href="#search" class="nav-tab <?php echo ($usbs_tab === "search") ? esc_attr("nav-tab-active") : "" ?>" data-tab="search"><?php echo esc_html__("Search", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></a>
            <a href="#products" class="nav-tab <?php echo ($usbs_tab === "products") ? esc_attr("nav-tab-active") : "" ?>" data-tab="products"><?php echo esc_html__("Products", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></a>
            <?php ?>
            <a href="#orders" class="nav-tab <?php echo ($usbs_tab === "orders") ? esc_attr("nav-tab-active") : "" ?>" data-tab="orders"><?php echo esc_html__("Orders", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></a>
            <?php ?>
            <?php ?>
            <?php ?>
            <a href="#css" class="nav-tab <?php echo ($usbs_tab === "css") ? esc_attr("nav-tab-active") : "" ?>" data-tab="css"><?php echo esc_html__("Other", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></a>
            <?php ?>

            <?php ?>
        </nav>
        <div class="tabs">
            <?php ?>

            <!-- search -->
            <div class="settings-tab search-tab" <?php echo ($usbs_tab !== "search") ? 'style="display: none;"' : "" ?>>
                <?php require_once(__DIR__ . "/views/tab-search.php"); ?>
            </div>
            <!-- products -->
            <div class="settings-tab products-tab" <?php echo ($usbs_tab !== "products") ? 'style="display: none;"' : "" ?>>
                <?php require_once(__DIR__ . "/views/tab-products.php"); ?>
            </div>
            <!-- orders -->
            <div class="settings-tab orders-tab" <?php echo ($usbs_tab !== "orders") ? 'style="display: none;"' : "" ?>>
                <?php require_once(__DIR__ . "/views/tab-orders.php"); ?>
            </div>

            <?php ?>

            <!-- CSS -->
            <div class="settings-tab css-tab" <?php echo ($usbs_tab !== "css") ? 'style="display: none;"' : "" ?>>
                <?php require_once(__DIR__ . "/views/tab-other.php"); ?>
            </div>

            <?php ?>
        </div>
    </div>
</div>

<div id="barcode-scanner-preloader"><span class="a4b-preloader-icon"></span></div>
<style>
    #barcode-scanner-preloader {
        position: fixed;
        top: 0px;
        left: 0px;
        width: 100vw;
        height: 100vh;
        z-index: 9000;
        font-size: 14px;
        background: rgba(0, 0, 0, 0.3);
        transition: opacity 0.3s ease 0s;
        transform: translate3d(0px, 0px, 0px);
    }

    #barcode-scanner-preloader .a4b-preloader-icon {
        position: relative;
        top: 50%;
        left: 50%;
        color: #fff;
        border-radius: 50%;
        opacity: 1;
        width: 30px;
        height: 30px;
        border: 2px solid #f3f3f3;
        border-top: 3px solid #3498db;
        display: inline-block;
        animation: a4b-spin 1s linear infinite;
    }

    .usbs-info-icon {
        color: #1976d2;
    }

    @keyframes a4b-spin {
        100% {
            -webkit-transform: rotate(360deg);
            transform: rotate(360deg);
        }
    }
</style>

<style>
    #bs-settings-page .tabs .form-table th {
        padding: 4px 8px 4px 0;
        font-weight: normal;
    }

    #bs-settings-page .tabs .form-table td {
        padding: 4px;
        line-height: 1.2;
        vertical-align: top;
    }

    #bs-settings-page .tabs .settings-tab table.form-table tr.usbs-section-label td {
        padding-left: 0;
    }

    #bs-settings-page .tabs .settings-tab table.form-table tr h2 {
        margin: 0;
        padding: 24px 0px 4px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('.nav-tab-wrapper a');
        let selectedTab = '<?php echo esc_html($usbs_tab); ?>';

        tabs.forEach((tab) => {
            tab.addEventListener('click', (event) => {
                selectedTab = event.target.getAttribute('data-tab');
                history.pushState(null, null, '?page=barcode-scanner-settings&tab=' + selectedTab);
            });
        });
    });
</script>