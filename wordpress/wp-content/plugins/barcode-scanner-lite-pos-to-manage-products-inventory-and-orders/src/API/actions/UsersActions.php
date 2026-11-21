<?php

namespace UkrSolution\BarcodeScanner\API\actions;

use UkrSolution\BarcodeScanner\API\classes\Users;
use UkrSolution\BarcodeScanner\API\RequestHelper;
use UkrSolution\BarcodeScanner\features\interfaceData\InterfaceData;
use UkrSolution\BarcodeScanner\features\settings\Settings;
use UkrSolution\BarcodeScanner\features\settings\SettingsHelper;
use WP_REST_Request;

class UsersActions
{
    public function find(WP_REST_Request $request)
    {
        global $wpdb;

        $query = RequestHelper::getQuery($request, "user_find");
        $source = $request->get_param("source");
        $users = array();
        $errors = array();

        $currentIds = $request->get_param("currentIds");
        $currentIds = $currentIds && is_array($currentIds) ? array_map('intval', $currentIds) : array();

        $firstName = $query;
        $lastName = $query;
        $parts = $query ? explode(" ", $query) : array();

        if (count($parts) == 2) {
            $firstName = $parts[0];
            $lastName = $parts[1];
        }

        try {
            // @codingStandardsIgnoreStart

            $sql = "SELECT * FROM {$wpdb->users} AS u ";

            $sql .= " WHERE u.ID = %d
                OR u.user_nicename LIKE %s
                OR u.user_email LIKE %s
                OR u.display_name LIKE %s
            ";

            $sql .= " OR (
                    (SELECT _um.umeta_id
                    FROM {$wpdb->usermeta} AS _um
                    WHERE _um.user_id = u.ID
                    AND _um.meta_key = 'first_name'
                    AND _um.meta_value LIKE %s
                    LIMIT 1)

                    AND

                    (SELECT _um.umeta_id
                    FROM {$wpdb->usermeta} AS _um
                    WHERE _um.user_id = u.ID
                    AND _um.meta_key = 'last_name'
                    AND _um.meta_value LIKE %s
                    LIMIT 1)
                )
            ";

            $sql .= " OR (
                    SELECT _um.umeta_id
                    FROM {$wpdb->usermeta} AS _um
                    WHERE _um.user_id = u.ID
                    AND (
                        (
                            _um.meta_key = 'billing_phone'
                            AND _um.meta_value LIKE %s
                        )
                        OR
                        (
                            _um.meta_key = 'shipping_phone'
                            AND _um.meta_value LIKE %s
                        )
                    )
                    LIMIT 1
                )
            ";

            $qetyLike = $wpdb->esc_like($query);
            $qetyLike = str_replace(' ', '%', $qetyLike);

            $prepare_args = array(
                (int) $query,
                '%' . $qetyLike . '%',
                '%' . $qetyLike . '%',
                '%' . $qetyLike . '%',

                '%' . $wpdb->esc_like($firstName) . '%',
                '%' . $wpdb->esc_like($lastName) . '%',

                '%' . $qetyLike . '%',
                '%' . $qetyLike . '%',
            );

            if (!empty($currentIds)) {
                $placeholders = implode(', ', array_fill(0, count($currentIds), '%d'));

                $sql .= " OR u.ID IN ({$placeholders}) ";

                foreach ($currentIds as $current_id) {
                    $prepare_args[] = (int) $current_id;
                }
            }

            $sql .= ' LIMIT 10';

            $rows = $wpdb->get_results($wpdb->prepare($sql, $prepare_args));

            // @codingStandardsIgnoreEnd

