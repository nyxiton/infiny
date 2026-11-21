<?php

namespace UkrSolution\BarcodeScanner\features\frontend;

use UkrSolution\BarcodeScanner\API\actions\UsersActions;
use UkrSolution\BarcodeScanner\API\classes\PostsList;
use UkrSolution\BarcodeScanner\API\classes\ResultsHelper;
use UkrSolution\BarcodeScanner\API\classes\SearchFilter;
use UkrSolution\BarcodeScanner\API\classes\WPML;
use UkrSolution\BarcodeScanner\API\PluginsHelper;
use UkrSolution\BarcodeScanner\features\cart\Cart;
use UkrSolution\BarcodeScanner\features\history\History;
use UkrSolution\BarcodeScanner\features\interfaceData\InterfaceData;
use UkrSolution\BarcodeScanner\features\locations\Locations;
use UkrSolution\BarcodeScanner\features\locations\LocationsData;
use UkrSolution\BarcodeScanner\features\settings\Settings;
use UkrSolution\BarcodeScanner\features\settings\SettingsHelper;
use UkrSolution\BarcodeScanner\features\sounds\Sounds;

class FrontendRouter
{
    private $userPermissionKey = "barcode-scanner-permission";
    private $core = null;

    public function init($core)
    {
        $this->core = $core;


                $urlData = $this->getParamsFromPlainUrl();


        return $urlData;
    }

    private function getParamsFromPlainUrl()
    {
        $result = array("route" => "", "params" => array());


        return $result;
    }

    public function frontendPagesByUrl($urlData)
    {
    }

    private function getLangs()
    {
        $languages = require USBS_PLUGIN_BASE_PATH . "src/Languages.php";


        return $languages;

    }

    public function checkUserPermissions($userId = null)
    {
    }
}
