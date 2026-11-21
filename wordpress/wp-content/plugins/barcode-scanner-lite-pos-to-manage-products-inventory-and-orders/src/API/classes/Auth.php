<?php

namespace UkrSolution\BarcodeScanner\API\classes;

use UkrSolution\BarcodeScanner\features\settings\Settings;
use UkrSolution\BarcodeScanner\features\settings\SettingsHelper;

class Auth
{
    public function login()
    {
    }

    public function loginOtp()
    {
    }

    public function loginLink()
    {
    }

    public function check($token, $userToken = "", $userSession = "")
    {
        global $wpdb;
        $settings = new Settings();

        if ($userToken) {
            $user = null;
            // @codingStandardsIgnoreStart
            $userMeta = $wpdb->get_row($wpdb->prepare(
                "SELECT UM.meta_key, UM.meta_value, UM.user_id FROM {$wpdb->usermeta} AS UM WHERE UM.meta_key IN ('barcode_scanner_app_otp', 'barcode_scanner_web_otp') AND UM.meta_value = '%s';",
                $userToken
            ));
            // @codingStandardsIgnoreEnd

            if ($userMeta && $userMeta->user_id) {
                $user = get_user_by("ID", $userMeta->user_id);
            }

            return $userMeta && $userMeta->user_id && $user;
        } else if ($token && !in_array($token, array("web", "usersFind"))) {
            // @codingStandardsIgnoreStart
            $userMeta = $wpdb->get_row($wpdb->prepare(
                "SELECT UM.meta_key, UM.meta_value, UM.user_id FROM {$wpdb->usermeta} AS UM WHERE UM.meta_key IN ('barcode_scanner_app_otp', 'barcode_scanner_web_otp') AND UM.meta_value = '%s';",
                $token
            ));
            // @codingStandardsIgnoreEnd

            if ($userMeta && $userMeta->user_id) {
                return true;
            }

            return false;
        }

        return false;
    }

    public function getUserId($token, $userToken = "", $userSession = "")
    {
    }

    private function getSystemInfo($user)
    {
    }

    private function getWooVersion()
    {
    }
}
