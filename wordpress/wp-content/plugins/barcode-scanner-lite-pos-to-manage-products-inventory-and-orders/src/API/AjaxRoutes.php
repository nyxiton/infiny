<?php

namespace UkrSolution\BarcodeScanner\API;

use UkrSolution\BarcodeScanner\API\actions\CartScannerActions;
use UkrSolution\BarcodeScanner\API\actions\DbActions;
use UkrSolution\BarcodeScanner\API\actions\ManagementActions;
use UkrSolution\BarcodeScanner\API\actions\OrdersActions;
use UkrSolution\BarcodeScanner\API\actions\PostActions;
use UkrSolution\BarcodeScanner\API\actions\UsersActions;
use UkrSolution\BarcodeScanner\API\classes\ACFRepeater;
use UkrSolution\BarcodeScanner\API\classes\BatchNumbers;
use UkrSolution\BarcodeScanner\API\classes\BatchNumbersWebis;
use UkrSolution\BarcodeScanner\API\classes\OrdersHelper;
use UkrSolution\BarcodeScanner\API\classes\PostsList;
use UkrSolution\BarcodeScanner\API\classes\Users;
use UkrSolution\BarcodeScanner\Database;
use UkrSolution\BarcodeScanner\features\Debug\Debug;
use UkrSolution\BarcodeScanner\features\history\History;
use UkrSolution\BarcodeScanner\features\mobile\MobileRouter;
use UkrSolution\BarcodeScanner\features\settings\PermissionsHelper;
use UkrSolution\BarcodeScanner\features\settings\Settings;
use UkrSolution\BarcodeScanner\features\settings\SettingsHelper;
use WP_REST_Request;


class AjaxRoutes
{
    private $coreInstance = null;

