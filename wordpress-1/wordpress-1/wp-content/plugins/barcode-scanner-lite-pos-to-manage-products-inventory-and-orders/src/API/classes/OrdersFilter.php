<?php

namespace UkrSolution\BarcodeScanner\API\classes;

use UkrSolution\BarcodeScanner\Database;

class OrdersFilter
{
    public static function getFilter()
    {
        $filter = array();

        $filter[] = array(
            "id" => "status",
            "label" => __("Status", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"),
            "is_default" => 1,
        );

        $filter[] = array(
            "id" => "from_date",
            "label" => __("From", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"),
            "is_default" => 1,
        );

        $filter[] = array(
            "id" => "to_date",
            "label" => __("To", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"),
            "is_default" => 1,
        );

        $filter[] = array(
            "id" => "wp_user",
            "label" => __("WP User", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"),
            "placeholder" => __("User name", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"),
            "is_default" => 1,
        );

        $filter = apply_filters("barcode_scanner_orders_filter", $filter);

        return self::removeNonexistentFields($filter);
    }

    private static function removeNonexistentFields($filter) {
        global $wpdb;

        // @codingStandardsIgnoreStart
        $customFields = $wpdb->get_results("SELECT `name` FROM {$wpdb->prefix}barcode_scanner_posts_columns;");
        // @codingStandardsIgnoreEnd
        $mainMetaFields = Database::$postMetaFields;

        foreach ($filter as $key => $field) {
            if (isset($field["is_default"]) && $field["is_default"]) continue;

            foreach ($mainMetaFields as $mainMetaField) {
                if ($field["id"] == str_replace("`", "", $mainMetaField)) continue 2;
            }

            if (!in_array($field["id"], array_column($customFields, "name"))) {
                unset($filter[$key]);
            }
        }    

        return $filter;
    }
}
