<?php

namespace UkrSolution\BarcodeScanner\features\interfaceData;

use UkrSolution\BarcodeScanner\API\classes\Users;
use UkrSolution\BarcodeScanner\API\PluginsHelper;
use UkrSolution\BarcodeScanner\Database;

class InterfaceData
{
    public static $filter_dropdown_options = 'scanner_dropdown_%field_options';

    private static $allFields = array();
    private static $plugin = "";

    public static function getFields($addTranslations = false, $plugin = "", $isReload = false, $role = null, $defaultIsEmpty = true)
    {
        global $wpdb;

        $tab = '';
        $subTab = '';
        $role = $role ? $role : '';



        $isRemoveFields = '';
        $isLoadDefault = '';


        $table = $wpdb->prefix . Database::$interface;

        if (!isset(self::$allFields[$role])) {
            self::$allFields[$role] = array();
        }


        $orderKey = $plugin == "mobile" ? "order_mobile" : "order";

        if (self::$allFields[$role] && self::$plugin == $plugin && !$isReload) {
            usort(self::$allFields[$role], function ($a, $b) use ($orderKey) {
                return $a[$orderKey] && $b[$orderKey] && $a[$orderKey] < $b[$orderKey] ? 1 : 0;
            });

            return self::$allFields[$role];
        }


        // @codingStandardsIgnoreStart
        $fields = $wpdb->get_results("SELECT *, `disabled_field` as 'read_only' FROM {$table} WHERE `role` IS NUll ORDER BY `order` DESC;", ARRAY_A);
        // @codingStandardsIgnoreEnd



        if (!$fields && $defaultIsEmpty && $role && $role != 'default') {
            return self::getFields($addTranslations, $plugin, $isReload, "default", false);
        }

        foreach ($fields as &$value) {
            if ($addTranslations && isset($value["field_label"])) {
            }

            if ($value["type"] != "select") {
                continue;
            }

            $options = $value["options"] ? @json_decode($value["options"], false) : array();

            $filterName = str_replace("%field", $value["field_name"], self::$filter_dropdown_options);

            $filteredOptions = (array) $options;

            $value["options"] = json_encode($filteredOptions);
        }

        self::$allFields[$role] = $fields ? $fields : array();
        self::$plugin = $plugin;

        usort(self::$allFields[$role], function ($a, $b) use ($orderKey) {
            return $a[$orderKey] && $b[$orderKey] && $a[$orderKey] < $b[$orderKey] ? 1 : 0;
        });

        return self::$allFields[$role];
    }

    public static function getField($fieldName)
    {
        global $wpdb;

        // @codingStandardsIgnoreStart
        $table = $wpdb->prefix . Database::$interface;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE `field_name` = '%s';", $fieldName), ARRAY_A);
        // @codingStandardsIgnoreEnd
    }

    public static function saveFields($fields, $role)
    {
        global $wpdb;

        $table = $wpdb->prefix . Database::$interface;
        $dt = new \DateTime("now");
        $created = $dt->format("Y-m-d H:i:s");
        $deviceActive = '';


        foreach ($fields as $id => $value) {
            $data = array(
                "field_label" => $value["field_label"],
                "field_name" => in_array($value["type"], array("taxonomy", "taxonomy_term")) ? $value["taxonomy_field_name"] : $value["field_name"],
                "term" => $value["term"],
                "type" => $value["type"],
                "label_position" => isset($value["label_position"]) ? $value["label_position"] : "",
                "field_height" => $value["field_height"],
                "label_width" => $value["label_width"],
                "textarea_lines" => isset($value["textarea_lines"]) ? $value["textarea_lines"] : 3,
                "show_in_create_order" => isset($value["show_in_create_order"]) ? $value["show_in_create_order"] : "",
                "show_in_products_list" => $value["show_in_products_list"],
                "read_only" => $value["read_only"],
                "disabled_field" => $value["read_only"],
                "role" => !$role || $role == 'default' ? null : $role,
                "updated" => $created,
                "attribute_id" => $value["attribute_id"] ? $value["attribute_id"] : null,
                "button_js" => $value["button_js"] ? $value["button_js"] : null,
                "button_width" => $value["button_width"] ? $value["button_width"] : null,
            );

            if (isset($value['status'])) {
                $data['status'] = $value["status"];
            }
            if (isset($value['mobile_status'])) {
                $data['mobile_status'] = $value["mobile_status"];
            }

            if (isset($value['order'])) {
                $data['order'] = $value["order"];
            }
            if (isset($value['order_mobile'])) {
                $data['order_mobile'] = $value["order_mobile"];
            }

            if ($deviceActive == "desktop" && isset($value['position'])) {
                $data['position'] = $value["position"];
            }

            if (isset($value['show_on_mobile_preview'])) {
                $data['show_on_mobile_preview'] = $value["show_on_mobile_preview"];
            }

            $options = array();

            if (isset($value["options"]) && is_array($value["options"])) {
                foreach ($value["options"] as $option) {
                    $options[$option["key"]] = $option["value"];
                }

                $data["options"] = json_encode($options);
            }



            if ($value["remove"] == 1) {
                // @codingStandardsIgnoreStart
                $wpdb->delete($table, array("id" => $id));
                // @codingStandardsIgnoreEnd
            } else if (preg_match("/^[0-9]+$/", $id, $m)) {
                // @codingStandardsIgnoreStart
                $wpdb->update($table, $data, array("id" => $id));
                // @codingStandardsIgnoreEnd
            } else {
                if (!isset($data["position"])) {
                    $data["position"] = "product-middle-left";
                }

                // @codingStandardsIgnoreStart
                $wpdb->insert($table, $data);
                // @codingStandardsIgnoreEnd
            }
        }

        self::generateFieldsTranslationsFile($fields);
    }

