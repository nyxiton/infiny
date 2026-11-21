<?php

namespace UkrSolution\BarcodeScanner;

use UkrSolution\BarcodeScanner\API\actions\ManagementActions;
use UkrSolution\BarcodeScanner\API\actions\UsersActions;
use UkrSolution\BarcodeScanner\API\AjaxRoutes;
use UkrSolution\BarcodeScanner\API\classes\Checker;
use UkrSolution\BarcodeScanner\API\classes\Integrations;
use UkrSolution\BarcodeScanner\API\classes\OrdersFilter;
use UkrSolution\BarcodeScanner\API\classes\OrdersHelper;
use UkrSolution\BarcodeScanner\API\classes\PostsList;
use UkrSolution\BarcodeScanner\API\classes\RequestHelper;
use UkrSolution\BarcodeScanner\API\classes\Results;
use UkrSolution\BarcodeScanner\API\classes\ResultsHelper;
use UkrSolution\BarcodeScanner\API\classes\Roles;
use UkrSolution\BarcodeScanner\API\classes\SearchFilter;
use UkrSolution\BarcodeScanner\API\classes\Users;
use UkrSolution\BarcodeScanner\API\classes\WPML;
use UkrSolution\BarcodeScanner\API\PluginsHelper;
use UkrSolution\BarcodeScanner\API\RequestHelper as RequestHelperCore;
use UkrSolution\BarcodeScanner\API\Routes;


use UkrSolution\BarcodeScanner\features\admin\Admin;
use UkrSolution\BarcodeScanner\features\cart\Cart;
use UkrSolution\BarcodeScanner\features\debug\Debug;
use UkrSolution\BarcodeScanner\features\export\Export;
use UkrSolution\BarcodeScanner\features\frontend\Frontend;
use UkrSolution\BarcodeScanner\features\frontend\FrontendRouter;
use UkrSolution\BarcodeScanner\features\history\History;
use UkrSolution\BarcodeScanner\features\import\Import;
use UkrSolution\BarcodeScanner\features\indexedData\IndexedData;
use UkrSolution\BarcodeScanner\features\interfaceData\InterfaceData;
use UkrSolution\BarcodeScanner\features\locations\Locations;
use UkrSolution\BarcodeScanner\features\locations\LocationsData;
use UkrSolution\BarcodeScanner\features\mobile\MobileRouter;
use UkrSolution\BarcodeScanner\features\orders\Orders;
use UkrSolution\BarcodeScanner\features\products\Products;
use UkrSolution\BarcodeScanner\features\settings\PermissionsHelper;
use UkrSolution\BarcodeScanner\features\settings\Settings;
use UkrSolution\BarcodeScanner\features\settings\SettingsHelper;
use UkrSolution\BarcodeScanner\features\sounds\Sounds;
use UkrSolution\BarcodeScanner\features\updater\Updater;
use WP_REST_Request;

class Core
{
    protected $updater;
    private $appLinkProtocol = "scan-app://";

        public function __construct() 
    {
        Debug::init();


        $integrations = new Integrations($this);
        Debug::addPoint("- integrations inicializiated");

        add_action('rest_api_init', function () {
            try {
                if(class_exists("Routes")) {
                    (new Routes())->registerRoutes();
                }
            } catch (\Throwable $th) {
            }
        });

        add_action('wp_enqueue_media', function () {
            Checker::setMediaLoader();
        });

        $priority = RequestHelper::getRequestPriority();

        add_action('init', function () {
            $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
            $nonce = !$nonce && isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : $nonce;

            if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
                return;
            }


                $getAction = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
            $postAction = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';

            if ( 'barcodescanneraction' == $getAction || 'barcodescanneraction' == $postAction ) {
                $this->ajaxRequest();
            }
        }, $priority);

        add_action('admin_menu', array($this, 'createMenu'), 9);
        add_action('admin_menu', array($this, 'adminEnqueueScripts'), 9);

