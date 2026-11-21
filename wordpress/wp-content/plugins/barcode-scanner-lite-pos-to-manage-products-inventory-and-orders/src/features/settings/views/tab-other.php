<?php

use UkrSolution\BarcodeScanner\API\RequestHelper;
use UkrSolution\BarcodeScanner\features\sounds\Sounds;

if (!defined('ABSPATH')) {
    exit;
}
?>
<form id="bs-settings-css-tab" method="POST" action="<?php echo esc_url($usbs_actualLink); ?>" enctype="multipart/form-data">
    <input type="hidden" name="tab" value="css" />
    <input type="hidden" name="storage" value="table" />
    <input type="hidden" name="nonce" value="<?php echo esc_attr($usbs_nonce); ?>" />
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row" style="width: 240px;">
                    <?php echo esc_html__("Custom CSS", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php $usbs_customCss = $settings->getSettings("customCss"); ?>
                    <textarea name="customCss" rows="6" cols="60"><?php echo $usbs_customCss ? wp_kses_post(stripslashes($usbs_customCss->value)) : ""; ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row" style="width: 240px;">
                    <?php echo esc_html__("Custom CSS for Mobile app", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php $usbs_customCssMobile = $settings->getSettings("customCssMobile"); ?>
                    <textarea name="customCssMobile" rows="6" cols="60"><?php echo $usbs_customCssMobile ? wp_kses_post(stripslashes($usbs_customCssMobile->value)) : ""; ?></textarea>
                </td>
            </tr>
            <tr id="bs_debug">
                <th scope="row">
                    <?php echo esc_html__("Sound effects", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_sounds = new Sounds();
                    $usbs_list = $usbs_sounds->getList();
                    ?>
                    <!-- Increase -->
                    <div class="sound-block">
                        <!-- preview -->
                        <div style="display: flex; align-items: center;">
                            <b><?php echo esc_html__("Success", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></b>
                            <audio controls>
                                <source src="<?php echo wp_kses_post($usbs_list["increase"]); ?>" type="audio/mpeg">
                                <?php echo esc_html__("Your browser does not support the audio element.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                            </audio>
                        </div>
                        <!-- upload -->
                        <div style="display: flex; align-items: center;">
                            <b><?php echo esc_html__("Upload new", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></b> &nbsp;
                            <input type="file" accept=".mp3" name="increaseFile" />
                        </div>
                    </div><br />

                    <!-- Decrease -->
                    <div class="sound-block">
                        <!-- preview -->
                        <div style="display: flex; align-items: center;">
                            <b><?php echo esc_html__("Decrease value", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></b>
                            <audio controls>
                                <source src="<?php echo wp_kses_post($usbs_list["decrease"]); ?>" type="audio/mpeg">
                                <?php echo esc_html__("Your browser does not support the audio element.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                            </audio>
                        </div>
                        <!-- upload -->
                        <div style="display: flex; align-items: center;">
                            <b><?php echo esc_html__("Upload new", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></b> &nbsp;
                            <input type="file" accept=".mp3" name="decreaseFile" />
                        </div>
                    </div><br />

                    <!-- Fail -->
                    <div class="sound-block">
                        <!-- preview -->
                        <div style="display: flex; align-items: center;">
                            <b><?php echo esc_html__("Fail", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></b>
                            <audio controls>
                                <source src="<?php echo wp_kses_post($usbs_list["fail"]); ?>" type="audio/mpeg">
                                <?php echo esc_html__("Your browser does not support the audio element.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                            </audio>
                        </div>
                        <!-- upload -->
                        <div style="display: flex; align-items: center;">
                            <b><?php echo esc_html__("Upload new", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></b> &nbsp;
                            <input type="file" accept=".mp3" name="failFile" />
                        </div>
                    </div><br />

                    <!-- FF End -->
                    <div class="sound-block">
                        <!-- preview -->
                        <div style="display: flex; align-items: center;">
                            <b><?php echo esc_html__("Order fulfilled", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></b>
                            <audio controls>
                                <source src="<?php echo wp_kses_post($usbs_list["ffEnd"]); ?>" type="audio/mpeg">
                                <?php echo esc_html__("Your browser does not support the audio element.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                            </audio>
                        </div>
                        <!-- upload -->
                        <div style="display: flex; align-items: center;">
                            <b><?php echo esc_html__("Upload new", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></b> &nbsp;
                            <input type="file" accept=".mp3" name="ffEndFile" />
                        </div>
                    </div>
                </td>
            </tr>
            <tr id="disabledIndexation">
                <th scope="row">
                    <?php echo esc_html__("Disable indexation ajax request", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <label>
                        <?php
                        $usbs_defaultValue = $settings->getSettings("disabledIndexation");
                        $usbs_defaultValue = $usbs_defaultValue === null ? 'off' : $usbs_defaultValue->value;
                        $usbs_checked = $usbs_defaultValue !== "off" ? ' checked=checked ' : '';
                        ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> onchange="WebbsSettingsCheckboxChange(`#disabledIndexation input[name='disabledIndexation']`, this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="disabledIndexation" value="<?php echo esc_attr($usbs_checked ? "on" : "off"); ?>" />
                        <?php echo esc_html__("Disabled", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
                <th scope="row">
                    <?php echo esc_html__("Update history", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php
                    $usbs_field = $settings->getSettings("updateHistory");
                    $usbs_value = $usbs_field === null ? "" : $usbs_field->value;
                    $usbs_value = $usbs_value ? explode(",", $usbs_value) : [];

                    if (empty($usbs_value)) {
                        echo "-";
                    } else {
                        foreach ($usbs_value as $usbs_item) {
                            ?>
                            <div><?php echo esc_html($usbs_item); ?></div><?php
                        }
                    }
                    ?>
                </td>
            </tr>
            <tr class="usbs-section-label">
                <td>
                    <h2><?php echo esc_html__("Debug", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></h2>
                </td>
            </tr>
            <tr id="bs_debug">
                <th scope="row">
                    <?php echo esc_html__("Debug information", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <label>
                        <?php
                        $usbs_field = $settings->getSettings("debugInfo");
                        $usbs_debugInfoValue = $usbs_field === null ? $settings->getField("general", "debugInfo", "") : $usbs_field->value;

                        if ($usbs_debugInfoValue === "on") {
                            $usbs_checked = ' checked=checked ';
                        } else {
                            $usbs_checked = '';
                        }
                        ?>
                        <input type="checkbox" <?php echo esc_html($usbs_checked); ?> onchange="WebbsSettingsCheckboxChange(`#bs_debug input[name='debugInfo']`,this.checked ? 'on' : 'off')" />
                        <input type="hidden" name="debugInfo" value="<?php echo esc_attr($usbs_checked ? "on" : "off"); ?>" />
                        <?php echo esc_html__("Enable", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </label>
                </td>
            </tr>
            <?php if ($usbs_debugInfoValue === "on" && RequestHelper::checkFile()): ?>
                <tr id="bs_debug">
                    <th scope="row"> </th>
                    <td>
                        <?php $usbs_link = admin_url("/admin.php?page=barcode-scanner-download-log&tab=css&nonce=" . esc_attr($usbs_nonce)); ?>
                        <a href="<?php echo esc_url($usbs_link); ?>"><?php echo esc_html__("Download log", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></a>
                    </td>
                </tr>
            <?php endif; ?>
            <tr id="bs_debug">
                <th scope="row">
                    <?php echo esc_html__("Reset all settings to default", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                </th>
                <td>
                    <?php $usbs_link = admin_url("/admin.php?page=barcode-scanner-settings-reset&tab=css&nonce=" . esc_attr($usbs_nonce)); ?>
                    <a href="<?php echo esc_url($usbs_link); ?>" class="usbs-btn" id="reset-all-settings-to-default"
                        onclick="return confirm('<?php echo esc_html__("All current configuration will be ERASED and replaced with the default one, proceed?", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>');"><?php echo esc_html__("Reset", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></a>

                </td>
            </tr>
            <?php if ($wpml): ?>
                <tr id="bs_wpml">
                    <th scope="row">
                        <?php echo esc_html__("wpml languages", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                    </th>
                    <td>
                        <label>
                            <?php
                            $usbs_field = $settings->getSettings("wpmlUpdateProductsTree");
                            $usbs_value = $usbs_field === null ? $settings->getField("general", "wpmlUpdateProductsTree", "") : $usbs_field->value;

                            if ($usbs_value === "on") {
                                $usbs_checked = ' checked=checked ';
                            } else {
                                $usbs_checked = '';
                            }
                            ?>
                            <input type="checkbox" <?php echo esc_html($usbs_checked); ?> onchange="WebbsSettingsCheckboxChange(`#bs_wpml input[name='wpmlUpdateProductsTree']`,this.checked ? 'on' : 'off')" />
                            <input type="hidden" name="wpmlUpdateProductsTree" value="<?php echo esc_attr($usbs_checked ? "on" : "off"); ?>" />
                            <?php echo esc_html__("Update all products", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>
                        </label><br />
                        <i><?php echo esc_html__("If your wpml already configured to sync data between product translations,<br/>then you DON'T need to enable this option as it may cause double increase of qty.", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?></i>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="submit">
        <input type="submit" class="button button-primary" value="<?php echo esc_html__("Save Changes", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"); ?>">
    </div>
</form>