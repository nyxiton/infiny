<?php

if (!defined('ABSPATH')) {
    exit;
}

$usbs_locations_nonce = wp_create_nonce('usbs_locations_nonce');

?>
<style>
    .usbs_stock_location_levels>div>div:nth-child(2) {
        padding: 0 15px;
    }
</style>
<div class="form-field form-row form-row-full usbs_stock_location_levels">
    <div style="display: flex; justify-content: space-between;">
        <?php foreach ($this->get() as $usbs_location): ?>
            <?php if (trim($usbs_location->name)): ?>
                <div style="width: 100%;">
                    <?php echo esc_html($usbs_location->name); ?><br />
                    <input type="text" name="v_<?php echo esc_attr($usbs_location->slug); ?>[<?php echo esc_attr($loop); ?>]" value="<?php echo esc_attr(get_post_meta($variation->ID, $usbs_location->slug, true)); ?>" />
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <input type="hidden" name="usbs_locations_nonce" value="<?php echo esc_attr($usbs_locations_nonce); ?>" />
    </div>
</div>