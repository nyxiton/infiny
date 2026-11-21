<?php

if (!defined('ABSPATH')) {
    exit;
}

?>
<a href="#?p=barcode-scanner-frontend" data-plugin="barcode-scanner-frontend" id="<?php echo $autoShow === true ? esc_attr('barcode-scanner-auto-show') : ''; ?>" style="<?php echo $showLink === "" ? esc_attr('display:none;') : ''; ?>"><?php echo esc_html($showLink); ?></a>