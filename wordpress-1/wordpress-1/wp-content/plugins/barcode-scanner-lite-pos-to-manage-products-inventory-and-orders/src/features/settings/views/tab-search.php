<?php

use UkrSolution\BarcodeScanner\API\classes\SearchFilter;
use UkrSolution\BarcodeScanner\API\classes\WPML;
use UkrSolution\BarcodeScanner\features\settings\SettingsHelper;

if (!defined('ABSPATH')) {
    exit;
}
?>
<form class="bs-settings-input-conditions" id="bs-settings-search-tab" method="POST" action="<?php echo esc_url($usbs_actualLink); ?>">
    <input type="hidden" name="tab" value="search" />
    <input type="hidden" name="storage" value="table" />
    <input type="hidden" name="nonce" value="<?php echo esc_attr($usbs_nonce); ?>" />
    <table class="form-table">
        <tbody>
            <tr class="usbs-section-label">
                <td>
                    <h2><?php echo esc_html__("Exclude from search", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></h2>
                </td>
            </tr>
            <!--  -->
            <tr>
                <th scope="row">
                    <?php echo esc_html__("Exclude product statuses from search", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_field = $settings->getSettings("productStatuses");
                    $usbs_productStatusesValue = $usbs_field === null ? "trash" : $usbs_field->value;
                    ?>
                    <input type="hidden" name="productStatuses" value="" />
                    <select name="productStatuses[]" class="usbs_product_statuses" multiple="true" style="width:300px;">
                        <option value="publish"><?php echo esc_html__("Publish", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></option>
                        <option value="future"><?php echo esc_html__("Future", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></option>
                        <option value="draft"><?php echo esc_html__("Draft", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></option>
                        <option value="pending"><?php echo esc_html__("Pending", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></option>
                        <option value="private"><?php echo esc_html__("Private", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></option>
                        <option value="auto-draft"><?php echo esc_html__("Auto-Draft", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                        </option>
                        <option value="inherit"><?php echo esc_html__("Inherit", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></option>
                        <option value="trash"><?php echo esc_html__("Trash", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></option>
                    </select>
                </td>
            </tr>
            <!-- Exclude products from search with "Catalog visibility" -->
            <tr>
                <th scope="row">
                    <?php echo esc_html__('Exclude products from search with "Catalog visibility"', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_field = $settings->getSettings("productsCatalogVisibility");
                    $usbs_productsCatalogVisibilityValue = $usbs_field === null ? "" : $usbs_field->value;
                    ?>
                    <input type="hidden" name="productsCatalogVisibility" value="" />
                    <select name="productsCatalogVisibility[]" class="usbs_products_catalog_visibility" multiple="true" style="width:300px;">
                        <?php foreach ($settings->getCatalogVisibility() as $usbs_key => $usbs_value): ?>
                            <option value="<?php echo esc_attr($usbs_key); ?>"><?php echo esc_html($usbs_value); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <!-- Exclude order statuses from search -->
            <tr>
                <th scope="row">
                    <?php echo esc_html__("Exclude order statuses from search", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_field = $settings->getSettings("orderStatuses");
                    $usbs_orderStatusesValue = $usbs_field === null ? "wc-checkout-draft,trash" : $usbs_field->value;
                    ?>
                    <input type="hidden" name="orderStatuses" value="" />
                    <select name="orderStatuses[]" class="usbs_order_statuses" multiple="true" style="width:300px;">
                        <?php foreach (SettingsHelper::getOrderStatuses() as $usbs_key => $usbs_value): ?>
                            <option value="<?php echo esc_attr($usbs_key); ?>"><?php echo esc_html($usbs_value); ?></option>
                        <?php endforeach; ?>
                        <option value="trash"><?php echo esc_html__("Trash", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></option>
                    </select>
                </td>
            </tr>
            <!-- Exclude disabled variations for "Products" -->
            <tr id="disabled_variations_products">
                <th scope="row">
                    <?php echo esc_html__('Exclude disabled variations for "Products" tab', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <label>
                        <?php
                        $usbs_defaultValue = $settings->getSettings("disabledVariationsProducts");
                        $usbs_defaultValue = $usbs_defaultValue === null ? 'off' : $usbs_defaultValue->value;
                        $usbs_checked = $usbs_defaultValue !== "off" ? ' checked=checked ' : '';
                        ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> data-main="disabledVariationsProducts" onchange="WebbsSettingsCheckboxChange(`#disabled_variations_products input[name='disabledVariationsProducts']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="disabledVariationsProducts" value="<?php echo $usbs_checked ? "on" : "off"; ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?> <span class="usbs_option_notice"></span>
                    </label>
                </td>
            </tr>
            <!-- Exclude disabled variations for "Orders" & "New Order" -->
            <tr id="disabled_variations_orders">
                <th scope="row">
                    <?php echo esc_html__('Exclude disabled variations for "Orders" & "New Order" tabs', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <label>
                        <?php
                        $usbs_defaultValue = $settings->getSettings("disabledVariationsOrders");
                        $usbs_defaultValue = $usbs_defaultValue === null ? 'on' : $usbs_defaultValue->value;
                        $usbs_checked = $usbs_defaultValue !== "off" ? ' checked=checked ' : '';
                        ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> data-main="disabledVariationsOrders" onchange="WebbsSettingsCheckboxChange(`#disabled_variations_orders input[name='disabledVariationsOrders']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="disabledVariationsOrders" value="<?php echo $usbs_checked ? "on" : "off"; ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?> <span class="usbs_option_notice"></span>
                    </label>
                </td>
            </tr>
            <!-- WPML languages -->
            <?php if (WPML::status()): ?>
                <?php
                $usbs_searchFilter = SearchFilter::get();
                $usbs_translations = null;

                if (WPML::status()) {
                    $usbs_translations = WPML::getTranslations();
                }
                ?>
                <?php if ($usbs_translations): ?>
                    <tr id="wpml_translations">
                        <th scope="row">
                            <?php echo esc_html__('Include product languages in the search', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                        </th>
                        <td>
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <?php foreach ($usbs_translations as $usbs_key => $usbs_value): ?>
                                    <label style="display: flex; align-items: center; gap: 5px;">
                                        <?php
                                        $usbs_checked = isset($usbs_searchFilter['wpml']) && isset($usbs_searchFilter['wpml'][$usbs_key]) && $usbs_searchFilter['wpml'][$usbs_key] == 1 ? ' checked=checked ' : '';
                                        ?>
                                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> name="wpml[languages][<?php echo esc_attr($usbs_key) ?>]" value="1" style="margin-top: 2px;" />
                                        <?php if (isset($usbs_value["country_flag_url"])): ?>
                                            <img src="<?php echo esc_url($usbs_value["country_flag_url"]) ?>" height="15" />
                                        <?php endif; ?>
                                        <?php echo esc_html($usbs_value["native_name"]); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endif; ?>
            <tr class="usbs-section-label">
                <td>
                    <h2><?php echo esc_html__("Indexation", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></h2>
                </td>
            </tr>
            <!-- Search fields: [Open] -->
            <tr>
                <th scope="row">
                    <?php echo esc_html__("Search fields", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_link = '#barcode-scanner-search-filter';
                    ?>
                    <a href="<?php echo esc_url($usbs_link); ?>" class="usbs-btn"><?php echo esc_html__("Setup fields", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></a>
                    <i style="padding-left: 10px;"><?php echo esc_html__("Select which product/order fields should be used by the search to find the item.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>
                </td>
            </tr>
            <tr id="bs_search_indexation">
                <th scope="row">
                    <?php echo esc_html__("Start indexation", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <div>
                        <?php if (!$settings->getField("indexing", "indexed", false)): ?>
                            <div id="bs_search_indexation_notice">
                                <div class="notice-error notice" style="margin: 0 0 10px; display: inline-block;">
                                    <p>Please start indexation to speed up search</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php
                        $usbs_link = '#barcode-scanner-products-indexation';
                        ?>
                        <a href="<?php echo esc_url($usbs_link); ?>"><?php echo esc_html__("Start indexation", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></a>
                        <i style="padding-left: 10px;"><?php echo esc_html__("Re-create index tables and make full indexation of products and orders.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>
                    </div>
                    <div style="padding-top: 5px;">
                        <?php
                        $usbs_indexed = $settings->getTotalIndexedRecords();
                        $usbs_total = $settings->getTotalPosts();
                        $usbs_cannotIndexed = $settings->getTotalCantIndexedRecords();
                        ?>
                        <span id="barcode-scanner-products-total-indexed" style="color: #008b00;"><?php echo esc_html($usbs_indexed < $usbs_total ? $usbs_indexed : $usbs_total); ?></span>
                        <?php echo esc_html__("successfully indexed of", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?> <span id="barcode-scanner-products-total"><?php echo esc_html($usbs_total); ?></span>
                        <?php if ($usbs_cannotIndexed): ?>
                            <?php echo esc_html__("Can't index", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?> <span style="color: #ff0000;" id="barcode-scanner-products-fail-indexed"><?php echo esc_html($cannotIndexed); ?></span>
                            <?php echo esc_html__("items", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>.
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <!-- Indexation step (items per request) -->
            <tr>
                <th scope="row">
                    <?php echo esc_html__("Indexation step (items per request)", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <label>
                        <?php
                        $usbs_field = $settings->getSettings("indexationStep");
                        $usbs_value = $usbs_field === null ? 50 : (int) $usbs_field->value;
                        $usbs_value = $usbs_value ? $usbs_value : 50;
                        ?>
                        <input type="number" name="indexationStep" value="<?php echo esc_html($usbs_value); ?>" placeholder="50" min="1" max="1000" />
                    </label>
                </td>
            </tr>
            <!-- Enable products indexation (enabled by default) -->
            <tr id="products_indexation">
                <th scope="row">
                    <?php echo esc_html__("Enable products indexation", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <label>
                        <?php
                        $usbs_defaultValue = $settings->getSettings("productsIndexation");
                        $usbs_defaultValue = $usbs_defaultValue === null ? 'on' : $usbs_defaultValue->value;
                        $usbs_checked = $usbs_defaultValue !== "off" ? ' checked=checked ' : '';
                        ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> data-main="productsIndexation" onchange="WebbsSettingsCheckboxChange(`#products_indexation input[name='productsIndexation']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="productsIndexation" value="<?php echo $usbs_checked ? "on" : "off"; ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?> <span class="usbs-option-notice"></span>
                    </label>
                </td>
            </tr>
            <!-- Enable orders indexation (enabled by default) -->
            <tr id="orders_indexation">
                <th scope="row">
                    <?php echo esc_html__("Enable orders indexation", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <label>
                        <?php
                        $usbs_defaultValue = $settings->getSettings("ordersIndexation");
                        $usbs_defaultValue = $usbs_defaultValue === null ? 'on' : $usbs_defaultValue->value;
                        $usbs_checked = $usbs_defaultValue !== "off" ? ' checked=checked ' : '';
                        ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> data-main="ordersIndexation" onchange="WebbsSettingsCheckboxChange(`#orders_indexation input[name='ordersIndexation']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="ordersIndexation" value="<?php echo $usbs_checked ? "on" : "off"; ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?> <span class="usbs-option-notice"></span>
                    </label>
                </td>
            </tr>
            <tr class="usbs-section-label">
                <td>
                    <h2><?php echo esc_html__("Other", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></h2>
                </td>
            </tr>
            <tr id="bs_display_search_counter">
                <th scope="row">
                    <?php echo esc_html__("Display search counter", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <label>
                        <?php
                        $usbs_field = $settings->getSettings("displaySearchCounter");
                        $usbs_value = $usbs_field === null ? $settings->getField("general", "displaySearchCounter", "") : $usbs_field->value;
                        $usbs_checked = $usbs_value === "on" ? ' checked=checked ' : '';
                        ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> data-main="displaySearchCounter" onchange="WebbsSettingsCheckboxChange(`#bs_display_search_counter input[name='displaySearchCounter']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="displaySearchCounter" value="<?php echo $usbs_checked ? "on" : "off"; ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?> <span class="usbs-option-notice"></span>
                    </label><br />
                    <i>
                        <?php echo esc_html__("Displays the counter of how much times the product/order has been opened using barcode scanner.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </i>
                </td>
            </tr>
            <!-- Search results max limit -->
            <tr>
                <th scope="row">
                    <?php echo esc_html__("Search results max limit", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <label>
                        <?php
                        $usbs_field = $settings->getSettings("searchResultsLimit");
                        $usbs_value = $usbs_field === null ? 20 : (int) $usbs_field->value;
                        $usbs_value = $usbs_value ? $usbs_value : 20;
                        ?>
                        <input type="number" name="searchResultsLimit" value="<?php echo esc_html($usbs_value); ?>" placeholder="20" min="1" max="999" />
                    </label>
                    <br />
                    <i><?php echo esc_html__("Specify how much maximum search result you would like to see in search suggestion dropdown.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>

                </td>
            </tr>
            <!-- Delay between scanning and ajax request -->
            <tr>
                <th scope="row">
                    <?php echo esc_html__("Delay between scanning and ajax request", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <label>
                        <?php
                        $usbs_field = $settings->getSettings("delayBetweenScanning");
                        $usbs_value = $usbs_field === null ? 300 : (int) $usbs_field->value;
                        $usbs_value = $usbs_value ? $usbs_value : 300;
                        ?>
                        <input type="number" name="delayBetweenScanning" value="<?php echo esc_html($usbs_value); ?>" placeholder="300" min="50" max="1000" />
                    </label>
                    <br />
                    <i><?php echo esc_html__('Plugin waits for "X" ms while barcode scanner is filling the search input with code.', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>

                </td>
            </tr>
            <?php ?>
            <?php ?>
            <!-- Modify (pre-process) search string -->
            <tr>
                <th scope="row">
                    <?php echo esc_html__("Modify (pre-process) search string", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <label>
                        <?php
                        $usbs_field = $settings->getSettings("modifyPreProcessSearchString");
                        $usbs_value = $usbs_field === null ? "" : trim($usbs_field->value);
                        ?>
                        <textarea type="number" name="modifyPreProcessSearchString" value="<?php echo esc_textarea(base64_encode($usbs_value)); ?>" rows="5" cols="55"></textarea>
                        <script>
                            var text = document.querySelector('textarea[name="modifyPreProcessSearchString"]').getAttribute('value');
                            var decodedText = atob(text);
                            document.querySelector('textarea[name="modifyPreProcessSearchString"]').value = decodedText;
                        </script>
                    </label>
                    <br />
                    <i><?php echo esc_html__('Use JavaScript to edit search string (use JS variable "bs_search_string" for work).', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>
                </td>
            </tr>
            <!-- After search JavaScript -->
            <tr>
                <th scope="row">
                    <?php echo esc_html__("After search JavaScript", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <label>
                        <?php
                        $usbs_field = $settings->getSettings("afterSearchJavaScript");
                        $usbs_value = $usbs_field === null ? "" : trim($usbs_field->value);
                        ?>
                        <textarea type="number" name="afterSearchJavaScript" value="<?php echo esc_textarea(base64_encode($usbs_value)); ?>" rows="5" cols="55"></textarea>
                        <script>
                            var text = document.querySelector('textarea[name="afterSearchJavaScript"]').getAttribute('value');
                            var decodedText = atob(text);
                            document.querySelector('textarea[name="afterSearchJavaScript"]').value = decodedText;
                        </script>
                    </label>
                </td>
            </tr>
        </tbody>
    </table>
    <div class="submit">
        <input type="submit" class="button button-primary" value="<?php echo esc_html__("Save Changes", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>">
    </div>
</form>
<script>
    jQuery(document).ready(function () {
        jQuery(".usbs_product_statuses").chosen({
            search_contains: true,
            no_results_text: "<?php echo esc_html__("Nothing found for:", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?> ",
            width: "300px"
        });
        var defaultValue = '<?php echo esc_html($usbs_productStatusesValue); ?>'.split(',');
        jQuery(".usbs_product_statuses").val(defaultValue).trigger("chosen:updated");

        jQuery(".usbs_order_statuses").chosen({
            search_contains: true,
            no_results_text: "<?php echo esc_html__("Nothing found for:", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?> ",
            width: "300px"
        });
        var defaultValue = '<?php echo esc_html($usbs_orderStatusesValue); ?>'.split(',');
        jQuery(".usbs_order_statuses").val(defaultValue).trigger("chosen:updated");

        jQuery(".usbs_products_catalog_visibility").chosen({
            search_contains: true,
            no_results_text: "<?php echo esc_html__("Nothing found for:", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?> ",
            width: "300px"
        });
        var defaultValue = '<?php echo esc_html($usbs_productsCatalogVisibilityValue); ?>'.split(',');
        jQuery(".usbs_products_catalog_visibility").val(defaultValue).trigger("chosen:updated");
    });
</script>