<?php

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$usbs_productId = isset($_GET["pid"]) ? sanitize_text_field(wp_unslash($_GET["pid"])) : "";
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$usbs_get_page = isset($_GET["page"]) ? sanitize_text_field(wp_unslash($_GET["page"])) : "";
$usbs_index_nonce = wp_create_nonce('barcode_scanner_indexed_data');
?>
<a href="#barcode-scanner-settings"></a>
<div id="bs-settings-page">
    <h2><?php echo esc_html__("Barcode Scanner Indexed Data", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></h2>
    <div>
        <div class="tabs">
            <form id="barcode-scan-indexed-data" method="GET">
                <input type="hidden" name="usbs_index_nonce" value="<?php echo esc_attr($usbs_index_nonce); ?>" />
                <input type="hidden" name="page" value="<?php echo esc_html($usbs_get_page); ?>" />&nbsp;
                <div>
                    <?php echo esc_html__("Enter product ID to get indexed data:", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    <input type="text" name="pid" value="<?php echo esc_attr($usbs_productId); ?>" placeholder="<?php echo esc_attr("Product ID", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>" />
                    <input id="submit" type="submit" class="button button-primary" value="<?php echo esc_attr("Apply", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>">
                </div>
                <?php if ($usbs_productId) : ?>
                    <?php
                    echo '<pre>';
                    echo wp_json_encode($indexedData->getByPostId($usbs_productId));
                    echo '</pre>';
                    $usbs_wpml = $indexedData->getWpmlData($usbs_productId);
                    if ($usbs_wpml) {
                        echo '<b>WPML</b>';
                        echo '<pre>';
                        echo wp_json_encode($usbs_wpml);
                        echo '</pre>';
                    }
                    ?>
                    <!-- <table class="form-table barcode-scan-logs">
                        <thead>
                            <tr>

                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table> -->
                <?php endif; ?>
            </form>

            <?php if ($usbs_productId) : ?>
                <form id="barcode-scan-indexed-data" method="GET">
                    <input type="hidden" name="usbs_index_nonce" value="<?php echo esc_attr($usbs_index_nonce); ?>" />
                    <input type="hidden" name="page" value="<?php echo esc_attr($usbs_get_page); ?>" />&nbsp;
                    <div>
                        <input type="hidden" name="index" value="<?php echo esc_attr($usbs_productId); ?>" />&nbsp;
                        <input type="hidden" name="pid" value="<?php echo esc_attr($usbs_productId); ?>" />
                        <input id="submit" type="submit" class="button button-primary" value="<?php echo esc_attr("Index product", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>">
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <br /><br />
        <hr />
        <b><?php echo esc_html__("Product fields:", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></b>
        <ul style="columns: 3; -webkit-columns: 3; -moz-columns: 3;">
            <?php foreach ($indexedData->getAllColumns() as $usbs_value) : ?>
                <li><?php echo esc_html($usbs_value); ?></li>
            <?php endforeach; ?>
        </ul>
        <hr />
        <form id="barcode-scan-indexed-data" method="GET" onSubmit="if(!confirm('Index will be empty, you will have to reindex all products.')){return false;}">
            <input type="hidden" name="usbs_index_nonce" value="<?php echo esc_attr($usbs_index_nonce); ?>" />
            <input type="hidden" name="page" value="<?php echo esc_html($usbs_get_page); ?>" />&nbsp;
            <div>
                <span style="display: inline-block; padding-top: 6px;"><?php echo esc_html__("Remove & create empty index table", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></span>
                <input type="hidden" name="reCreateTable" value="1" />&nbsp;
                <input id="submit" type="submit" class="button button-primary re-create-index-table" value="<?php echo esc_attr("Re-create index table", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>">
            </div>
        </form>
        <hr />
        <?php $usbs_itc = \get_option("usbs_index_triggers_counting", ""); ?>
        <form id="barcode-scan-indexed-data-triggers" method="GET">
            <input type="hidden" name="usbs_index_nonce" value="<?php echo esc_attr($usbs_index_nonce); ?>" />
            <input type="hidden" name="page" value="<?php echo esc_html($usbs_get_page); ?>" />&nbsp;
            <div>
                <label>
                    <input type="hidden" name="triggers" value="1" />&nbsp;
                    <input type="checkbox" name="index_triggers_counting" <?php echo $usbs_itc === "on" ? 'checked="checked"' : ''; ?> onchange="this.form.submit();" />
                    <?php echo esc_html__("Enable index triggers counting", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders") ?>
                </label>
            </div>
        </form>
        <br />
        <?php if (\get_option("usbs_index_triggers_counting", "") === "on") : ?>
            updated_post_meta_admin: <?php echo esc_html(\get_option("usbs_iic_updated_post_meta_admin", 0)); ?><br />
            updated_post_meta: <?php echo esc_html(\get_option("usbs_iic_updated_post_meta", 0)); ?><br />
            woocommerce_save_product_variation: <?php echo esc_html(\get_option("usbs_iic_woocommerce_save_product_variation", 0)); ?><br />
            transition_post_status: <?php echo esc_html(\get_option("usbs_iic_transition_post_status", 0)); ?><br />
            wp_insert_post: <?php echo esc_html(\get_option("usbs_iic_wp_insert_post", 0)); ?><br />
            pageIndexedData: <?php echo esc_html(\get_option("usbs_iic_pageIndexedData", 0)); ?><br />
            updatePostsTable: <?php echo esc_html(\get_option("usbs_iic_updatePostsTable", 0)); ?><br />
        <?php endif; ?>
    </div>
</div>