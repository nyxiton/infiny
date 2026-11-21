<?php
use UkrSolution\BarcodeScanner\features\settings\SettingsHelper;

if (!defined('ABSPATH')) {
    exit;
}
?>

<form class="bs-settings-input-conditions" id="bs-settings-orders-tab" method="POST" action="<?php echo esc_url($usbs_actualLink); ?>">
    <input type="hidden" name="tab" value="orders" />
    <input type="hidden" name="storage" value="table" />
    <input type="hidden" name="nonce" value="<?php echo esc_attr($usbs_nonce); ?>" />
    <table class="form-table">
        <tbody>
            <?php ?>

            <tr class="usbs-section-label">
                <td>
                    <h2><?php echo esc_html__("General", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></h2>
                </td>
            </tr>
            <!-- Display "Coupon" field -->
            <tr id="display_coupon_field">
                <th scope="row">
                    <?php echo esc_html__('Display "Coupon" field', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_defaultValue = $settings->getSettings("displayCouponField");
                    $usbs_defaultValue = $usbs_defaultValue === null ? 'on' : $usbs_defaultValue->value;
                    ?>
                    <label>
                        <?php $usbs_checked = $usbs_defaultValue !== "off" ? ' checked=checked ' : ''; ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> onchange="WebbsSettingsCheckboxChange(`#display_coupon_field input[name='displayCouponField']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="displayCouponField" value="<?php echo $usbs_checked ? "on" : "off"; ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </label>
                </td>
            </tr>
            <!-- Display "Customer provided note" field -->
            <tr id="display_note_field">
                <th scope="row">
                    <?php echo esc_html__('Display "Customer provided note" field', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_defaultValue = $settings->getSettings("displayNoteField");
                    $usbs_defaultValue = $usbs_defaultValue === null ? 'on' : $usbs_defaultValue->value;
                    ?>
                    <label>
                        <?php $usbs_checked = $usbs_defaultValue !== "off" ? ' checked=checked ' : ''; ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> onchange="WebbsSettingsCheckboxChange(`#display_note_field input[name='displayNoteField']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="displayNoteField" value="<?php echo $usbs_checked ? "on" : "off"; ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </label>
                </td>
            </tr>
            <!-- Show taxes for each item -->
            <tr id="displayTaxesForItems">
                <th scope="row">
                    <?php echo esc_html__('Show taxes for each item', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_defaultValue = $settings->getSettings("displayTaxesForItems");
                    $usbs_defaultValue = $usbs_defaultValue === null ? '' : $usbs_defaultValue->value;
                    ?>
                    <label>
                        <?php $usbs_checked = $usbs_defaultValue !== "off" ? ' checked=checked ' : ''; ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> onchange="WebbsSettingsCheckboxChange(`#displayTaxesForItems input[name='displayTaxesForItems']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="displayTaxesForItems" value="<?php echo $usbs_checked ? "on" : "off"; ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </label>
                </td>
            </tr>
            <!-- Display "PAY" buttons -->
            <tr id="displayPayButton">
                <th scope="row">
                    <?php echo esc_html__('Display "PAY" buttons', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_defaultValue = $settings->getSettings("displayPayButton");
                    $usbs_defaultValue = $usbs_defaultValue === null ? 'on' : $usbs_defaultValue->value;
                    ?>
                    <label>
                        <?php $usbs_checked = $usbs_defaultValue !== "off" ? ' checked=checked ' : ''; ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> onchange="WebbsSettingsCheckboxChange(`#displayPayButton input[name='displayPayButton']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="displayPayButton" value="<?php echo $usbs_checked ? "on" : "off"; ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </label>
                </td>
            </tr>
            <!-- Product image size -->
            <tr id="displayPayButton">
                <th scope="row">
                    <?php echo esc_html__('Product image size', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_defaultValueW = $settings->getSettings("orderProductImageWidth");
                    $usbs_defaultValueW = $usbs_defaultValueW === null ? "64" : $usbs_defaultValueW->value;
                    ?>
                    <span style="margin-right: 15px;">
                        <input type="text" name="orderProductImageWidth" value="<?php echo esc_attr($usbs_defaultValueW); ?>" placeholder="<?php echo esc_attr("1"); ?>" style="width: 70px; height: 30px;" />
                        px
                    </span>
                </td>
            </tr>
            <tr class="usbs-section-label">
                <td>
                    <h2><?php echo esc_html__("Existing Orders", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></h2>
                </td>
            </tr>
            <!-- List of order statuses which considered as still not completed -->
            <tr>
                <th scope="row" style="width: 240px;">
                    <?php echo esc_html__("List of order statuses which considered as still not completed", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td class="statuses_still_not_completed">
                    <?php
                    $usbs_field = $settings->getSettings("orderStatusesAreStillNotCompleted");
                    $usbs_orderStatusesAreStillNotCompletedValue = $usbs_field === null ? "wc-pending,wc-processing,wc-on-hold" : $usbs_field->value;
                    ?>
                    <input type="hidden" name="orderStatusesAreStillNotCompleted" value="" />
                    <select name="orderStatusesAreStillNotCompleted[]" class="usbs_order_statuses_are_still_not_complected" multiple="true" style="width:300px;">
                        <?php foreach (SettingsHelper::getOrderStatuses() as $usbs_key => $usbs_value): ?>
                            <option value="<?php echo esc_attr($usbs_key); ?>"><?php echo esc_html($usbs_value); ?></option>
                        <?php endforeach; ?>
                        <option value="trash"><?php echo esc_html__("Trash", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></option>
                    </select>
                    <br />
                    <i><?php echo esc_html__("These statuses are used to determine how much orders are not completed for the same customer", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>
                </td>
            </tr>
            <tr class="usbs-section-label">
                <td>
                    <h2><?php echo esc_html__("New order", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></h2>
                </td>
            </tr>
            <!-- Default order status -->
            <tr>
                <th scope="row" style="width: 240px;">
                    <?php echo esc_html__("Default order status", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_defaultValue = $settings->getSettings("defaultOrderStatus");
                    $usbs_defaultValue = $usbs_defaultValue === null ? $settings->getField("general", "defaultOrderStatus", "wc-processing") : $usbs_defaultValue->value;
                    ?>
                    <select name="defaultOrderStatus">
                        <?php
                        foreach (SettingsHelper::getOrderStatuses() as $usbs_key => $usbs_value) {
                            $usbs_selected = "";
                            ?>

                            <?php ?>
                            <option value="<?php echo esc_html($usbs_key); ?>" <?php echo esc_html($usbs_selected); ?>><?php echo esc_html($usbs_value); ?></option>
                            <?php ?>
                            <?php
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <!-- New order default user -->
            <tr>
                <th scope="row" style="width: 240px;">
                    <?php echo esc_html__("New order default user", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_defaultValue = $settings->getSettings("nowOrderDefaultUser");
                    $usbs_defaultValue = $usbs_defaultValue === null ? "" : $usbs_defaultValue->value;
                    $usbs_userName = "";
                    if ($usbs_defaultValue) {
                        $usbs_user = get_user_by("ID", $usbs_defaultValue);
                        if ($usbs_user) {
                            $usbs_userName = $usbs_user->display_name . " (" . $usbs_user->user_login . ") - " . $usbs_user->user_email;
                        }
                    }
                    ?>
                    <span style="position: relative;">
                        <input type="text" value="<?php echo esc_html($usbs_userName); ?>" placeholder="<?php echo esc_html__("Find user", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>" class="order-default-user-search-input" />
                        <input type="hidden" name="nowOrderDefaultUser" value="<?php echo esc_html($usbs_defaultValue); ?>" class="order-default-user-id-search-input" />
                        <span style="position: relative;">
                            <span style="position: absolute; top: -5px; left: 0; display: none;" id="order-default-user-search-preloader">
                                <span id="barcode-scanner-action-preloader">
                                    <span class="a4b-action-preloader-icon"></span>
                                </span>
                            </span>
                        </span>
                        <ul class="order-default-users-search-list"></ul>
                        <div>
                            <i><?php echo esc_html__("Link this user (by default) to all newly created orders via Barcode Scanner popup.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>
                        </div>
                    </span>
                </td>
            </tr>
            <!-- Require to select user -->
            <tr id="bs_new_order_user_required">
                <th scope="row">
                    <?php echo esc_html__("Require to select user", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_defaultValue = $settings->getSettings("newOrderUserRequired");
                    $usbs_defaultValue = $usbs_defaultValue === null ? 'off' : $usbs_defaultValue->value;
                    ?>
                    <label>
                        <?php $usbs_checked = $usbs_defaultValue !== "off" ? ' checked=checked ' : ''; ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> onchange="WebbsSettingsCheckboxChange(`#bs_new_order_user_required input[name='newOrderUserRequired']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="newOrderUserRequired" value="<?php echo $usbs_checked ? "on" : "off"; ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </label>
                </td>
            </tr>
            <!-- Open order after creation -->
            <tr id="bs_open_order_after_creation">
                <th scope="row">
                    <?php echo esc_html__("Open order after creation", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_defaultValue = $settings->getSettings("openOrderAfterCreation");
                    $usbs_defaultValue = $usbs_defaultValue === null ? 'off' : $usbs_defaultValue->value;
                    ?>
                    <label>
                        <?php $usbs_checked = $usbs_defaultValue !== "off" ? ' checked=checked ' : ''; ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> onchange="WebbsSettingsCheckboxChange(`#bs_open_order_after_creation input[name='openOrderAfterCreation']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="openOrderAfterCreation" value="<?php echo esc_attr($usbs_checked ? "on" : "off"); ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </label>
                </td>
            </tr>
            <!-- Default product qty -->
            <tr>
                <th scope="row" style="width: 240px;">
                    <?php echo esc_html__("Default product qty", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_defaultValue = $settings->getSettings("defaultProductQty");
                    $usbs_defaultValue = $usbs_defaultValue === null ? "1" : $usbs_defaultValue->value;
                    ?>
                    <span>
                        <input type="text" name="defaultProductQty" value="<?php echo esc_attr($usbs_defaultValue); ?>" placeholder="<?php echo esc_attr("1"); ?>" style="width: 70px; height: 30px;" />
                        <span class="defaultProductQty_error_message"></span>
                        <div>
                            <i><?php echo esc_html__("Any added product (to the new order) will have the QTY equal to this setting by default.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>
                        </div>
                    </span>
                </td>
            </tr>

            <!-- Cart product qty step -->
            <tr>
                <th scope="row">
                    <?php echo esc_html__("Cart product qty step", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_defaultValue = $settings->getSettings("cartQtyStep");
                    $usbs_defaultValue = $usbs_defaultValue === null ? "" : $usbs_defaultValue->value;
                    ?>
                    <span>
                        <input type="text" name="cartQtyStep" value="<?php echo esc_html($usbs_defaultValue); ?>" placeholder="<?php echo esc_html__("Product custom meta field", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>" />
                    </span>
                    <br />
                    <i><?php echo esc_html__('Take into account "qty step" field of the product. It may be helpful if you sell items in boxes (e.g. products 10 per box) and you want to scan the barcode and to increase/decrease item by 10 in cart. Specify here the product meta field where "qty step"/"amount in the box" is specified.', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>
                </td>
            </tr>
            <!-- List of order statuses which considered as still not completed -->
            <tr>
                <th scope="row" style="width: 240px;">
                    <?php echo esc_html__("Item price priority", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td class="priceFieldPriority_row">
                    <?php
                    $usbs_field = $settings->getSettings("priceFieldPriority");
                    $usbs_priceFieldPriority = $usbs_field === null ? "wc_default" : $usbs_field->value;
                    ?>
                    <input type="hidden" name="priceFieldPriority" value="" />
                    <select class="priceFieldPriority" multiple="true" style="width:300px;">
                        <!-- default wclogic -->
                        <option value="wc_default"><?php echo esc_html_e("WooCommerce default", 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'); ?>
                        </option>
                        <!-- generated fields -->
                        <?php foreach ($interfaceData::getFields(true) as $usbs_field): ?>
                            <?php if ($usbs_field["type"] == "price" && $usbs_field["status"] == 1): ?>
                                <option value="<?php echo esc_attr($usbs_field["field_name"]); ?>">
                                    <?php ?>
                                    <?php echo esc_html($usbs_field["field_label"]); ?>
                                    <?php ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="priceFieldPriority" id="priceFieldPriority" value="<?php echo esc_html($usbs_priceFieldPriority); ?>">
                    <br />
                    <i><?php echo esc_html__("This price will be used in new orders. The first price in this list will have the highest priority.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>
                </td>
            </tr>
            <!-- Default order tax -->
            <?php
            $usbs_woocommerce_calc_taxes = get_option("woocommerce_calc_taxes");
            ?>
            <?php if ($usbs_woocommerce_calc_taxes == "yes"): ?>
                <tr>
                    <th scope="row" style="width: 240px;">
                        <?php echo esc_html__("Default order tax", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </th>
                    <td>
                        <?php
                        $usbs_defaultValue = $settings->getSettings("defaultOrderTax");
                        $usbs_defaultValue = $usbs_defaultValue === null ? 'based_on_store' : $usbs_defaultValue->value;
                        ?>
                        <select name="defaultOrderTax" style="max-width: 175px;">
                            <?php $usbs_selected = $usbs_defaultValue === "based_on_store" ? 'selected="selected"' : ""; ?>
                            <option value="based_on_store" <?php echo esc_html($usbs_selected); ?>>
                                <?php echo esc_html_e("Based on the store location", 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'); ?>
                            </option>
                            <?php $usbs_selected = $usbs_defaultValue === "no_tax" ? 'selected="selected"' : ""; ?>
                            <option value="no_tax" <?php echo esc_html($usbs_selected); ?>>
                                <?php echo esc_html_e("No Tax", 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'); ?>
                            </option>
                        </select>
                        <div>
                            <i><?php echo esc_html__("This tax will be used for new order which doesn't have billing address information yet.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            <!-- Default payment method -->
            <tr>
                <th scope="row" style="width: 240px; padding-top: 10px;">
                    <?php echo esc_html__("Default payment method", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td class="payment-methods" style="padding-top: 10px;">
                    <?php
                    $usbs_defaultValue = $settings->getSettings("defaultPaymentMethod");
                    $usbs_defaultValue = $usbs_defaultValue === null ? $settings->getField("general", "defaultPaymentMethod", "wc-processing") : $usbs_defaultValue->value;
                    ?>
                    <select name="defaultPaymentMethod">
                        <option value=""><?php echo esc_html__('Not selected', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'); ?></option>
                        <?php foreach ($cart->getPaymentMethods() as $usbs_value): ?>
                            <?php $usbs_selected = $usbs_defaultValue === $usbs_value['id'] ? ' selected=selected ' : ""; ?>
                            <option value="<?php echo esc_html($usbs_value['id']); ?>" <?php echo esc_html($usbs_selected); ?>>
                                <?php ?>
                                <?php echo esc_html($usbs_value['title']); ?>
                                <?php ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <!-- Require Payment Method -->
            <tr id="bs_payment_required">
                <th scope="row">
                    <?php echo esc_html__("Payment method is required", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_defaultValue = $settings->getSettings("paymentRequired");
                    $usbs_defaultValue = $usbs_defaultValue === null ? 'off' : $usbs_defaultValue->value;
                    ?>
                    <label>
                        <?php $usbs_checked = $usbs_defaultValue !== "off" ? ' checked=checked ' : ''; ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> onchange="WebbsSettingsCheckboxChange(`#bs_payment_required input[name='paymentRequired']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="paymentRequired" value="<?php echo $usbs_checked ? "on" : "off"; ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </label>
                </td>
            </tr>
            <!-- Default shipping method -->
            <tr>
                <th scope="row" style="width: 240px; padding-top: 10px;">
                    <?php echo esc_html__("Default shipping method", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td class="shipping-methods" style="padding-top: 10px;">
                    <?php
                    $usbs_shippingsMethods = $settings->getAllShippingMethod();

                    $usbs_field = $settings->getSettings("defaultShippingMethods");
                    $usbs_shippingMethodsValue = $usbs_field === null ? "" : $usbs_field->value;
                    $usbs_shippingMethodsValueArr = $usbs_shippingMethodsValue ? explode(",", $usbs_shippingMethodsValue) : array();

                    usort($usbs_shippingsMethods, function ($a, $b) use ($usbs_shippingMethodsValueArr) {
                        $aIndex = array_search($a['id'], $usbs_shippingMethodsValueArr);
                        $bIndex = array_search($b['id'], $usbs_shippingMethodsValueArr);

                        $aIndex = ($aIndex === false) ? PHP_INT_MAX : $aIndex;
                        $bIndex = ($bIndex === false) ? PHP_INT_MAX : $bIndex;

                        return $aIndex - $bIndex;
                    });
                    ?>
                    <?php
                    $usbs_customShippingStatuses = $settings->getSettings("customShippingStatuses");
                    $usbs_customShippingStatuses = $usbs_customShippingStatuses === null ? "{}" : str_replace("'", '"', $usbs_customShippingStatuses->value);
                    $usbs_customShippingStatuses = json_decode($usbs_customShippingStatuses, true);
                    ?>
                    <input type="hidden" name='defaultShippingMethods[]' value="<?php echo esc_attr($usbs_shippingMethodsValue); ?>" />
                    <select multiple data-placeholder='Choose a shipping...' multiple class='chosen-select-shipping-methods' style="width:300px;">
                        <?php foreach ($usbs_shippingsMethods as $usbs_method): ?>
                            <option value="<?php echo esc_attr($usbs_method['id']); ?>"><?php echo esc_html($usbs_method['title']); ?>
                            </option>
                        <?php endforeach; ?>
                        <?php $usbs_shippingMethodIndex = 0; ?>
                        <?php if ($usbs_customShippingStatuses && is_array($usbs_customShippingStatuses)): ?>
                            <?php foreach ($usbs_customShippingStatuses as $usbs_method): ?>
                                <?php
                                $usbs_value = "cs_" . $usbs_shippingMethodIndex . ":0";
                                $usbs_shippingMethodIndex++;
                                ?>
                                <option value="<?php echo esc_attr($usbs_value); ?>"><?php echo esc_html($usbs_method['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </td>
            </tr>
            <!-- Shipping method is required -->
            <tr id="bs_shipping_required">
                <th scope="row">
                    <?php echo esc_html__("Shipping method is required", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_defaultValue = $settings->getSettings("shippingRequired");
                    $usbs_defaultValue = $usbs_defaultValue === null ? 'off' : $usbs_defaultValue->value;
                    ?>
                    <label>
                        <?php $usbs_checked = $usbs_defaultValue !== "off" ? ' checked=checked ' : ''; ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> onchange="WebbsSettingsCheckboxChange(`#bs_shipping_required input[name='shippingRequired']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="shippingRequired" value="<?php echo esc_attr($usbs_checked ? "on" : "off"); ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </label>
                </td>
            </tr>
            <!-- Send new order email to admin - Disable by default -->
            <tr id="bs_send_email_for_created_order">
                <th scope="row">
                    <?php echo esc_html__("Send new order email to admin", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_defaultValue = $settings->getSettings("sendAdminEmailCreatedOrder");
                    $usbs_defaultValue = $usbs_defaultValue === null ? 'off' : $usbs_defaultValue->value;
                    ?>
                    <label>
                        <?php $usbs_checked = $usbs_defaultValue !== "off" ? ' checked=checked ' : ''; ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> onchange="WebbsSettingsCheckboxChange(`#bs_send_email_for_created_order input[name='sendAdminEmailCreatedOrder']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="sendAdminEmailCreatedOrder" value="<?php echo $usbs_checked ? "on" : "off"; ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </label>
                </td>
            </tr>
            <!-- Send new order email to client - Enable by default -->
            <tr id="bs_send_email_for_created_order">
                <th scope="row">
                    <?php echo esc_html__("Send new order email to client", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_defaultValue = $settings->getSettings("sendClientEmailCreatedOrder");
                    $usbs_defaultValue = $usbs_defaultValue === null ? 'on' : $usbs_defaultValue->value;
                    ?>
                    <label>
                        <?php $usbs_checked = $usbs_defaultValue !== "off" ? ' checked=checked ' : ''; ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> onchange="WebbsSettingsCheckboxChange(`#bs_send_email_for_created_order input[name='sendClientEmailCreatedOrder']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="sendClientEmailCreatedOrder" value="<?php echo $usbs_checked ? "on" : "off"; ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </label>
                </td>
            </tr>
            <!-- PDF Invoices & Packing Slips for WooCommerce -->
            <?php if (function_exists("wcpdf_get_document")): ?>
                <tr id="autoGenInvoice">
                    <th scope="row">
                        <?php echo esc_html__('PDF Invoices & Packing Slips for WooCommerce', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </th>
                    <td>
                        <?php
                        $usbs_defaultValue = $settings->getSettings("autoGenInvoice");
                        $usbs_defaultValue = $usbs_defaultValue === null ? 'on' : $usbs_defaultValue->value;
                        ?>
                        <label>
                            <?php $usbs_checked = $usbs_defaultValue !== "off" ? ' checked=checked ' : ''; ?>
                            <input type="checkbox" <?php echo esc_html($usbs_checked); ?> onchange="WebbsSettingsCheckboxChange(`#autoGenInvoice input[name='autoGenInvoice'
                            ]`,this.checked ? 'on' : 'off' )" />
                            <input type="hidden" name="autoGenInvoice" value="<?php echo $usbs_checked ? "on" : "off"; ?>" />
                            <?php echo esc_html__("Generate invoice after order creation", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                        </label>
                    </td>
                </tr>
            <?php endif; ?>

            <tr class="usbs-section-label">
                <td>
                    <h2><?php echo esc_html__("Shipping", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></h2>
                </td>
            </tr>
            <!-- New shipping method -->
            <tr id="bs_new_shipping_statuses">
                <th scope="row">
                    <?php echo esc_html__("New shipping method", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_customShippingStatuses = $settings->getSettings("customShippingStatuses");
                    $usbs_customShippingStatuses = $usbs_customShippingStatuses === null ? "{}" : str_replace("'", '"', $usbs_customShippingStatuses->value);
                    $usbs_customShippingStatuses = json_decode($usbs_customShippingStatuses, true);
                    ?>
                    <input type="hidden" name="customShippingStatuses" value="[]" />
                    <div id="bs_custom_shipping_status_wrapper"></div>
                    <div>
                        <button id="bs_add_custom_shipping_status"><?php echo esc_html__("Add new", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></button>
                    </div>
                    <div>
                        <i><?php echo esc_html__("Specify new shipping methods, they will appear in the barcode scanner", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <div class="submit">
        <input type="submit" class="button button-primary" value="<?php echo esc_html__("Save Changes", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>">
    </div>
</form>
<style>
    .order-default-user-search-input {
        min-width: 250px;
    }
</style>
<script>
    jQuery(document).ready(() => {
        const chosenOption = {
            width: "300px",
            no_results_text: "Loading:"
        };
        jQuery(".chosen-select-shipping-methods").chosen(chosenOption);

        const listUpdated = () => {
            const selectedListEl = jQuery('.shipping-methods .chosen-container .chosen-choices');
            const optionsEl = selectedListEl.find('.search-choice');
            let values = '';
            let orderIndex = [];
            let options = [];

            for (let index = 0; index < optionsEl.length; index++) {
                const element = jQuery(optionsEl[index]).find('a[data-option-array-index]');
                const optionIndex = element.attr('data-option-array-index');
                orderIndex.push(optionIndex)
            }

            var allOptions = jQuery(".chosen-select-shipping-methods option");
            for (let index = 0; index < allOptions.length; index++) {
                const id = jQuery(allOptions[index]).attr('value');
                options.push(id);
            }

            const sortedOptions = orderIndex.map(index => options[index]);

            const selectedItems = jQuery(".chosen-select-shipping-methods").val();

            jQuery('input[name="defaultShippingMethods[]"]').val(sortedOptions.join(','))
        };

        let timer = null;
        jQuery(".chosen-select-shipping-methods").on('change', (event, params) => {
            if (timer) clearTimeout(timer);
            timer = setTimeout(listUpdated, 50);
        });

        var defaultValue = '<?php echo wp_kses_post($usbs_shippingMethodsValue); ?>'.split(',');
        jQuery(".chosen-select-shipping-methods").val(defaultValue).trigger("chosen:updated");
    });
</script>
<script>
    jQuery(document).ready(function () {
        jQuery(".usbs_order_statuses_are_still_not_complected").chosen({
            search_contains: true,
            no_results_text: "<?php echo esc_html__("Nothing found for:", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?> ",
            width: "300px"
        });
        var defaultValue = '<?php echo esc_html($usbs_orderStatusesAreStillNotCompletedValue); ?>'.split(',');
        jQuery(".usbs_order_statuses_are_still_not_complected").val(defaultValue).trigger("chosen:updated");
    });
</script>
<script>
    jQuery(document).ready(function () {
        var savedOrder = '<?php echo esc_html($usbs_priceFieldPriority); ?>'.split(',');
        var $select = jQuery(".priceFieldPriority");
        savedOrder.forEach(function (val) {
            var $opt = $select.find('option[value="' + val + '"]');
            if ($opt.length) $select.append($opt);
        });
        $select.val(savedOrder);
        $select.chosen({ search_contains: true, no_results_text: "<?php echo esc_html__("Nothing found for:", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?> ", width: "300px" });
        var order = savedOrder.slice();
        jQuery("#priceFieldPriority").val(order.join(","));
        $select.on("change", function (e, params) {
            if (params?.selected) order.push(params.selected);
            if (params?.deselected) order = order.filter(v => v !== params.deselected);
            jQuery("#priceFieldPriority").val(order.join(","));
        });
    });
</script>
<script>
    const priceValidator = (value, maxValue) => {
        try {
            if (value == "") {
                return {
                    status: true,
                    message: ""
                };
            }

            const pluginData = usbs;
            let invalidMsg = "";

            if (pluginData.priceDecimalSeparator == ",") invalidMsg = '<?php echo esc_html__('Use decimal separator comma "," and don\'t use thousand separators', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>';
            else if (pluginData.priceDecimalSeparator == ".") invalidMsg = '<?php echo esc_html__('Use decimal separator period "." and don\'t use thousand separators', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>';
            else invalidMsg = '<?php echo esc_html__('Use decimal separator and don\'t use thousand separators', "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>';


            const incorrectMsg = '<?php echo esc_html__("Sale price should be smaller then Regular price.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>';
            const parts = `${value}`.split(pluginData.priceDecimalSeparator);
            let regChecker = false;

            if (parts.length != 1 && parts.length != 2) regChecker = false;
            else if (parts.length == 1) regChecker = new RegExp(/^\d+$/, 'gi').test(parts[0]);
            else if (parts.length == 2) regChecker = new RegExp(/^\d+$/, 'gi').test(parts[0]) && (parts[1] == "" || new RegExp(/^\d+$/, 'gi').test(parts[1]));


            if (!regChecker) return {
                status: false,
                message: invalidMsg
            };
            else if (maxValue) {
                const value1 = parseFloat(value.replace(pluginData.priceDecimalSeparator, "."));
                const value2 = parseFloat(maxValue.replace(pluginData.priceDecimalSeparator, "."));
                if (maxValue && value1 >= value2) return {
                    status: false,
                    message: incorrectMsg
                };
            }

            return {
                status: true,
                message: ""
            };
        } catch (error) {
            return {
                status: false,
                message: error.message
            };
        }
    }

    jQuery(document).ready(function () {
        const validDefaultProductQty = (event, params) => {
            jQuery('.defaultProductQty_error_message').text("");

            const validator = priceValidator(event.target.value, "");
            if (!validator.status && validator.message) {
                jQuery('.defaultProductQty_error_message').html(validator.message);
                return;
            }
        };

        const defaultProductQtyOnBlur = (event) => {
            const validator = priceValidator(event.target.value, "");
            if (!validator.status && validator.message) {
                jQuery('.defaultProductQty_error_message').text("");
                jQuery(event.target).val(1);
            }
        };

        jQuery('input[name="defaultProductQty"]').on('change', validDefaultProductQty);
        jQuery('input[name="defaultProductQty"]').on('keyup', validDefaultProductQty);
        jQuery('input[name="defaultProductQty"]').on('blur', defaultProductQtyOnBlur);

    });
</script>
<script>
    jQuery(document).ready(function () {
        let shippingStatusIndex = 1;

        const addCustomShippingStatus = (event) => {
            event.preventDefault();

            let wrapper = jQuery("#bs_custom_shipping_status_wrapper");

            const html = `
                <div class="bs-shipping-status-row" data-index="${shippingStatusIndex}">
                    <input type="text" name="customShippingStatuses[${shippingStatusIndex}][label]" placeholder="Label"/>
                    <input type="number" step="0.01" name="customShippingStatuses[${shippingStatusIndex}][price]" placeholder="Price" style="width: 100px" />
                    <label>
                        <input type="checkbox" name="customShippingStatuses[${shippingStatusIndex}][active]" value="1" checked="checked" title="Enabled/Disabled" />
                    </label>
                    <button type="button" class="bs-remove-shipping-status">Delete</button>
                </div>
            `;

            wrapper.append(html);
            shippingStatusIndex++;
        };

        const restoreCustomShippingStatuses = () => {
            let statuses = <?php echo wp_json_encode($usbs_customShippingStatuses); ?>;
            if (statuses) statuses = Object.values(statuses);
            const wrapper = jQuery("#bs_custom_shipping_status_wrapper");

            statuses.forEach((status) => {
                const html = `
                    <div class="bs-shipping-status-row" data-index="${shippingStatusIndex}">
                        <input type="text" name="customShippingStatuses[${shippingStatusIndex}][label]" placeholder="Label" value="${status.label || ''}" />
                        <input type="number" step="0.01" name="customShippingStatuses[${shippingStatusIndex}][price]" placeholder="Price" style="width: 100px" value="${status.price || ''}" />
                        <label>
                            <input type="checkbox" name="customShippingStatuses[${shippingStatusIndex}][active]" value="1" ${status.active ? 'checked' : ''} title="Enabled/Disabled" />
                        </label>
                        <button type="button" class="bs-remove-shipping-status">Delete</button>
                    </div>
                `;

                wrapper.append(html);
                shippingStatusIndex++;
            });
        };

        jQuery('button#bs_add_custom_shipping_status').on('click', addCustomShippingStatus);
        jQuery(document).on("click", ".bs-remove-shipping-status", function () {
            if (confirm('Are you sure to delete this shipping?')) jQuery(this).closest(".bs-shipping-status-row").remove();
        });

        restoreCustomShippingStatuses();
    });
</script>
<style>
    .defaultProductQty_error_message {
        color: red;
        font-style: italic;
    }

    #bs_custom_shipping_status_wrapper {
        display: flex;
        flex-direction: column;
        gap: 5px;
        margin-bottom: 5px;
    }

    .bs-remove-shipping-status {
        margin-left: 15px !important;
    }
</style>