<?php

namespace UkrSolution\BarcodeScanner\features\mobile;

use UkrSolution\BarcodeScanner\API\actions\UsersActions;
use UkrSolution\BarcodeScanner\API\classes\Auth;
use UkrSolution\BarcodeScanner\API\classes\PostsList;
use UkrSolution\BarcodeScanner\API\classes\ResultsHelper;
use UkrSolution\BarcodeScanner\API\classes\Users;
use UkrSolution\BarcodeScanner\features\settings\Settings;
use UkrSolution\BarcodeScanner\features\sounds\Sounds;
use UkrSolution\BarcodeScanner\API\classes\WPML;
use UkrSolution\BarcodeScanner\API\PluginsHelper;
use UkrSolution\BarcodeScanner\features\cart\Cart;
use UkrSolution\BarcodeScanner\features\history\History;
use UkrSolution\BarcodeScanner\features\interfaceData\InterfaceData;
use UkrSolution\BarcodeScanner\features\locations\Locations;
use UkrSolution\BarcodeScanner\features\settings\SettingsHelper;
use WP_REST_Request;

class MobileRouter
{
    private $coreInstance = null;
    private $tn = "script";

    public function init($coreInstance)
    {
        $this->coreInstance = $coreInstance;

            }

    public function getParamsFromPlainUrl()
    {
    }

    public function mobilePagesByUrl($urlData)
    {
    }

    private function pluginChecker()
    {
    }

    private function userAuth()
    {
    }

    private function loadWebviewData($urlData)
    {
    }
}
