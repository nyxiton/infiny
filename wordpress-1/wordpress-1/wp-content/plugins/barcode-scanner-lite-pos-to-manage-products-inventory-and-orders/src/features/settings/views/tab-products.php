<?php

use UkrSolution\BarcodeScanner\API\PluginsHelper;

if (!defined('ABSPATH')) {
    exit;
}
?>
<form class="bs-settings-input-conditions" id="bs-settings-products-tab" method="POST" action="<?php echo esc_url($usbs_actualLink); ?>">
    <input type="hidden" name="tab" value="products" />
    <input type="hidden" name="storage" value="table" />
    <input type="hidden" name="nonce" value="<?php echo esc_attr($usbs_nonce); ?>" />
    <table class="form-table">
        <tbody>
            <tr class="usbs-section-label">
                <td>
                    <h2><?php echo esc_html__("New product", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></h2>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php echo esc_html__('Default "Status" for new products', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_field = $settings->getSettings("newProductStatus");
                    $usbs_value = $usbs_field ? $usbs_field->value : "draft";
                    ?>
                    <select name="newProductStatus" style="width: 178px;">
                        <?php foreach (get_post_statuses() as $usbs_key => $usbs_label) : ?>
                            <?php $usbs_selected = $usbs_value === $usbs_key ? 'selected="selected"' : ""; ?>
                            <option value="<?php echo esc_html($usbs_key); ?>" <?php echo esc_html($usbs_selected); ?>><?php echo esc_html($usbs_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php echo esc_html__('Default "Quantity" for new products', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_field = $settings->getSettings("newProductQty");
                    $usbs_value = $usbs_field ? $usbs_field->value : "";
                    ?>
                    <input type="number" name="newProductQty" min="0" value="<?php echo esc_attr($usbs_value, 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'); ?>" placeholder="<?php esc_attr("Quantity", 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'); ?>" />
                </td>
            </tr>
            <tr class="usbs-section-label">
                <td>
                    <h2><?php echo esc_html__("Other", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></h2>
                </td>
            </tr>
            <!-- allowNegativeStock -->
            <?php
            $usbs_allowNegativeStock = $settings->getSettings("allowNegativeStock");
            ?>
            <tr id="bs_allow_negative_stock">
                <th scope="row">
                    <?php echo esc_html__("Allow negative stock quantity", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <label>
                        <?php
                        if (!$usbs_allowNegativeStock || ($usbs_allowNegativeStock && $usbs_allowNegativeStock->value === "on")) {
                            $usbs_checked = ' checked=checked ';
                        } else {
                            $usbs_checked = '';
                        }
                        ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> onchange="WebbsSettingsCheckboxChange(`#bs_allow_negative_stock input[name='allowNegativeStock']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="allowNegativeStock" value="<?php echo esc_attr($usbs_checked ? "on" : "off"); ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </label>
                </td>
            </tr>
            <!-- crete search field -->
            <tr id="bs_search_cf">
                <th scope="row">
                    <?php echo esc_html__("Create search field", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <label>
                        <?php
                        $usbs_searchCF = $settings->getSettings("searchCF");
                        $usbs_searchCF = $usbs_searchCF ? $usbs_searchCF->value : $settings->getField("general", "searchCF", "on");

                        if ($usbs_searchCF === "on") {
                            $usbs_checked = ' checked=checked ';
                        } else {
                            $usbs_checked = '';
                        }
                        ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> data-main="searchCF" onchange="WebbsSettingsCheckboxChange(`#bs_search_cf input[name='searchCF']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="searchCF" value="<?php echo esc_attr($usbs_checked ? "on" : "off"); ?>" />
                        <?php echo esc_html__("Create new custom field for products and variations which can be used for product search", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </label>
                </td>
            </tr>
            <tr id="bs_search_indexation" data-parent="searchCF">
                <th scope="row">
                    <?php echo esc_html__("Display name", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_searchCFLabel = $settings->getSettings("searchCFLabel");
                    $usbs_searchCFLabel = $usbs_searchCFLabel ? $usbs_searchCFLabel->value : $settings->getField("general", "searchCFLabel", "Barcode");
                    ?>
                    <input type="text" name="searchCFLabel" value=" <?php echo esc_html($usbs_searchCFLabel); ?>" />
                    <br /><i><?php echo esc_html__("Custom field name, which will be displayed on the product page", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>
                </td>
            </tr>
            <!-- Notify users on low product stock (after QTY change) -->
            <tr>
                <th scope="row">
                    <?php echo esc_html__("Notify users on low product stock (after QTY change)", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_field = $settings->getSettings("notifyUsersStock");
                    $usbs_notifyUsersStockValue = $usbs_field === null ? "" : $usbs_field->value;
                    $usbs_notifyUsersStockValueArr = $usbs_notifyUsersStockValue ? explode(",", $usbs_notifyUsersStockValue) : array();
                    ?>
                    <input type="hidden" name="notifyUsersStock[]" value="" />
                    <select name='notifyUsersStock[]' multiple data-placeholder='Choose a user...' multiple class='chosen-select-users-products' style="width:300px;">
                        <?php if ($usbs_notifyUsersStockValueArr) : ?>
                            <?php foreach (get_users(array("orderby" => "ID", "include" => $usbs_notifyUsersStockValueArr)) as $usbs_user) : ?>
                                <?php if (!in_array($usbs_user->ID, $usbs_notifyUsersStockValueArr)) continue; ?>
                                <?php $usbs_name = $usbs_user->display_name == $usbs_user->user_login ? $usbs_user->display_name : $usbs_user->display_name . " (" . $usbs_user->user_login . ")"; ?>
                                <option value="<?php echo esc_attr($usbs_user->ID); ?>"><?php echo esc_html($usbs_name); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div>
                        <i>
                            <?php echo esc_html__("If stock QTY comes to a defined WooCommerce threshold - send en email to specific users about it.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                        </i>
                    </div>
                </td>
            </tr>
            <!-- New product code field -->
            <tr id="field_for_new_product">
                <th scope="row">
                    <?php echo esc_html__("Use this field for barcode scanning", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_field = $settings->getSettings("fieldForNewProduct");
                    $usbs_fieldValue = $usbs_field === null ? $settings->getField("general", "fieldForNewProduct", "_sku") : $usbs_field->value;

                    $usbs_fields = array(
                        array("key" => "_sku", "value" => __("SKU Field", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders")),
                    );

                    $usbs_plugins = PluginsHelper::customPluginFields();
                    ?>
                    <select name="fieldForNewProduct" data-main="fieldForNewProduct">
                        <!-- default fields -->
                        <?php foreach ($usbs_fields as $usbs_value) : ?>
                            <option value="<?php echo esc_attr($usbs_value["key"]); ?>" <?php if ($usbs_value["key"] == $usbs_fieldValue) echo esc_html('selected="selected"'); ?>><?php echo esc_html($usbs_value["value"]); ?></option>
                        <?php endforeach; ?>
                        <!-- plugins fields -->
                        <?php foreach ($usbs_plugins as $usbs_key => $usbs_value) : ?>
                            <?php if (!$usbs_value["status"]) continue; ?>
                            <option value="<?php echo esc_attr($usbs_key); ?>" <?php if ($usbs_key == $usbs_fieldValue) echo esc_html('selected="selected"'); ?>><?php echo esc_html($usbs_value["label"]); ?></option>
                        <?php endforeach; ?>
                        <!-- custom field -->
                        <option value="custom_field" <?php if ("custom_field" == $usbs_fieldValue) echo esc_html('selected="selected"'); ?>><?php echo esc_html__("Custom field (meta name)", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></option>
                    </select>
                    <br /><i><?php echo esc_html__("The scanned barcode will be saved into the selected field.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>
                </td>
            </tr>
            <tr id="cf_for_new_product" data-parent="fieldForNewProduct">
                <th scope="row" style="padding-left: 20px;">
                    <?php echo esc_html__("Custom field", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_cfForNewProduct = $settings->getSettings("cfForNewProduct");
                    $usbs_cfForNewProduct = $usbs_cfForNewProduct ? $usbs_cfForNewProduct->value : "_sku";
                    ?>
                    <input type="text" name="cfForNewProduct" value=" <?php echo esc_html($usbs_cfForNewProduct); ?>" />
                    <br /><i><?php echo esc_html__("Meta name", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>
                </td>
            </tr>
            <!-- decimal_quantity -->
            <tr id="decimal_quantity">
                <th scope="row">
                    <?php echo esc_html__("Allow product qty with the floating point", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <label>
                        <?php
                        $usbs_field = $settings->getSettings("cartDecimalQuantity");
                        $usbs_value = $usbs_field === null ? "on" : $usbs_field->value;
                        ?>
                        <?php
                        if ($usbs_value === "on") {
                            $usbs_checked = ' checked=checked ';
                        } else {
                            $usbs_checked = '';
                        }
                        ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> data-main="cartDecimalQuantity" onchange="WebbsSettingsCheckboxChange(`#decimal_quantity input[name='cartDecimalQuantity']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="cartDecimalQuantity" value="<?php echo esc_attr($usbs_checked ? "on" : "off"); ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                        <br /><i><?php echo esc_html__("Important: Enable only if your woocommerce already supports product quantity with the floating point.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>
                    </label>
                </td>
            </tr>
        </tbody>
    </table>
    <div class="submit">
        <input type="submit" class="button button-primary" value="<?php echo esc_html__("Save Changes", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>">
    </div>
</form>

<?php
$usbs_stockLocations = get_terms(array(
    'taxonomy' => 'location',
    'hide_empty' => false,
));
?>

<style>
    table.scanner-tab-stock-locations th,
    table.scanner-tab-stock-locations td {
        padding: 5px 25px 10px 10px;
    }
</style>
<script>
    jQuery(document).ready(() => {
        const chosenOption = {
            width: "300px",
            no_results_text: "Loading:"
        };
        jQuery(".chosen-select-users-products").chosen(chosenOption);

        let request = null;
        jQuery('.chosen-select-users-products').on('chosen:no_results', function(evt, params) {
            const query = jQuery(params.chosen.search_field).val();
            const currentIds = jQuery(evt.target).val();
            if (request) request.abort();
            request = jQuery.post(window.usbs.ajaxUrl + "?token=" + window.usbs.utoken, {
                action: "barcodeScannerAction",
                rout: "usersFind",
                query,
                currentIds,
                nonce: window.usbs.nonce
            }, function(data) {
                const selectElem = jQuery(evt.target);
                const selectedIds = selectElem.val();
                selectElem.empty();

                try {
                    if (data && JSON.parse(data)) data = JSON.parse(data);
                } catch (error) {

                }

                if (data.users) {
                    jQuery.each(data.users, function(idx, obj) {
                        const selected = currentIds.includes(obj.ID) ? "selected='selected'" : "";
                        selectElem.append('<option value="' + obj.ID + '" ' + selected + '>' + obj.display_name + '</option>');
                    });
                }

                jQuery(evt.target).trigger('chosen:updated');
                jQuery(".chosen-select-users-products").val(selectedIds).trigger("chosen:updated");
            });
        });

        var defaultValue = '<?php echo esc_html($usbs_notifyUsersStockValue); ?>'.split(',');
        jQuery(".chosen-select-users-products").val(defaultValue).trigger("chosen:updated");
    });
</script>