    public static function getFieldForAutoAction()
    {
        global $wpdb;

        // @codingStandardsIgnoreStart
        $table = $wpdb->prefix . Database::$interface;
        return $wpdb->get_row("SELECT * FROM {$table} WHERE `use_for_auto_action` = '1';");
        // @codingStandardsIgnoreEnd
    }

    public static function generateFieldsTranslationsFile($fields)
    {
    }

    public static function getUserFields()
    {
        $result = array();


        return $result;
    }

    public static function getOrderFields()
    {
        $result = array();


        return $result;
    }

    public static function getUserFormFields()
    {
        $result = array();

        $result[] = array(
            "id" => "first_name",
            "label" => __("First name", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"),
            "name" => "first_name",
            "position" => "user_section",
            "type" => "text",
            "required" => 0,
            "default" => "",
            "inputClass" => "addr-form-first_name",
            "placeholder" => __("Enter first name", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"),
        );

        $result[] = array(
            "id" => "last_name",
            "label" => __("Last name", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"),
            "name" => "last_name",
            "position" => "user_section",
            "type" => "text",
            "required" => 0,
            "default" => "",
            "inputClass" => "addr-form-last_name",
            "placeholder" => __("Enter last name", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"),
        );

        $result[] = array(
            "id" => "username",
            "label" => "* " . __("Username", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"),
            "name" => "username",
            "position" => "user_section",
            "type" => "text",
            "required" => 1,
            "default" => "",
            "inputClass" => "addr-form-username",
            "placeholder" => __("Enter username", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"),
        );

        $result[] = array(
            "id" => "email",
            "label" => __("Email", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"),
            "name" => "email",
            "position" => "user_section",
            "type" => "text",
            "required" => 1,
            "default" => "",
            "inputClass" => "addr-form-email",
            "placeholder" => __("Enter email", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"),
        );

        $roles = Users::getNewUserRoles();
        $result[] = array(
            "id" => "user_role",
            "label" => __("Role", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"),
            "name" => "role",
            "position" => "user_section",
            "type" => "select",
            "required" => 1,
            "default" => "customer",
            "inputClass" => "user-role",
            "placeholder" => __("Role", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders"),
            "options" => array_map(function ($role, $roleData) {
                return array("value" => $role, "label" => $roleData["name"]);
            }, array_keys($roles), $roles),
        );


        return $result;
    }

    public static function getWooShippmentProviders()
    {
        global $wpdb;

        try {
            if (PluginsHelper::is_plugin_active('woo-advanced-shipment-tracking/woocommerce-advanced-shipment-tracking.php')) {
                $WC_Countries = new \WC_Countries();
                $countries = $WC_Countries->get_countries();


                // @codingStandardsIgnoreStart
                $providers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woo_shippment_provider;");
                // @codingStandardsIgnoreEnd

                if ($providers) {
                    foreach ($providers as &$value) {
                        $value->shipping_country_name = $value->shipping_country && isset($countries[$value->shipping_country]) ? $countries[$value->shipping_country] : "";
                    }
                }

                return $providers;
            } else {
                return array();
            }
        } catch (\Throwable $th) {
            return array();
        }
    }

    static function getCustomFieldsToExportImport()
    {
        $fields = self::getFields();

        $excludesList = array('usbs_categories', 'usbs_taxonomy', 'taxonomy_term', 'usbs_product_status', '_stock_status', '_sale_price', '_regular_price', '_sku', '_stock', 'usbs_variation_attributes');
        $fieldsToExport = array();

        if ($fields) {
            foreach ($fields as $field) {
                if ($field['status'] && $field['field_name'] && $field['position'] != 'product-middle-bottom' && !in_array($field['field_name'], $excludesList)) {
                    $fieldsToExport[$field['field_name']] = $field['field_label'];
                }
            }
        }

        return $fieldsToExport;
    }

    public static function getIndexedData($postId)
    {
        global $wpdb;

        if ($postId) {
            // @codingStandardsIgnoreStart
            $indexedOrderData = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}barcode_scanner_posts AS P WHERE post_id = %d", $postId));
            // @codingStandardsIgnoreEnd

            if ($indexedOrderData) {
                return $indexedOrderData;
            }
        }

        return null;
    }
}
