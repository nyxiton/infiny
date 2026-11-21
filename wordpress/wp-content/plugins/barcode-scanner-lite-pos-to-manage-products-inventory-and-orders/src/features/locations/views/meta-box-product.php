<?php

if (!defined('ABSPATH')) {
    exit;
}

$usbs_locations_nonce = wp_create_nonce('usbs_locations_nonce');

?>

<div style="display: flex; flex-wrap: wrap;">
    <?php foreach ($this->get() as $usbs_location): ?>
        <?php if (trim($usbs_location->name)): ?>
            <div style="padding-right: 15px;">
                <?php echo esc_html($usbs_location->name); ?><br />
                <input type="text" name="<?php echo esc_attr($usbs_location->slug); ?>" value="<?php echo esc_attr(get_post_meta($post->ID, $usbs_location->slug, true)); ?>" />
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
    <input type="hidden" name="usbs_locations_nonce" value="<?php echo esc_attr($usbs_locations_nonce); ?>" />
</div>