            foreach ($rows as $value) {
                $userMeta = get_userdata($value->ID);
                $roles = array();

                if ($userMeta->roles) {
                    foreach ($userMeta->roles as $_role) {
                        if (is_string($_role)) {
                            $roles[] = $_role;
                        }
                    }
                }


                $users[] = array(
                    'ID' => $value->ID,
                    'user_login' => $value->user_login,
                    'user_nicename' => $value->user_nicename,
                    'display_name' => $value->display_name . " (" . $value->user_login . ")",
                    'full_name' => trim(get_user_meta($value->ID, 'first_name', true) . " " . get_user_meta($value->ID, 'last_name', true)),
                    'email' => $value->user_email,
                    'avatar' => get_avatar_url($value->ID),
                    'roles' => $roles,
                );
            }
        } catch (\Throwable $th) {
            $errors[] = $th->getMessage();
        }


        $result = array(
            "users" => $users,
            "usersErrors" => $errors,
            "createNew" => count($users) === 0
        );

        return rest_ensure_response($result);
    }


    public function getRoleData(WP_REST_Request $request)
    {
        global $wp_roles;

        $roleSlug = $request->get_param("query");
        $roleData = array();
        $errors = array();

        if ($roleSlug) {
            $role = get_role($roleSlug);

            if ($role) {
                if (!isset($wp_roles)) {
                    $wp_roles = new WP_Roles();
                }
                $roleData["name"] = $role->name;
                $roleData["label"] = isset($wp_roles->role_names[$roleSlug]) ? translate_user_role($wp_roles->role_names[$roleSlug]) : $roleSlug;

                $userIds = get_users(array('role' => $roleSlug, 'fields' => 'ID'));
                $roleData["ids"] = $userIds;
            }
        }

        return rest_ensure_response(array("roleData" => $roleData, "errors" => $errors));
    }

    public function createUser(WP_REST_Request $request)
    {
        global $wpdb;

        $userData = $request->get_param("userData");

        if (!isset($userData["username"])) {
            return rest_ensure_response(array("error" => esc_html__("Username is required", "barcode-scanner-lite-pos-to-manage-products-inventory-and-orders")));
        }

        $settings = new Settings();
        $orderCreateEmail = $settings->getField("general", "orderCreateEmail", "on");
        $shippingAsBilling = isset($userData["shipping_as_billing"]) && $userData["shipping_as_billing"] == 1;
        $email = isset($userData["email"]) ? $userData["email"] : '';
        $role = isset($userData["role"]) ? $userData["role"] : 'customer';

        $password = wp_generate_password(32, true, true);
        $userId = wp_create_user(sanitize_user($userData["username"]), $password, sanitize_email($email));

        if (is_wp_error($userId)) {
            return rest_ensure_response(array("error" => $userId->get_error_message()));
        }
        else if ($orderCreateEmail === "on" && $userId && $email) {
            wp_new_user_notification($userId, null, 'both');
        }

        if (!is_wp_error($userId)) {
            $user = new WP_User($userId);
            $user->set_role('subscriber');
        }


        if ($userId) {
            $keysList = array(
                'first_name',
                'last_name',
                'billing_first_name',
                'billing_last_name',
                'billing_company',
                'billing_phone',
                'billing_address_1',
                'billing_address_2',
                'billing_city',
                'billing_postcode',
                'billing_country',
                'billing_state',
                'billing_email',
                'shipping_first_name',
                'shipping_last_name',
                'shipping_company',
                'shipping_phone',
                'shipping_address_1',
                'shipping_address_2',
                'shipping_city',
                'shipping_postcode',
                'shipping_country',
                'shipping_state'
            );

            foreach (InterfaceData::getUserFields() as $value) {
                if (isset($value['_name'])) {
                    $keysList[] = $value['_name'];
                }
            }

            foreach ($userData as $key => $value) {
                if (in_array($key, $keysList)) {
                    if ($shippingAsBilling && preg_match("/shipping_.*/", $key)) {
                        continue;
                    } else {
                        \update_user_meta($userId, $key, $value);
                    }

                    if ($shippingAsBilling && preg_match("/billing_.*/", $key)) {
                        $shippingKey = str_replace("billing_", "shipping_", $key);
                        \update_user_meta($userId, $shippingKey, $value);
                    }
                }

                apply_filters('barcode_scanner_update_user_custom_fields', $key, $value, $userId);
            }
        }

        $user = array();

        try {
            // @codingStandardsIgnoreStart
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->users} as u WHERE u.ID = %d;", $userId));
            // @codingStandardsIgnoreEnd

            $user = array(
                'ID' => $row->ID,
                'user_nicename' => $row->user_nicename,
                'display_name' => $row->display_name,
            );

        } catch (\Throwable $th) {
            $errors[] = $th->getMessage();
        }

        return rest_ensure_response(array("user" => $user));
    }

    public function getStates(WP_REST_Request $request)
    {
        $country = $request->get_param("country");
        $states = array();

        try {
            $countries_obj = new \WC_Countries();
            $states = $countries_obj->get_states($country);
        } catch (\Throwable $th) {
            $errors[] = $th->getMessage();
        }

        return rest_ensure_response(array("states" => $states));
    }

    public function updatePassword(WP_REST_Request $request)
    {
        $userId = $request->get_param("userId");
        $errors = array();
        $password = "";

        try {
            $user = get_user_by('ID', $userId);
            if (!$user) {
                return rest_ensure_response(array(
                    "states" => false,
                    "errors" => ["User not found."]
                ));
            }
            $password = $this->usersGenerateOtp();

            update_user_meta($userId, "barcode_scanner_app_otp", md5($password));
            update_user_meta($userId, "barcode_scanner_app_otp_expired_dt", time());
            update_user_meta($userId, "barcode_scanner_app_auth_method", "");
        } catch (\Throwable $th) {
            $errors[] = $th->getMessage();
        }

        return rest_ensure_response(array(
            "states" => true,
            "password" => $password,
            "userId" => $userId,
            "errors" => $errors,
            "users" => $this->getUsersOtpStatus()
        ));
    }

    public function usersGenerateOtp()
    {
        return strtoupper(SettingsHelper::generateRandomString(3)) . wp_rand(100, 999);
    }

    public function getUsersOtpStatus()
    {
        global $wpdb;

        $users = array();

        // @codingStandardsIgnoreStart
        $usersMeta = $wpdb->get_results("SELECT * FROM {$wpdb->usermeta} AS UM WHERE UM.meta_key IN ('barcode_scanner_app_otp')");
        // @codingStandardsIgnoreEnd

        foreach ($usersMeta as $value) {
            $users[$value->user_id] = $value->meta_value;
        }

        return $users;
    }

    public function getUsersOtpExpired()
    {
        global $wpdb;

        $users = array();

        // @codingStandardsIgnoreStart
        $usersMeta = $wpdb->get_results("SELECT * FROM {$wpdb->usermeta} AS UM WHERE UM.meta_key IN ('barcode_scanner_app_otp_expired_dt')");
        // @codingStandardsIgnoreEnd

        try {
            foreach ($usersMeta as $value) {
                if ($value->meta_value) {
                    $users[$value->user_id] = (time() - $value->meta_value) > 60 * 60 * 24 * 30 ? 1 : 0;
                }
            }
        } catch (\Throwable $th) {
        }

        return $users;
    }

    public function getUserMeta($userId)
    {
        $fields = array("orderFulfillmentByDefault", "usbs_search_input_type");
        $fieldsData = array();

        try {
            foreach ($fields as $field) {
                $fieldsData[$field] = get_user_meta($userId, $field, true);
            }
        } catch (\Throwable $th) {
        }

        return $fieldsData;
    }

    public function setUserMeta(WP_REST_Request $request)
    {
        $userId = Users::getUserId($request);
        $fields = $request->get_param("fields");
        $errors = array();

        try {
            $available = array('usbs_search_input_type', 'orderFulfillmentByDefault');

            foreach ($fields as $field) {
                if (in_array($field['meta_key'], $available)) {
                    \update_user_meta($userId, $field['meta_key'], $field['meta_value']);
                }
            }
        } catch (\Throwable $th) {
            $errors[] = $th->getMessage();
        }

        return rest_ensure_response(array("errors" => $errors, "fields" => $this->getUserMeta($userId)));
    }
}
