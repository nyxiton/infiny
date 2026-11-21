<?php

namespace UkrSolution\BarcodeScanner\API\classes;

class RequestHelper
{
    public static function getRequestPriority()
    {
        $priority = 10;

        try {
            $post = json_decode(file_get_contents("php://input"), true);

            $rout = $post && isset($post['rout']) ? sanitize_text_field(wp_unslash($post['rout'])) : '';
            $request_priority = $post && isset($post['request_priority']) ? sanitize_text_field(wp_unslash($post['request_priority'])) : '';
            $fulfillmentOrderId = $post && isset($post['fulfillmentOrderId']) ? sanitize_text_field(wp_unslash($post['fulfillmentOrderId'])) : '';
            $orderAutoAction = $post && isset($post['orderAutoAction']) ? sanitize_text_field(wp_unslash($post['orderAutoAction'])) : '';
            $postAutoAction = $post && isset($post['postAutoAction']) ? sanitize_text_field(wp_unslash($post['postAutoAction'])) : '';

            if ($request_priority == "low") {
                $priority = 999999;
            }
            else if (in_array($rout, array("changeStatus", "orderCreate", "updateProductMeta", "orderUpdateItemMeta"))) {
                $priority = 999999;
            }
            else if ($rout && $fulfillmentOrderId) {
                $priority = 999999;
            }
            else if (in_array($rout, array("getProductTaxonomy"))) {
                $priority = 999999;
            }
            else if (in_array($rout, array("userCreate"))) {
                $priority = 999999;
            }
            else if ($rout && $orderAutoAction && !empty($orderAutoAction) && $orderAutoAction != "empty") {
                $priority = 999999;
            }
            else if ($rout && $postAutoAction && !empty($postAutoAction) && $postAutoAction != "empty") {
                $priority = 999999;
            }
            else if ($rout && in_array($rout, array('update', 'batchNumbersRemoveBatch', 'batchNumbersAddNewBatch', 'batchNumbersSaveBatchField', 'batchNumbersWebisRemoveBatch', 'batchNumbersWebisAddNewBatch', 'batchNumbersWebisSaveBatchField'))) {
                $priority = 999999;
            }


        } catch (\Throwable $th) {
        }

        return $priority;
    }
}
