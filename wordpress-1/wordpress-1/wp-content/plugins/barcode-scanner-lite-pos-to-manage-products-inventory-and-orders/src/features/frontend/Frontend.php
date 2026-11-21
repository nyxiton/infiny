<?php

namespace UkrSolution\BarcodeScanner\features\frontend;

use UkrSolution\BarcodeScanner\API\classes\Checker;
use UkrSolution\BarcodeScanner\features\settings\Settings;

class Frontend
{
    private $core = null;
    public $statusFrontend = null;
    private $statusShortcodes = null;
    private $statusMyAccount = null;
    private $userPermissionKey = "barcode-scanner-permission";

    function __construct($core = null)
    {
    }

    public function userMenuIntegration()
    {
    }

    public function woocommerce_account_menu_items($menu_items)
    {
        return $menu_items;
    }

    public function checkUserPermissions($userId = null)
    {
        try {
            if (!$userId) {
                $userId = \get_current_user_id();
            }

            $settings = new Settings();
            $rolePermissions = $settings->getUserRolePermissions($userId);

            if ($rolePermissions && isset($rolePermissions["frontend"]) && $rolePermissions["frontend"]) {
                return true;
            }

            $permission = \get_user_meta($userId, $this->userPermissionKey, true);

            if ($permission && (int)$permission) {
                return true;
            } else {
                return false;
            }
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function shordcodesIntegration()
    {
    }

    public function barcodeScannerPopupEmpty()
    {
        return "";
    }

    public function barcodeScannerPopup($args = array())
    {
        try {
            ob_start();

            $autoShow = isset($args["auto-show"]) ? $args["auto-show"] === "true" : false;
            $showLink = isset($args["show-link"]) ? trim($args["show-link"]) : __("Show Scanner", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders");

            if ($showLink === "false") {
                $showLink = "";
            }

            if ($this->core !== null) {
                $this->core->adminEnqueueScripts();

                if ($autoShow === true) {
                    wp_add_inline_script("barcode_scanner_loader", "window.BarcodeScannerAutoShow = true;");
                }
            }

            require_once USBS_PLUGIN_BASE_PATH . "src/features/frontend/link.php";

            return ob_get_clean();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
