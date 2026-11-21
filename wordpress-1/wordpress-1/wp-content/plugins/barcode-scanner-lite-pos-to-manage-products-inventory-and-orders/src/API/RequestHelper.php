<?php

namespace UkrSolution\BarcodeScanner\API;

use UkrSolution\BarcodeScanner\API\classes\Users;
use WP_REST_Request;

class RequestHelper
{
    public static $scanner_search_query = 'barcode_scanner_search_query';

    public static function getQuery(WP_REST_Request $request, $type = "")
    {
        $query = $request->get_param("query");



        return $query ? trim($query) : "";
    }

    public static function initMemoryLimit()
    {
        $memoryLimit = ini_get('memory_limit');

        $memoryLimitBytes = self::convertToBytes($memoryLimit);

        if ($memoryLimitBytes < 512 * 1024 * 1024) {
        }

        return $memoryLimitBytes;
    }

    private static function convertToBytes($value)
    {
        if ($value === '-1') {
            return PHP_INT_MAX;
        }

        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $numeric = (int) $value;

        switch ($last) {
            case 'g':
                $numeric *= 1024;
            case 'm':
                $numeric *= 1024;
            case 'k':
                $numeric *= 1024;
        }

        return $numeric;
    }

    public static function logClear()
    {
        if (!current_user_can('administrator')) {
            return;
        }

        try {
            $logFileName = "usbs_requests_log";
            $wp_upload_dir = wp_upload_dir();
            $upload_dir = $wp_upload_dir['basedir'] . '/barcode-scanner/';
            $csvFilePath = $upload_dir . 'logs/' . $logFileName . '.csv';

            if (file_exists($csvFilePath)) {
                wp_delete_file($csvFilePath);
            }
        } catch (\Throwable $th) {
        }
    }


    public static function checkFile()
    {
        if (!current_user_can('administrator')) {
            return;
        }

        try {
            $logFileName = "usbs_requests_log";
            $wp_upload_dir = wp_upload_dir();
            $upload_dir = $wp_upload_dir['basedir'] . '/barcode-scanner/';
            $csvFilePath = $upload_dir . 'logs/' . $logFileName . '.csv';

            return file_exists($csvFilePath);
        } catch (\Throwable $th) {
        }
    }

    public static function downloadLog()
    {
        if (!current_user_can('administrator')) {
            return;
        }

        try {
            $logFileName = "usbs_requests_log";
            $wp_upload_dir = wp_upload_dir();
            $upload_dir = $wp_upload_dir['basedir'] . '/barcode-scanner/';
            $csvFilePath = $upload_dir . 'logs/' . $logFileName . '.csv';

            if (!file_exists($csvFilePath)) {
                return;
            }

            $dt = new \DateTime("now");
            $downloadFileName = "Barcode_scanner_requests_log_" . $dt->format("d-m-Y_h-i-s") . ".csv";

            while (ob_get_level()) {
                ob_end_clean();
            }
            nocache_headers();

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $downloadFileName . '"');

            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
            readfile($csvFilePath);
            exit();
        } catch (\Throwable $th) {
        }

        return false;
    }

    public static function log($request, $settings)
    {
        if (!current_user_can('administrator')) {
            return;
        }

        try {
            $debugInfo = $settings->getSettings("debugInfo");

            if (!$debugInfo || $debugInfo->value !== "on") {
                return;
            }

            $userId = Users::getUserId($request);
            $userName = "[" . $userId . "]";

            if ($user_data = get_userdata($userId)) {
                $userName = $user_data->display_name;
            }


            $logFileName = "usbs_requests_log";
            $wp_upload_dir = wp_upload_dir();
            $upload_dir = $wp_upload_dir['basedir'] . '/barcode-scanner/';
            $csvFilePath = $upload_dir . 'logs/' . $logFileName . '.csv';

            if (!file_exists($upload_dir)) {
                wp_mkdir_p($upload_dir);
            }
            if (!file_exists($upload_dir . 'logs')) {
                wp_mkdir_p($upload_dir . 'logs');
            }

            $scheme = is_ssl() ? 'https' : 'http';
            $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
            $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            $requestUrl = $host ? $scheme . '://' . $host . $uri : $uri;

            $dateTime = wp_date('Y-m-d H:i:s');
            $csvSeparator = ',';
            $isNewFile = !file_exists($csvFilePath);

            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
            $fp = fopen($csvFilePath, 'a');

            if ($fp === false) {
                return;
            }

            if (flock($fp, LOCK_EX)) {
                if ($isNewFile) {
                    fputcsv($fp, array('Date time', 'User Id', 'User name', 'Request URL'), $csvSeparator, '"', '\\');
                }

                fputcsv($fp, array($dateTime, $userId, $userName, $requestUrl), $csvSeparator, '"', '\\');
                flock($fp, LOCK_UN);
            }

            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            fclose($fp);
        } catch (\Throwable $th) {
        }
    }
}