        add_action('init', function () {
            $nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

            if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
                return;
            }


            $getAction = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
            $postAction = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';

            if ( 'barcodescannerconfigs' == $getAction || 'barcodescannerconfigs' == $postAction ) {
                $this->handleConfigs();
            }
        }, 999999);




        add_action('admin_footer', array($this, 'adminFooter'));

        add_action('shutdown', function() {
            foreach (PluginsHelper::$postsForUpdate as $postId) {
                Database::updatePost($postId, array(), null, null, "updated_post_meta_admin");
            }
        }, 999999);

        add_action('updated_post_meta', function($metaId, $postId, $metaKey, $metaValue) {
            if(\is_admin()) {
                if(!in_array($postId, PluginsHelper::$postsForUpdate)) {
                    PluginsHelper::$postsForUpdate[] = $postId;
                }
            } else if(true) {
                if(wp_rand(0, 100) === 100) {
                    Database::updatePost($postId, array(), null, null, "updated_post_meta");
                }
            }
        }, 1000, 4);

        add_action('woocommerce_save_product_variation', function($variationId){
            Database::updatePost($variationId, array(), null, null, "woocommerce_save_product_variation");
        }, 1000, 2);

        add_action('transition_post_status', function($newStatus, $oldStatus, $post){            
            if ($post->post_type !== "product") {
                return;
            }
            Database::updatePost($post->ID, array(), null, null, "transition_post_status");
        }, 9999, 3);

        add_action('woocommerce_order_status_changed', function ($order_id, $old_status, $new_status, $order) {
            Database::updatePost($order->get_id(), array(), null, null, "woocommerce_order_status_changed");
        }, 9999, 4);

        add_action('wp_insert_post', function($orderId) {
            if(in_array(get_post_type($orderId), array("shop_order"))) {
                Database::updatePost($orderId, array(), null, null, "wp_insert_post");
            }
        });


                add_filter('woocommerce_order_item_get_formatted_meta_data', function ($formatted_meta) {
            if ($formatted_meta) {
                foreach ($formatted_meta as $key => $value) {
                    if (in_array($value->key, array("usbs_check_product", "usbs_check_product_scanned", "usbs_product_scanned_by"))) {
                        unset($formatted_meta[$key]);
                    }
                }
            }
            return $formatted_meta;
        }, 10, 1 );

        add_action('before_delete_post', function ($post_id) {
            Database::removeIndexedRecord($post_id);
        });





        $frontend = new Frontend($this);
        $admin = new Admin($frontend);
        $products = new Products();
        $orders = new Orders();
        $locations = new Locations();
        $indexedData = new IndexedData();
        $roles = new Roles();
        $import = new Import();
        $export = new Export();


        add_action('init', array($this, "parseAuthRequest"));





        add_filter('user_has_cap', function ($allCaps, $caps, $args) {
            if (isset($caps[0]) ) {
                switch ($caps[0]) {
                case 'pay_for_order':
                    $user_id = $args[1];
                    $order_id = isset($args[2]) ? $args[2] : null;

                    $user = get_userdata($user_id);

                    if ($order_id && $user && in_array('administrator', (array)$user->roles ) ) { 
                        $allcaps['pay_for_order'] = true;
                    }
                    else if($order_id) {
                        $settings = new Settings();
                        $permissions = $settings->getUserRolePermissions();

                            if (is_array($permissions) && isset($permissions['orders']) && $permissions['orders'] == 1) {
                            $allCaps['pay_for_order'] = true;
                        }
                    }

                    break;
                }
            }

            $allCaps['pay_for_order'] = true;

            return $allCaps;
        }, 10, 3);

        add_action('admin_enqueue_scripts', function () {
            if ( Checker::getMediaLoader() ) {
                return;
            }

            global $pagenow;

            if ( 'post.php' === $pagenow ) {
                $screen = get_current_screen();

                if ( $screen && 'product' === $screen->post_type ) {
                    return;
                }
            }

            wp_enqueue_media();
        });


    }

    public function createMenu()
    {
        $icons = str_replace("src/", "", \plugin_dir_url(__FILE__)) . "assets/icons/";
        $icons = USBS_PLUGIN_BASE_URL . "assets/icons/";


        $suf = '';
        $mainRout = 'barcode-scanner';
        $icon = $icons . 'barcode-scanner-menu-logo.svg';

                add_menu_page(__('Barcode Scanner', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), __('Barcode Scanner', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), 'read', $mainRout, array($this, 'modalPage'), $icon);

        add_submenu_page($mainRout, __('Scan & Find item', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), __('Scan & Find item', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), 'read', $mainRout, array($this, 'modalPage'));


        add_submenu_page($mainRout, __('Settings', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), __('Settings', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), 'read', 'barcode-scanner-settings' . $suf, array($this, 'pageSettings'));
        add_submenu_page("", __('Settings', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), __('Settings', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), 'read', 'barcode-scanner-settings-update' . $suf, array($this, 'pageSettingsUpdate'));
        add_submenu_page("", __('Settings', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), __('Settings', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), 'read', 'barcode-scanner-settings-reset' . $suf, array($this, 'pageSettingsReset'));
        add_submenu_page("", __('Settings', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), __('Settings', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), 'read', 'barcode-scanner-download-log' . $suf, array($this, 'pageDownloadLog'));


        add_submenu_page($mainRout, __('Indexed data', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), __('Indexed data', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), 'read', 'barcode-scanner-indexed-data', array($this, 'pageIndexedData'));

        add_submenu_page($mainRout, __('Ask on the Forum', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), __('Ask on the Forum', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), 'manage_woocommerce', 'barcode-scanner-ask-on-the-forum', array($this, 'emptyPage'));        
        add_submenu_page($mainRout, __('Support', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), '<span class="barcode_scanner_support">' . __('Support', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders') . '</span>', 'read', 'barcode-scanner-support', array($this, 'emptyPage'));

        add_submenu_page($mainRout, __('FAQ', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), '<span class="barcode_scanner_faq">' . __('FAQ', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders') . '</span>', 'read', 'barcode-scanner-faq', array($this, 'emptyPage'));

        add_submenu_page("", __('Barcode Scanner', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), __('Barcode Scanner', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), 'read', 'bs-mobile-home', array($this, 'mobilePageHome'));

        add_submenu_page("", __('Barcode Scanner', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), __('Barcode Scanner', 'barcode-scanner-lite-pos-to-manage-products-inventory-and-orders'), 'read', 'usbs-redirect', array($this, 'redirectPage'));
    }

    public function adminEnqueueScripts($isReturn = false, $webview = false, $urlData = array())
    {
        global $wp_version;

        Debug::addPoint("ajax_action::Core->adminEnqueueScripts()");        

        $path = plugin_dir_url(__FILE__);
        $path = str_replace('src/', '', $path);

        if ($webview) {
            
  $appJsPath = plugin_dir_url(__FILE__)."../assets/js/bundle-free-1.13.6-1791555749272.js";

  $vendorJsPath = plugin_dir_url(__FILE__)."../assets/js/chunk-free-1.13.6-1791555749272.js";

  
        } else {
            wp_enqueue_script("barcode_scanner_loader", $path."assets/js/index-free-1.13.6-1791555749272.js", array("jquery"), 1791555749272, true);

    $appJsPath = $path."assets/js/bundle-free-1.13.6-1791555749272.js";

    $vendorJsPath = $path."assets/js/chunk-free-1.13.6-1791555749272.js";

        }

        wp_enqueue_style('barcode_scanner_main', USBS_PLUGIN_BASE_URL . '/assets/css/style.css', array(), '1.13.6');

        if(!$isReturn) {
            $settings = get_option("barcode-scanner-settings-options", array());

            $usbs = array(
                'appJsPath' => $appJsPath,
                'ajaxUrl' => SettingsHelper::getAjaxUrl(),
                'jsonUrl' => get_rest_url(),
                'nonce' => wp_create_nonce('wp_rest'),
                'wc_nonce' => wp_create_nonce('wc_store_api'),
                'wp_version' => $wp_version,
                'wc_version' => defined("WC_VERSION") ? WC_VERSION : 0,
                "phpVersion" => phpversion(),
                'settings' => array(
                ),
            );
            wp_localize_script("barcode_scanner_loader", "usbs", $usbs);
            return;
        }

                $userId = get_current_user_id();
        $request = null;


                $userId = $request ? Users::getUserId($request) : $userId;
        $userRole = Users::getUserRole($userId);
        PermissionsHelper::setUser($userId);

         if (!$userId){
            PermissionsHelper::roleRequired(array());
        }

                Debug::addPoint("- user data collected");

        $platform = '';


        $settings = new Settings();
        $sounds = new Sounds();
        $cart = new Cart();
        $interfaceData = new InterfaceData();
        $location = new Locations();
        $usersActions = new UsersActions();

        $wpml = null;

        if(WPML::status()) {
            $wpml = array("translations" => WPML::getTranslations());
        }

        $currency = "$";
        $currencyLabel = "USD";
        $priceThousandSeparator = "";
        $priceDecimalSeparator = ".";
        $priceDecimals = 2;

        if(function_exists('get_woocommerce_currency_symbol') && function_exists('get_woocommerce_currency')) {
            $currency = html_entity_decode(get_woocommerce_currency_symbol());
            $currencyLabel = get_woocommerce_currency();
        }

        if(function_exists('wc_get_price_decimal_separator')) {
            $priceDecimalSeparator = wc_get_price_decimal_separator();
        }

        if(function_exists('wc_get_price_thousand_separator')) {
            $priceThousandSeparator = wc_get_price_thousand_separator();
        }

        if(function_exists('wc_get_price_decimals')) {
            $priceDecimals = wc_get_price_decimals();
        }

                Debug::addPoint("- WC settings collected");

        $userSessions = $settings->getSettings("userSessions", false, $webview);
        if ($userSessions) {
            $userSessions = $userSessions->value;
        }

        $session = $settings->getSettings("session");
        $sessionStamp = $settings->getSettings("sessionStamp");
        $usbsInterface = $interfaceData::getFields(true, "", false, $userRole);

        $countries = array();
        if(PluginsHelper::is_plugin_active('woocommerce/woocommerce.php')) {
            try {
                $countries = WC()->countries->countries;
            } catch (\Throwable $th) {
            }
        }

        $productsList = PostsList::getList($userId, true);

        if ($webview) {
            $pluginSettings = $settings->getField('', '', '', false, false);
        } else {
            $pluginSettings = $settings->getField("", "", "", false, true);
        }

        $userDisplayName = "";

        if ($userId) {
            $user = get_user_by("ID", $userId);

            if ($user) {
                $userDisplayName = $user->display_name ? $user->display_name : $user->user_nicename;
            }
        }

        $currencyPosition = get_option('woocommerce_currency_pos');
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

        $usbs = array(
            'appJsPath' => $appJsPath,
            'vendorJsPath' => $vendorJsPath,
            'websiteUrl' => get_bloginfo("url"),
            'adminUrl' => get_admin_url(),
            'pluginUrl' => USBS_PLUGIN_BASE_URL,
            'frontendLink' => get_home_url() . "/barcode-scanner-front",
            'jsonUrl' => get_rest_url(),
            'pluginVersion' => '1.13.6',
            'isWoocommerceActive' => PluginsHelper::is_plugin_active('woocommerce/woocommerce.php'),
            'isStockLocations' => PluginsHelper::is_plugin_active('stock-locations-for-woocommerce/stock-locations-for-woocommerce.php'),
            'currencySymbol' => $currency,
            'currencyLabel' => $currencyLabel,
            'currencyPosition' => $currencyPosition,
            'priceDecimalSeparator' => $priceDecimalSeparator,
            'priceThousandSeparator' => $priceThousandSeparator,
            'priceDecimals' => $priceDecimals,
            'rest_root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'ajaxUrl' => SettingsHelper::getAjaxUrl(),
            'ajaxUrlUS' => SettingsHelper::getRequestUrl(),
            'urlSettings' => admin_url('/admin.php?page=barcode-scanner-settings'),
            'urlSettingsLocations' => admin_url('/admin.php?page=barcode-scanner-settings&tab=locations-data'),
            'wc_nonce' => wp_create_nonce('wc_store_api'),
            'uid' => $userId ? $userId : get_current_user_id(),
            'userId' => $userId,
            'userDisplayName' => $userDisplayName,
            'userSettings' => $usersActions->getUserMeta($userId),
            'settings' => $pluginSettings,
            'searchFilter' => SearchFilter::get(),
            'wp_version' => $wp_version,
            'wc_version' => defined("WC_VERSION") ? WC_VERSION : 0,
            "phpVersion" => phpversion(),
            'wpml' => $wpml,
            'plugins' => PluginsHelper::checkExternalPlugins(),
            'tabsPermissions' => $settings->getUserRolePermissions($userId),
            'session' => $session ? $session->value : "",
            'sessionStamp' => $sessionStamp ? $sessionStamp->value : "",
            "locations" => array(),
            "pp_locations" => array(),
            "prefix" => "",
            "mode" => 'b4zJfC5HCTN0hhVfQUmPxQ==',
            "userSessions" => $userSessions,
            "shippingMethods" => OrdersHelper::getWCshippingMethods(),
            "paymentMethods" => $cart->getPaymentMethods(),
            "wcPricesInclTax" => function_exists("wc_prices_include_tax") ? \wc_prices_include_tax() : "",
            "orderStatuses" => SettingsHelper::getOrderStatuses(),
            "orderAvailableStatuses" => apply_filters('scanner_orders_available_statuses', array()),
            "countries" => $countries,
            "searchHistory"=> array(),
            "productsListCount" => $productsList ? count($productsList) : 0,
            "platform" => $platform,
            'sounds' => $sounds->getList(),
            "ip" => $ip,
            'utoken' => Users::getUToken($userId, $platform),
            'upload_max_filesize' => SettingsHelper::getUploadMaxFilesize(),
            'taxBasedOn' => get_option('woocommerce_tax_based_on', 'shipping'),
            'user_roles' => Users::getNewUserRoles(),
            'user_role' => Users::getUserRole($userId),
            'usbs_orders_list_filter' => $userId ? get_user_meta($userId, "usbs_orders_list_filter", true) : array(),
            'search_results_sort_by' => $userId ? get_user_meta($userId, "search_results_sort_by", true) : "relevance",
        );

        $enableLocations = $settings->getSettings("enableLocations");
        if(($enableLocations && $enableLocations->value === "on") || !$enableLocations) {
            $usbs["locations"] = $location->get();
        }
        Debug::addPoint("- usbs collected");

        $customCss = $settings->getSettings("customCss");
        Debug::addPoint("- usbsCustomCss collected");


        $usbsHistory = History::getByUser($userId);
        Debug::addPoint("- usbsHistory collected");

        $usbsUserCF = InterfaceData::getUserFields();
        Debug::addPoint("- usbsUserCF collected");

        $usbsOrderCF = InterfaceData::getOrderFields();
        Debug::addPoint("- usbsOrderCF collected");

        $userFormCF = InterfaceData::getUserFormFields();
        Debug::addPoint("- userFormCF collected");

        $usbsWooShippmentProviders = InterfaceData::getWooShippmentProviders();
        Debug::addPoint("- usbsWooShippmentProviders collected");

        $usbsLangs = $this->getLangs();
        Debug::addPoint("- usbsLangs collected");

        $usbsLangsApp = $this->getLangsApp();
        Debug::addPoint("- usbsLangsApp collected");

        $usbsInterface = apply_filters("scanner_product_fields_filter", $usbsInterface);
        Debug::addPoint("- usbsInterface collected");

        $cartExtraData = ResultsHelper::getExtraData($userId ? $userId : get_current_user_id());
        Debug::addPoint("- cartExtraData collected");

        $field = $settings->getSettings("modifyPreProcessSearchString");
        $fnContent = $field === null ? "" : trim($field->value);
        $usbsModifyPreProcessSearchString = '';

        if ($fnContent) {
            $usbsModifyPreProcessSearchString = "window.modifyPreProcessSearchString = (bs_search_string) => {" . $fnContent . " ; \n return bs_search_string; };";
        }
        Debug::addPoint("- modifyPreProcessSearchString");

        $field = $settings->getSettings("afterSearchJavaScript");
        $fnContent = $field === null ? "" : trim($field->value);
        $usbsAfterSearchJavaScript = '';

        if ($fnContent) {
            $usbsAfterSearchJavaScript = "window.afterSearchJavaScript = () => {" . $fnContent . " ; };";
        }
        Debug::addPoint("- afterSearchJavaScript");

        $usbsOrdersListFilter = OrdersFilter::getFilter();

                return array(
            'usbs' => $usbs,
            'usbsCustomCss' => array("css" => $customCss ? $customCss->value : ""),
            'usbsHistory' => $usbsHistory,
            'usbsUserCF' => $usbsUserCF,
            'usbsOrderCF' => $usbsOrderCF,
            'userFormCF' => $userFormCF,
            'usbsWooShippmentProviders' => $usbsWooShippmentProviders,
            'usbsLangs' => $usbsLangs,
            'usbsLangsApp' => $usbsLangsApp,
            'usbsInterface' => $usbsInterface,
            'usbsCategories' => array(), 
            "cartExtraData" => $cartExtraData,
            "usbsModifyPreProcessSearchString" => $usbsModifyPreProcessSearchString,
            "usbsAfterSearchJavaScript" => $usbsAfterSearchJavaScript,
            "usbsOrdersListFilter" => $usbsOrdersListFilter,
        );
    }

    public function handleConfigs() {
        $response = $this->adminEnqueueScripts(true);


        $response['performance'] = Debug::getResult(true);

        PluginsHelper::responseHeaders();


        wp_send_json($response);


        wp_die();
    }

    private function getLangs() {
        if (file_exists(USBS_PLUGIN_BASE_PATH . "src/Languages.php")) {
            $languages = require USBS_PLUGIN_BASE_PATH . "src/Languages.php";
            return $languages;
        }

        return array();
    }

        private function getLangsApp() {
        if (file_exists(USBS_PLUGIN_BASE_PATH . "src/LanguagesApp.php")) {
            $languages = require USBS_PLUGIN_BASE_PATH . "src/LanguagesApp.php";
            return $languages;
        }

        return array();
    }

    public function pageSettings () {
        PermissionsHelper::setUser(get_current_user_id());
        $errorRole = PermissionsHelper::roleRequired(array('administrator'), true);
        $error = PermissionsHelper::onePermRequired(array('plugin_settings'), true);
        $userPermissions = PermissionsHelper::getUserPermissions();

        if(is_array($error) && isset($error['message']) && is_array($errorRole)) {
            $title = esc_html__("Barcode Scanner settings", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders");
            require_once USBS_PLUGIN_BASE_PATH . "src/features/settings/error.php";
            return;
        }

        $settings = new Settings();
        $wpml = WPML::status();
        $locations = new Locations();
        $interfaceData = new InterfaceData();
        $settingsHelper = new SettingsHelper();
        $managementActions = new ManagementActions();
        $cart = new Cart();

        $settings->restoreSettings();

        $customTabs = array();

                wp_enqueue_script('jquery-ui-sortable');

        $deps = array('jquery');

                wp_enqueue_script('barcode_scanner_settings', USBS_PLUGIN_BASE_URL . '/src/features/settings/assets/js/index-free-1.13.6-1791555749272.js', $deps, '1.3', true);
        wp_enqueue_style('barcode_scanner_settings', USBS_PLUGIN_BASE_URL . '/src/features/settings/assets/css/index.css', array(), '1.3');

        wp_enqueue_script('barcode_scanner_settings_chosen', USBS_PLUGIN_BASE_URL . '/src/features/settings/assets/js/chosen.jquery.min.js', $deps, '1.3', true);
        wp_enqueue_style('barcode_scanner_settings_chosen', USBS_PLUGIN_BASE_URL . '/src/features/settings/assets/css/chosen.min.css', array(), '1.3');

        wp_enqueue_script('barcode_scanner_settings_nestable', USBS_PLUGIN_BASE_URL . '/src/features/settings/assets/js/jquery.nestable.js', $deps, '1.3', true);
        wp_enqueue_style('barcode_scanner_settings_nestable', USBS_PLUGIN_BASE_URL . '/src/features/settings/assets/css/jquery.nestable.css', array(), '1.3');

        $codeSettings = wp_enqueue_code_editor(array('type' => 'text/html'));

        wp_enqueue_script('wp-theme-plugin-editor');


        require_once USBS_PLUGIN_BASE_PATH . "src/features/settings/index.php";
    }

    public function pageSettingsUpdate () {
        $nonce = isset($_POST["nonce"]) ? sanitize_text_field(wp_unslash($_POST["nonce"])) : "";

        PermissionsHelper::setUser(get_current_user_id());
        $errorRole = PermissionsHelper::roleRequired(array('administrator'), true);
        $error = PermissionsHelper::onePermRequired(array('plugin_settings'), true);

        if(is_array($error) && isset($error['message']) && is_array($errorRole)) {
            $title = esc_html__("Barcode Scanner settings", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders");
            require_once USBS_PLUGIN_BASE_PATH . "src/features/settings/error.php";
            return;
        }

        if ($nonce && wp_verify_nonce($nonce, USBS_PLUGIN_BASE_NAME . "-settings")) {
            $settings = new Settings();
            $settings->formSubmitted();
        }

        $tab = isset($_POST["tab"]) ? "&tab=" . sanitize_text_field(wp_unslash($_POST["tab"])) : "";
        $subTab = isset($_POST["sub"]) ? "&sub=" . sanitize_text_field(wp_unslash($_POST["sub"])) : "";
        $role = isset($_POST["role"]) ? "&role=" . sanitize_text_field(wp_unslash($_POST["role"])) : "";

        SettingsHelper::clearAllCaches();

        wp_safe_redirect(admin_url('/admin.php?page=barcode-scanner-settings' . $tab . $subTab . $role));
        exit;
    }

    public function pageSettingsReset () {
        $nonce = isset($_GET["nonce"]) ? sanitize_text_field(wp_unslash($_GET["nonce"])) : "";
        $tab = isset($_GET["tab"]) ? "&tab=" . sanitize_text_field(wp_unslash($_GET["tab"])) : "";

        PermissionsHelper::setUser(get_current_user_id());
        $errorRole = PermissionsHelper::roleRequired(array('administrator'), true);
        $error = PermissionsHelper::onePermRequired(array('plugin_settings'), true);

        if(is_array($error) && isset($error['message']) && is_array($errorRole)) {
            $title = esc_html__("Barcode Scanner settings", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders");
            require_once USBS_PLUGIN_BASE_PATH . "src/features/settings/error.php";
            return;
        }

        if($nonce && wp_verify_nonce($nonce, USBS_PLUGIN_BASE_NAME . "-settings")) {
            $settings = new Settings();
            $settings->resetOptionsSettings();
            Database::removeUsersData();        
            Database::removeAllTables();
            Database::setupTables(null);
            SettingsHelper::restoreReceiptTemplate();
        }

        wp_safe_redirect(admin_url('/admin.php?page=barcode-scanner-settings' . $tab));
        exit;
    }

    public function pageDownloadLog () {
        $nonce = isset($_GET["nonce"]) ? sanitize_text_field(wp_unslash($_GET["nonce"])) : "";
        $tab = isset($_GET["tab"]) ? "&tab=" . sanitize_text_field(wp_unslash($_GET["tab"])) : "";

        PermissionsHelper::setUser(get_current_user_id());
        $errorRole = PermissionsHelper::roleRequired(array('administrator'), true);
        $error = PermissionsHelper::onePermRequired(array('plugin_settings'), true);

        if(is_array($error) && isset($error['message']) && is_array($errorRole)) {
            $title = esc_html__("Barcode Scanner settings", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders");
            require_once USBS_PLUGIN_BASE_PATH . "src/features/settings/error.php";
            return;
        }

        if($nonce && wp_verify_nonce($nonce, USBS_PLUGIN_BASE_NAME . "-settings")) {
            RequestHelperCore::downloadLog();
        }

        wp_safe_redirect(admin_url('/admin.php?page=barcode-scanner-settings' . $tab));
        exit;
    }


    public function pageIndexedData () {
        PermissionsHelper::setUser(get_current_user_id());
        $error = PermissionsHelper::onePermRequired(array('plugin_settings'), true);

                if(is_array($error) && isset($error['message'])) {
            $title = esc_html__("Barcode Scanner Indexed Data", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders");
            require_once USBS_PLUGIN_BASE_PATH . "src/features/settings/error.php";
            return;
        }

                $indexedData = new IndexedData();

        if (isset($_GET["index"]) && sanitize_text_field(wp_unslash($_GET["index"]))) {
            $nonce = isset($_GET["usbs_index_nonce"]) ? sanitize_text_field(wp_unslash($_GET["usbs_index_nonce"])) : "";

            if ($nonce && wp_verify_nonce($nonce, "barcode_scanner_indexed_data")) {
                $pid = sanitize_text_field(wp_unslash($_GET["index"]));
                Database::updatePost($pid, array(), null, null, "pageIndexedData");
            }
        }

        if(isset($_GET["reCreateTable"]) && sanitize_text_field(wp_unslash($_GET["reCreateTable"]))) {
            $nonce = isset($_GET["usbs_index_nonce"]) ? sanitize_text_field(wp_unslash($_GET["usbs_index_nonce"])) : "";

            if ($nonce && wp_verify_nonce($nonce, "barcode_scanner_indexed_data")) {
                Database::clearTableColumns();
                Database::initDataTableColumns();
                Database::removeTableProducts();
                Database::setupTableProducts(true, true);      
                update_option("usbs_reCreateTable_msg", true);      
                wp_safe_redirect(admin_url('/admin.php?page=barcode-scanner-indexed-data'));
                exit;
            }
        }

                if(isset($_GET["triggers"])) {
            $nonce = isset($_GET["usbs_index_nonce"]) ? sanitize_text_field(wp_unslash($_GET["usbs_index_nonce"])) : "";

            if ($nonce && wp_verify_nonce($nonce, "barcode_scanner_indexed_data")) {
                $usbs_itc = isset($_GET["index_triggers_counting"]) ? sanitize_text_field(wp_unslash($_GET["index_triggers_counting"])) : "";
                update_option("usbs_index_triggers_counting", $usbs_itc);
                update_option("usbs_iic_updated_post_meta_admin", 0);
                update_option("usbs_iic_updated_post_meta", 0);
                update_option("usbs_iic_woocommerce_save_product_variation", 0);
                update_option("usbs_iic_transition_post_status", 0);
                update_option("usbs_iic_wp_insert_post", 0);
                update_option("usbs_iic_pageIndexedData", 0);
                update_option("usbs_iic_updatePostsTable", 0);

                wp_safe_redirect(admin_url('/admin.php?page=barcode-scanner-indexed-data'));
            }
        }

        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('barcode_scanner_logs', USBS_PLUGIN_BASE_URL . '/src/features/indexedData/assets/js/index-free-1.13.6-1791555749272.js', array('jquery'), '1.3', true);
        wp_enqueue_style('barcode_scanner_logs', USBS_PLUGIN_BASE_URL . '/src/features/indexedData/assets/css/index.css', array(), '1.3');
        wp_enqueue_style('jquery-ui'); 

               require_once USBS_PLUGIN_BASE_PATH . "src/features/indexedData/index.php";
    }

    public function mobilePageHome () {
        echo "<hr/>[mobilePageHome]<hr/>";
    }

    public function redirectPage () {
        $referer = \wp_get_referer();

        if($referer) {
            \wp_safe_redirect($referer);
        } else {
            \wp_safe_redirect(\get_admin_url());
        }
    }

    public function emptyPage () {}

    public function modalPage () {
        echo '<a href="admin.php?page=barcode-scanner" class="usbs-auto-start-modal"></a>';
    }


    public function ajaxRequest() {        
        new AjaxRoutes($this);
    }

    public function parseAuthRequest()
    {
    }


    public function adminFooter()
    {
        ?>
        <script>
        jQuery(function($){
            const $askOnTheForum = $('a[href="admin.php?page=barcode-scanner-ask-on-the-forum"]');
            $askOnTheForum.on('click', function(e){
                e.preventDefault();

                $('#usbs-ask-forum-modal').remove();
                $('style[data-usbs-ask-forum]').remove();

                var forumUrl = 'https://wordpress.org/support/plugin/barcode-scanner-lite-pos-to-manage-products-inventory-and-orders/#new-topic-0';
                var css = ''
                    + '#usbs-ask-forum-modal{position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:100000;font-size:14px;background:rgba(0,0,0,.3);}'
                    + '#usbs-ask-forum-modal .usbs-afm-body{box-sizing:border-box;background:#fff;padding:25px;position:relative;width:470px;max-width:calc(100vw - 40px);top:50%;left:50%;transform:translate(-50%,-50%);border-radius:8px;border:1px solid #f3f3f3;}'
                    + '#usbs-ask-forum-modal .usbs-afm-text{color:#333;font-size:14px;line-height:1.5;margin-bottom:20px;}'
                    + '#usbs-ask-forum-modal .usbs-afm-text p{margin:0 0 12px;}'
                    + '#usbs-ask-forum-modal .usbs-afm-actions{text-align:right;}'
                    + '#usbs-ask-forum-modal .usbs-afm-continue{background:#2271b1;border:1px solid #2271b1;color:#fff;padding:8px 16px;border-radius:4px;cursor:pointer;font-size:13px;}'
                    + '#usbs-ask-forum-modal .usbs-afm-continue:hover{background:#135e96;}'
                    + '#usbs-ask-forum-modal .usbs-afm-close{position:absolute;top:0;right:0;width:28px;cursor:pointer;padding:6px;box-sizing:border-box;opacity:.6;line-height:1;font-size:18px;color:#333;background:none;border:0;}';

                var $modal = $(
                    '<div id="usbs-ask-forum-modal">'
                        + '<div class="usbs-afm-body">'
                            + '<button type="button" class="usbs-afm-close" aria-label="Close">&times;</button>'
                            + '<div class="usbs-afm-text">'
                                + '<p>You will be redirected to wordpress.org forum where you can ask any questions.</p>'
                                + '<p>We will do our best to answer witin 24 hours.</p>'
                                + '<p><b>Important:</b> If you use "Pro" version, please specify your web-site address in description, so we can identify you (and for your SEO it is also good).</p>'
                            + '</div>'
                            + '<div class="usbs-afm-actions">'
                                + '<button type="button" class="usbs-afm-continue">CONTINUE</button>'
                            + '</div>'
                        + '</div>'
                    + '</div>'
                );

                $('head').append($('<style data-usbs-ask-forum></style>').text(css));
                $('body').append($modal);

                $modal.on('click', function(ev){
                    if (ev.target === this) $modal.remove();
                });
                $modal.find('.usbs-afm-close').on('click', function(){
                    $modal.remove();
                });
                $modal.find('.usbs-afm-continue').on('click', function(){
                    window.open(forumUrl, '_blank');
                    $modal.remove();
                });

                return false;
            });
        });
        </script>
        <?php
    }
}
