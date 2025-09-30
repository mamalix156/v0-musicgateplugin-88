<?php
/**
 * Payment handling for Music Gate plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class MG_Payments {
    
    private $merchant_id;
    private $zarinpal_gate = 'https://api.zarinpal.com/pg/v4/payment/request.json';
    private $zarinpal_verify = 'https://api.zarinpal.com/pg/v4/payment/verify.json';
    private $zarinpal_start = 'https://www.zarinpal.com/pg/StartPay/';
    
    public function __construct() {
        $this->merchant_id = get_option('music_gate_zarinpal_merchant', 'c5196eca-e170-48f0-a0e6-b38531087bfa');
        
        add_action('init', array($this, 'handle_payment_request'));
        add_action('init', array($this, 'handle_callback'));
        add_action('wp_ajax_mg_create_payment', array($this, 'ajax_create_payment'));
        add_action('wp_ajax_nopriv_mg_create_payment', array($this, 'ajax_create_payment'));
    }
    
    public function handle_payment_request() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'mg_buy') {
            return;
        }
        
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(add_query_arg($_GET, home_url())));
            exit;
        }
        
        $plan = sanitize_text_field($_GET['plan']);
        $nonce = sanitize_text_field($_GET['nonce']);
        
        if (!wp_verify_nonce($nonce, 'mg_buy_' . $plan)) {
            wp_die(__('بررسی امنیتی ناموفق بود.', MUSIC_GATE_TEXT_DOMAIN));
        }
        
        $this->process_payment($plan);
    }
    
    public function handle_callback() {
        if (!isset($_GET['mg_zarinpal_callback']) || $_GET['mg_zarinpal_callback'] != '1') {
            return;
        }
        
        $order_key = sanitize_text_field($_GET['order_key'] ?? '');
        $authority = sanitize_text_field($_GET['Authority'] ?? '');
        $status = sanitize_text_field($_GET['Status'] ?? '');
        
        if (empty($order_key) || empty($authority)) {
            wp_redirect(add_query_arg('mg_error', 'invalid_params', home_url('/')));
            exit;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'musicgate_orders';
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE order_key = %s",
            $order_key
        ));
        
        if (!$order) {
            wp_redirect(add_query_arg('mg_error', 'order_not_found', home_url('/')));
            exit;
        }
        
        if ($status !== 'OK') {
            $wpdb->update(
                $table,
                array('status' => 'failed'),
                array('id' => $order->id),
                array('%s'),
                array('%d')
            );
            wp_redirect(add_query_arg('mg_error', 'payment_failed', home_url('/')));
            exit;
        }
        
        $data = array(
            'merchant_id' => $this->merchant_id,
            'amount' => intval($order->amount),
            'authority' => $authority
        );
        
        $response = $this->zarinpal_request($this->zarinpal_verify, $data);
        
        error_log('ZarinPal Verify Response: ' . json_encode($response));
        
        if ($response && isset($response['data']['code']) && ($response['data']['code'] == 100 || $response['data']['code'] == 101)) {
            $ref_id = $response['data']['ref_id'];
            
            $wpdb->update(
                $table,
                array(
                    'status' => 'success',
                    'zarinpal_ref_id' => $ref_id,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $order->id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            
            $this->create_or_extend_subscription($order->user_id, $order->plan, $order->id);
            
            wp_redirect(add_query_arg('mg_success', '1', home_url('/')));
            exit;
        } else {
            // Verification failed
            $wpdb->update(
                $table,
                array('status' => 'failed'),
                array('id' => $order->id),
                array('%s'),
                array('%d')
            );
            
            error_log('ZarinPal Verify Error: ' . json_encode($response));
            wp_redirect(add_query_arg('mg_error', 'verification_failed', home_url('/')));
            exit;
        }
    }
    
    public function ajax_create_payment() {
        check_ajax_referer('music_gate_nonce', 'nonce');
        
        $plan = sanitize_text_field($_POST['plan']);
        
        $plan_amounts = array(
            '1' => 500000,    // 50,000 Toman
            '6' => 1200000,   // 120,000 Toman
            '12' => 4000000,  // 400,000 Toman
            'month1' => 500000,
            'month3' => 1200000,
            'year1' => 4000000
        );
        
        if (!isset($plan_amounts[$plan])) {
            wp_send_json_error(array('message' => 'پلان انتخاب شده معتبر نیست.'));
            return;
        }
        
        $amount_rial = $plan_amounts[$plan];
        
        if (!is_user_logged_in()) {
            $first_name = sanitize_text_field($_POST['first_name'] ?? '');
            $last_name = sanitize_text_field($_POST['last_name'] ?? '');
            $phone = mg_sanitize_phone($_POST['phone'] ?? '');
            
            if (empty($first_name) || empty($last_name) || empty($phone)) {
                wp_send_json_error(array('message' => 'لطفاً تمام فیلدها را پر کنید.'));
                return;
            }
            
            // Check if user exists
            $existing_user = get_users(array(
                'meta_key' => 'phone',
                'meta_value' => $phone,
                'number' => 1
            ));
            
            if (!empty($existing_user)) {
                $user_id = $existing_user[0]->ID;
            } else {
                // Create new user
                $username = 'user_' . $phone;
                $email = $phone . '@musicgate.local';
                $password = wp_generate_password(12, false);
                
                $user_id = wp_create_user($username, $password, $email);
                
                if (is_wp_error($user_id)) {
                    wp_send_json_error(array('message' => 'خطا در ایجاد حساب کاربری.'));
                    return;
                }
                
                update_user_meta($user_id, 'first_name', $first_name);
                update_user_meta($user_id, 'last_name', $last_name);
                update_user_meta($user_id, 'phone', $phone);
            }
            
            // Auto login
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
        } else {
            $user_id = get_current_user_id();
        }
        
        global $wpdb;
        $order_key = mg_generate_order_key();
        $table = $wpdb->prefix . 'musicgate_orders';
        
        $wpdb->insert(
            $table,
            array(
                'order_key' => $order_key,
                'user_id' => $user_id,
                'plan' => $plan,
                'amount' => $amount_rial,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%d', '%s', '%s')
        );
        
        $order_id = $wpdb->insert_id;
        
        if (!$order_id) {
            wp_send_json_error(array('message' => 'خطا در ایجاد سفارش.'));
            return;
        }
        
        $callback_url = add_query_arg(array(
            'mg_zarinpal_callback' => '1',
            'order_key' => $order_key
        ), site_url('/'));
        
        $user = get_userdata($user_id);
        $phone = get_user_meta($user_id, 'phone', true);
        
        $data = array(
            'merchant_id' => $this->merchant_id,
            'amount' => $amount_rial,
            'callback_url' => $callback_url,
            'description' => 'MusicGate subscription ' . $plan . ' month',
            'metadata' => array(
                'order_key' => $order_key,
                'user_id' => $user_id,
                'mobile' => $phone,
                'email' => $user->user_email
            )
        );
        
        error_log('ZarinPal Request: ' . json_encode($data));
        
        $response = $this->zarinpal_request($this->zarinpal_gate, $data);
        
        error_log('ZarinPal Response: ' . json_encode($response));
        
        if ($response && isset($response['data']['code']) && $response['data']['code'] == 100) {
            $authority = $response['data']['authority'];
            
            // Store authority in order
            $wpdb->update(
                $table,
                array('zarinpal_authority' => $authority),
                array('id' => $order_id),
                array('%s'),
                array('%d')
            );
            
            $redirect_url = $this->zarinpal_start . $authority;
            wp_send_json_success(array('redirect_url' => $redirect_url));
        } else {
            // Mark order as failed
            $wpdb->update(
                $table,
                array('status' => 'failed'),
                array('id' => $order_id),
                array('%s'),
                array('%d')
            );
            
            $error_message = isset($response['errors']) ? json_encode($response['errors']) : 'Unknown error';
            error_log('ZarinPal Error: ' . $error_message);
            
            wp_send_json_error(array('message' => 'خطا در اتصال به درگاه پرداخت.'));
        }
    }
    
    private function process_payment($plan, $ajax = false) {
        $user_id = get_current_user_id();
        $price = get_option('music_gate_plan_' . $plan . '_price', 0);
        $enabled = get_option('music_gate_plan_' . $plan . '_enabled', 0);
        
        if (!$enabled || !$price) {
            if ($ajax) {
                return false;
            }
            wp_die(__('پلان انتخاب شده معتبر نیست.', MUSIC_GATE_TEXT_DOMAIN));
        }
        
        global $wpdb;
        $order_key = mg_generate_order_key();
        $table = $wpdb->prefix . 'musicgate_orders';
        
        $wpdb->insert(
            $table,
            array(
                'order_key' => $order_key,
                'user_id' => $user_id,
                'plan' => $plan,
                'amount' => $price,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%d', '%s', '%s')
        );
        
        $order_id = $wpdb->insert_id;
        
        if (!$order_id) {
            if ($ajax) {
                return false;
            }
            wp_die(__('خطا در ایجاد سفارش.', MUSIC_GATE_TEXT_DOMAIN));
        }
        
        $callback_url = add_query_arg(array(
            'mg_zarinpal_callback' => '1',
            'order_key' => $order_key
        ), site_url('/'));
        
        $user = get_userdata($user_id);
        $phone = get_user_meta($user_id, 'phone', true);
        
        $data = array(
            'merchant_id' => $this->merchant_id,
            'amount' => intval($price),
            'callback_url' => $callback_url,
            'description' => 'MusicGate subscription ' . $plan . ' month',
            'metadata' => array(
                'order_key' => $order_key,
                'user_id' => $user_id,
                'mobile' => $phone,
                'email' => $user->user_email
            )
        );
        
        error_log('ZarinPal Request: ' . json_encode($data));
        
        $response = $this->zarinpal_request($this->zarinpal_gate, $data);
        
        error_log('ZarinPal Response: ' . json_encode($response));
        
        if ($response && isset($response['data']['code']) && $response['data']['code'] == 100) {
            $authority = $response['data']['authority'];
            
            // Store authority in order
            $wpdb->update(
                $table,
                array('zarinpal_authority' => $authority),
                array('id' => $order_id),
                array('%s'),
                array('%d')
            );
            
            $redirect_url = $this->zarinpal_start . $authority;
            
            if ($ajax) {
                return array('redirect_url' => $redirect_url);
            }
            
            wp_redirect($redirect_url);
            exit;
        } else {
            // Mark order as failed
            $wpdb->update(
                $table,
                array('status' => 'failed'),
                array('id' => $order_id),
                array('%s'),
                array('%d')
            );
            
            $error_message = isset($response['errors']) ? json_encode($response['errors']) : 'Unknown error';
            error_log('ZarinPal Error: ' . $error_message);
            
            if ($ajax) {
                return false;
            }
            wp_die(__('خطا در اتصال به درگاه پرداخت.', MUSIC_GATE_TEXT_DOMAIN));
        }
    }
    
    private function create_or_extend_subscription($user_id, $plan, $order_id) {
        // Map plan to months
        $plan_months = array(
            '1' => 1,
            '6' => 6,
            '12' => 12,
            'month1' => 1,
            'month3' => 3,
            'year1' => 12
        );
        
        $months = isset($plan_months[$plan]) ? $plan_months[$plan] : 1;
        
        $now = current_time('timestamp');
        $sub_end = get_user_meta($user_id, 'mg_sub_end', true);
        
        if ($sub_end && strtotime($sub_end) > $now) {
            // Extend existing subscription
            $new_end = date('Y-m-d H:i:s', strtotime($sub_end . ' +' . $months . ' months'));
        } else {
            // Create new subscription
            update_user_meta($user_id, 'mg_sub_start', current_time('mysql'));
            $new_end = date('Y-m-d H:i:s', strtotime('+' . $months . ' months', $now));
        }
        
        update_user_meta($user_id, 'mg_sub_end', $new_end);
        update_user_meta($user_id, 'mg_last_order_id', $order_id);
        
        // Also update the subscriptions table for compatibility
        global $wpdb;
        $sub_table = $wpdb->prefix . 'musicgate_subscriptions';
        
        $wpdb->insert(
            $sub_table,
            array(
                'user_id' => $user_id,
                'plan' => $plan,
                'start_date' => current_time('mysql'),
                'end_date' => $new_end,
                'status' => 'active',
                'order_id' => $order_id
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d')
        );
    }
    
    private function zarinpal_request($url, $data) {
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 45,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            error_log('ZarinPal Request Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        return $decoded;
    }
}