    public function __construct($coreInstance = null)
    {
        Debug::addPoint("AjaxRoutes->__construct", true);

        $this->coreInstance = $coreInstance;

        $input = json_decode(file_get_contents("php://input"), true);
        $route = $input && isset($input["rout"]) ? sanitize_text_field(wp_unslash($input["rout"])) : "";

        if (!$route) {
            check_ajax_referer('wp_rest', 'nonce');
            $route = isset($_POST['rout']) ? sanitize_text_field(wp_unslash($_POST['rout'])) : '';
        }

        if ($route) {
            add_filter('scanner_filter_cart_item_price', function ($productId, $price, $customFilter) {
                return $price;
            }, 1, 3);

            RequestHelper::initMemoryLimit();


            $settings = new Settings($this->coreInstance);
            $postActions = new PostActions();
            $managementActions = new ManagementActions();
            $ordersActions = new OrdersActions();
            $usersActions = new UsersActions();
            $dbActions = new DbActions();
            $cartActions = new CartScannerActions();
            $request = new WP_REST_Request("", "");

            $debugInfo = $settings->getSettings("debugInfo");
            $debugInfoStatus = $debugInfo ? $debugInfo->value : "";

            $token = isset($_GET["token"]) ? sanitize_text_field(wp_unslash($_GET["token"])) : "";
            $platform = isset($_GET["platform"]) ? sanitize_text_field(wp_unslash($_GET["platform"])) : "";

            $routes = new Routes();
            $request->set_param("token", $token);

            $tokenUserId = $routes->getUserId($request);
            $request->set_param("platform", $platform);
            $checker = $routes->permissionCallback($request);
            $request->set_param("token_user_id", $tokenUserId);

            if ($tokenUserId) {
                $userLocale = get_user_meta($tokenUserId, 'locale', true);
                if ($userLocale) {
                    switch_to_locale($userLocale);
                }

                $userRole = $tokenUserId ? Users::getUserRole($tokenUserId) : '';
                Users::setUserId($tokenUserId);
                Users::setUserRole($userRole);

                if ($platform == "android" || $platform == "ios") {


                    Users::updateAppUsesTime($tokenUserId);
                }
            }

            if (!PluginsHelper::is_plugin_active('woocommerce/woocommerce.php')) {
                $MobileRouter = new MobileRouter();
                echo wp_json_encode(array(
                    "errors" => array("WooCommerce is not activated"),
                    "cartErrors" => array(
                        array("notice" => "WooCommerce is not activated", "htmlMessageClass" => "err_woocommerce_is_not_activated")
                    ),
                ));
                wp_die();
            }

            if (
                !$checker && !in_array($route, array(
                    "recalculate",
                    "backgroundIndexing",
                    "checkOtherPrices",
                    "checkFieldName",
                    "indexingClearTable",
                    "getHistory",
                    "getItemsList"
                ))
            ) {
                $MobileRouter = new MobileRouter();

                $data = array("redirect" => 0, "data" => array("rout" => "invalid route"), "f" => 1);

                if ($platform == "android" || $platform == "ios") {
                    $urlData = $MobileRouter->getParamsFromPlainUrl();
                    $jsData = $this->coreInstance->adminEnqueueScripts(true, true, $urlData);
                    $usbs = $jsData && isset($jsData['usbs']) ? $jsData['usbs'] : array();
                    $data['usbs'] = $usbs;
                }

                echo wp_json_encode($data);
                exit;
            }

            PermissionsHelper::init($request);

            RequestHelper::log($request, $settings);

            $keysString = array(
                "id",
                "orderId",
                "noteId",
                "userId",
                "itemId",
                "customField",
                "recordId",
                "query",
                "withVariation",
                "productId",
                "quantity",
                "price",
                "title",
                "postId",
                "attachmentId",
                "field",
                "reqbs",
                "postAutoAction",
                "postAutoField",
                "postAutoActionQtyStep",
                "status",
                "autoFill",
                "byId",
                "setQty",
                "orderCustomPrice",
                "orderCustomShipping",
                "orderCustomShippingTax",
                "orderCustomSubPrice",
                "orderCustomTax",
                "orderCustomCashGot",
                "orderStatus",
                "shippingMethod",
                "paymentMethod",
                "key",
                "session",
                "sessionStamp",
                "orderUserId",
                "productQty",
                "tab",
                "param",
                "str",
                "orderAutoAction",
                "orderAutoStatus",
                "autoEnableIndexation",
                "slug",
                "country",
                "customAction",
                "userAction",
                "bsInstanceFrontendStatus",
                "confirmationLeftFulfillment",
                "isCheck",
                "isAddToList",
                "modifyAction",
                "isNew",
                "fulfillmentOrderId",
                "confirmed",
                "isUpdateShipping",
                "coupon",
                "customerId",
                "isPay",
                "fieldName",
                "parentId",
                "isMainImage",
                "withoutStatuses",
                "attributeName",
                "attributeValue",
                "loadCustomerData",
                "taxonomy",
                "ignoreIncrease",
                "note",
                "resultSortBy",
                "checkFulfillment",
                "request_priority",
                "content",
                "type",
                "integration",
            );
            $keysArray = array(
                "filter",
                "customFilter",
                "filterExcludes",
                "products",
                "fields",
                "searchAttributes",
                "progress",
                "userData",
                "currentItems",
                "itemsCustomPrices",
                "cartItem",
                "extraData",
                "orderCustomTaxes",
                "resetCustomPrices",
                "inputs",
                "data",
                "categories",
                "locations",
                "codes",
                "lines",
                "autoAction",
                "options",
                "items",
                "filterResult",
                "address",
                "batches",
                "batchesWebis",
                "shipmentTrackingItems",
                "currentIds",
                "postTypes",
                "value",
                "globalAttributes",
                "customAttributes",
                "globalOptions",
                "customOptions",
                "ids",
                "usersIds",
                "checkForDeletedIds",
                "prices",
                "values",
                "source",
                "metaFields",
                "payload"
            );
            $response = array();

            foreach ($keysString as $key) {
                $value = isset($input[$key]) ? $input[$key] : "";
                $value = wp_unslash($value);
                $value = sanitize_post_field($key, $value, 0, 'db');
                $request->set_param($key, $value);
            }

            foreach ($keysArray as $arrayKey) {
                $arrayKey = sanitize_text_field($arrayKey);

                if (isset($input[$arrayKey])) {
                    $value = map_deep($input[$arrayKey], 'sanitize_text_field');
                    $request->set_param($arrayKey, $value);
                }
            }


            $_POST["bsInstanceFrontendStatus"] = $request->get_param("bsInstanceFrontendStatus");

            SettingsHelper::updateSettings($request);

            Debug::addPoint("AjaxRoutes->before method call", $debugInfoStatus === "on");

            switch ($route) {
                case 'getPost':
                    PermissionsHelper::onePermRequired(array('inventory', 'orders'));
                    $response = $postActions->postSearch($request);
                    break;
                case 'getProduct':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod', 'orders', 'cart'));
                    $response = $managementActions->productSearch($request);
                    break;
                case 'getProducts':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod', 'orders', 'cart'));
                    $response = $managementActions->getProducts($request);
                    break;
                case 'getOrder':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->orderSearch($request);
                    break;
                case 'productEnableManageStock':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->productEnableManageStock($request);
                    break;
                case 'updateProductQuantity':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->productUpdateQuantity($request);
                    break;
                case 'updateProductQuantityPlus':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->productUpdateQuantityPlus($request);
                    break;
                case 'updateProductQuantityMinus':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->productUpdateQuantityMinus($request);
                    break;
                case 'updateProductRegularPrice':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->productUpdateRegularPrice($request);
                    break;
                case 'updateProductSalePrice':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->productUpdateSalePrice($request);
                    break;
                case 'updateProductCustomPrice':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->updateProductCustomPrice($request);
                    break;
                case 'updateProductMeta':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->productUpdateMeta($request);
                    break;
                case 'updatePostStatus':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->productUpdateStatus($request);
                    break;
                case 'updateTitle':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->productUpdateTitle($request);
                    break;
                case 'setImage':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->productSetImage($request);
                    break;
                case 'createNew':
                    PermissionsHelper::onePermRequired(array('newprod'));
                    $response = $managementActions->productCreateNew($request);
                    break;
                case 'reloadNewProduct':
                    PermissionsHelper::onePermRequired(array('newprod'));
                    $response = $managementActions->reloadNewProduct($request);
                    break;
                case 'update':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->productUpdateFields($request);
                    break;
                case 'uploadPostImage':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->uploadPostImage($request);
                    break;
                case 'removePostImages':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->removePostImages($request);
                    break;
                case 'removePostMainImage':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->removePostMainImage($request);
                    break;
                case 'sortProductGallery':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->sortProductGallery($request);
                    break;
                case 'changeStatus':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->orderChangeStatus($request);
                    break;
                case 'changeCustomer':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->orderChangeCustomer($request);
                    break;
                case 'changeOrderAddress':
                    PermissionsHelper::onePermRequired(array('orders'));
                    PermissionsHelper::onePermRequired(array('order_edit_address'));
                    $response = $managementActions->changeOrderAddress($request);
                    break;
                case 'orderItemUpdate':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->orderItemUpdate($request);
                    break;
                case 'orderChangeShippingMethod':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->orderChangeShippingMethod($request);
                    break;
                case 'orderChangePaymentMethod':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->orderChangePaymentMethod($request);
                    break;
                case 'orderChangeOrderNote':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->orderChangeOrderNote($request);
                    break;
                case 'orderChangeOrderCoupon':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->orderChangeOrderCoupon($request);
                    break;
                case 'orderReCalculate':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->orderReCalculate($request);
                    break;
                case 'updateOrderMeta':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->updateOrderMeta($request);
                    break;
                case 'updateOrderMetaFields':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->updateOrderMetaFields($request);
                    break;
                case 'orderUpdateItemsMeta':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->orderUpdateItemsMeta($request);
                    break;
                case 'orderUpdateItemMeta':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = rest_ensure_response($managementActions->orderUpdateItemMeta($request));
                    break;
                case 'updateFoundCounter':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod', 'orders', 'cart'));
                    $response = $managementActions->updateFoundCounter($request);
                    break;
                case 'uploadPick':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->uploadPick($request);
                    break;
                case 'getProductCategories':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->getProductCategories($request);
                    break;
                case 'getProductTaxonomy':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->getProductTaxonomy($request);
                    break;
                case 'updateCategories':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->updateCategories($request);
                    break;
                case 'updateTaxonomy':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->updateTaxonomy($request);
                    break;
                case 'updateAttributes':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->updateAttributes($request);
                    break;
                case 'getGlobalAttributes':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->getGlobalAttributes($request);
                    break;
                case 'createGlobalAttributeValue':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = $managementActions->createGlobalAttributeValue($request);
                    break;
                case 'importCodes':
                    PermissionsHelper::onePermRequired(array('inventory'));
                    $response = $managementActions->importCodes($request);
                    break;
                case 'getItemsList':
                    PermissionsHelper::onePermRequired(array('inventory'));
                    $response = rest_ensure_response(array("productsList" => PostsList::getList(Users::getUserId($request))));
                    break;
                case 'updateItemsFromList':
                    PermissionsHelper::onePermRequired(array('inventory'));
                    $response = $managementActions->updateItemsFromList($request);
                    break;
                case 'removeItemsListRecord':
                    PermissionsHelper::onePermRequired(array('inventory'));
                    $response = $managementActions->removeItemsListRecord($request);
                    break;
                case 'clearItemsList':
                    PermissionsHelper::onePermRequired(array('inventory'));
                    $response = $managementActions->clearItemsList($request);
                    break;
                case 'getOrdersList':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->getOrdersList($request);
                    break;
                case 'getNextOrder':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->getNextOrder($request);
                    break;
                case 'getPrevOrder':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->getPrevOrder($request);
                    break;
                case 'updateOrderFulfilledObject':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->updateOrderFulfilledObject($request);
                    break;
                case 'removeFulfillmentObject':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->removeFulfillmentObject($request);
                    break;
                case 'ff2Search':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $ordersActions->ff2Search($request);
                    break;
                case 'ff2PickItem':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $ordersActions->ff2PickItem($request);
                    break;
                case 'ff2PickCustomField':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $ordersActions->ff2PickCustomField($request);
                    break;
                case 'ff2RepickItem':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $ordersActions->ff2RepickItem($request);
                    break;
                case 'update_wc_shipment_tracking_item':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->update_wc_shipment_tracking_item($request);
                    break;
                case 'delete_wc_shipment_tracking_item':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->delete_wc_shipment_tracking_item($request);
                    break;
                case 'create_wc_shipment_tracking_item':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->create_wc_shipment_tracking_item($request);
                    break;
                case 'getItemsCustomFields':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $managementActions->getItemsCustomFields($request);
                    break;
                case 'removeOrderNote':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = OrdersHelper::removeOrderNote($request);
                    break;
                case 'addOrderNote':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = OrdersHelper::addOrderNote($request);
                    break;
                case 'orderAddProduct':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = OrdersHelper::orderAddProduct($request);
                    break;
                case 'removeProduct':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = OrdersHelper::removeProduct($request);
                    break;
                case 'checkEnableFFforOrder':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = OrdersHelper::checkEnableFFforOrder($request);
                    break;
                case 'disableFFforOrder':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = OrdersHelper::disableFFforOrder($request);
                    break;
                case 'setEditingFlagToOrder':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = OrdersHelper::setEditingFlagToOrder($request);
                    break;


                case 'usersFind':
                case 'usersfind':
                    PermissionsHelper::onePermRequired(array('linkcustomer', 'plugin_settings'));
                    $response = $usersActions->find($request);
                    break;


                case 'getRoleData':
                    PermissionsHelper::onePermRequired(array('linkcustomer', 'plugin_settings'));
                    $response = $usersActions->getRoleData($request);
                    break;
                case 'userCreate':
                    PermissionsHelper::onePermRequired(array('orders', 'cart'));
                    PermissionsHelper::onePermRequired(array('linkcustomer'));
                    $response = $usersActions->createUser($request);
                    break;
                case 'getStates':
                    PermissionsHelper::onePermRequired(array('orders', 'cart'));
                    $response = $usersActions->getStates($request);
                    break;
                case 'addItem':
                    PermissionsHelper::onePermRequired(array('cart'));
                    $response = $cartActions->addItem($request);
                    break;
                case 'removeItem':
                    PermissionsHelper::onePermRequired(array('cart'));
                    $response = $cartActions->removeItem($request);
                    break;
                case 'updateQuantity':
                    PermissionsHelper::onePermRequired(array('cart'));
                    $response = $cartActions->updateQuantity($request);
                    break;
                case 'updateCartItemAttributes':
                    PermissionsHelper::onePermRequired(array('cart'));
                    $response = $cartActions->updateAttributes($request);
                    break;
                case 'clear':
                    PermissionsHelper::onePermRequired(array('cart'));
                    $response = $cartActions->cartClear($request);
                    break;
                case 'orderCreate':
                    PermissionsHelper::onePermRequired(array('cart'));
                    $response = $cartActions->orderCreate($request);
                    break;
                case 'getStatuses':
                    PermissionsHelper::onePermRequired(array('orders', 'cart'));
                    $response = $cartActions->getStatuses($request);
                    break;
                case 'recalculate':
                    PermissionsHelper::onePermRequired(array('cart'));
                    $response = $cartActions->cartRecalculate($request);
                    break;
                case 'resetCustomPrices':
                    PermissionsHelper::onePermRequired(array('cart'));
                    $response = $cartActions->resetCustomPrices($request);
                    break;
                case 'createColumn':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod', 'orders', 'cart'));
                    $response = $dbActions->createColumn($request);
                    break;
                case 'saveSession':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod', 'orders', 'cart'));
                    $response = $dbActions->saveSession($request);
                    break;
                case 'saveSettings':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod', 'orders', 'cart'));
                    $response = $dbActions->saveSettings($request);
                    break;
                case 'backgroundIndexing':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod', 'orders', 'cart'));
                    Database::pluginUpdateHistory($settings);
                    $response = $dbActions->backgroundIndexing($request, $settings);
                    break;
                case 'indexingClearTable':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod', 'orders', 'cart'));
                    $response = $dbActions->indexingClearTable($request);
                    break;
                case 'checkOtherPrices':
                    PermissionsHelper::onePermRequired(array('plugin_settings'));
                    $response = $postActions->checkOtherPrices($request);
                    break;
                case 'checkFieldName':
                    PermissionsHelper::onePermRequired(array('plugin_settings'));
                    $response = $postActions->checkFieldName($request);
                    break;
                case 'updateSettings':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod', 'orders', 'cart'));
                    $response = $settings->updateSettingsArray($request);
                    break;
                case 'loadSettings':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod', 'orders', 'cart'));
                    $response = $settings->loadSettingsArray($request);
                    break;


                case 'setUserMeta':
                    PermissionsHelper::onePermRequired(array('orders'));
                    $response = $usersActions->setUserMeta($request);
                    break;
                case 'getHistory':
                    PermissionsHelper::onePermRequired(array('inventory', 'orders', 'cart'));
                    $response = rest_ensure_response(array("history" => History::getByUser(Users::getUserId($request))));
                    break;

                case 'usbs_find_order_save_id':
                    PermissionsHelper::onePermRequired(array('plugin_settings'));
                    $response = $settings->saveTemplateOrderId($request);
                    break;
                case 'usbs_find_order':
                    PermissionsHelper::onePermRequired(array('plugin_settings'));
                    $response = $settings->findOrder($request);
                    break;

                case 'batchNumbersRemoveBatch':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = BatchNumbers::removeBatch($request);
                    break;
                case 'batchNumbersAddNewBatch':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = BatchNumbers::addNewBatch($request);
                    break;
                case 'batchNumbersSaveBatchField':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = BatchNumbers::saveBatchField($request);
                    break;

                case 'batchNumbersWebisRemoveBatch':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = BatchNumbersWebis::removeBatch($request);
                    break;
                case 'batchNumbersWebisAddNewBatch':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = BatchNumbersWebis::addNewBatch($request);
                    break;
                case 'batchNumbersWebisSaveBatchField':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));
                    $response = BatchNumbersWebis::saveBatchField($request);
                    break;
                case 'integrationsUpdate':
                    PermissionsHelper::onePermRequired(array('inventory', 'newprod'));

                    $allowedIntegrations = array("ACFRepeater");

                    if (in_array($request->get_param("integration"), $allowedIntegrations)) {
                        $integration = "UkrSolution\BarcodeScanner\API\classes\\" . $request->get_param("integration");

                        if (class_exists($integration) && method_exists($integration, "update")) {
                            $response = $integration::update($request);
                        }
                    }

                    break;
                default: {
                    echo wp_json_encode(array(
                        "errors" => array("Invalid input"),
                        "token" => $token
                    ));
                    exit;
                }
            }

