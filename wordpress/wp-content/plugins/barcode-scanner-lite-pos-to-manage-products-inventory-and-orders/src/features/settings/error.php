<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div id="bs-settings-page">
    <h2><?php echo esc_html($title); ?></h2>
    <div>
        <?php echo wp_kses_post($error['message']); ?>
    </div>
</div>