<?php

namespace UkrSolution\BarcodeScanner\features\orders;

use UkrSolution\BarcodeScanner\API\classes\OrdersHelper;
use UkrSolution\BarcodeScanner\features\settings\Settings;

class Orders
{
    public $settings;
    public $postId = null;

    function __construct()
    {
    }

    public function woocommerce_shop_order_list_table_custom_column($column, $order)
    {
    }

    public function manage_shop_order_posts_custom_column($column)
    {
    }

    public function manage_shop_order_posts_custom_column_style()
    {
    }

    public function manage_edit_shop_order_columns($columns)
    {
    }
}