            Debug::addPoint("AjaxRoutes->after method call", $debugInfoStatus === "on");

            $filter = isset($input["filter"]) && is_array($input["filter"]) ? $input["filter"] : array();

            if ($filter && in_array($route, array("checkCustomFields", "createColumn"))) {
                $filteredData = array('products' => array(), 'orders' => array(), 'wpml' => array());

                foreach ($filteredData as $type => $items) {
                    if (isset($filter[$type]) && is_array($filter[$type])) {
                        foreach ($filter[$type] as $key => $value) {
                            $encodedKey = sanitize_text_field(wp_unslash($key));
                            $encodedValue = sanitize_text_field(wp_unslash($value));
                            $filteredData[$type][$encodedKey] = $encodedValue;
                        }
                    }
                }

                $settings = new Settings();
                $settings->updateSettings('search_filter', json_encode($filteredData));
            }

            PluginsHelper::responseHeaders();

            if ($response && $response->data) {
                $updatedTimestamp = $settings->getSettings("updated_timestamp");
                $response->data["settings_updated_timestamp"] = $updatedTimestamp ? $updatedTimestamp->value : "";
                $response->data["microtime"] = microtime(true);

                if ($debugInfoStatus === "on") {
                    $response->data["debug"] = Debug::getResult(true, true);
                }

                echo wp_json_encode($response->data);
            } else {
                echo wp_json_encode("error");
            }
            exit;
        }
    }

    private function getParam($data, $key, $default = null)
    {
        if (isset($data[$key])) {
            return $data[$key];
        } else {
            return $default;
        }
    }
}
