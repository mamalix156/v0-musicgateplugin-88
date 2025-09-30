<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MG_Payments {
    
    private $merchant_id;
    private $zarinpal_gate = 'https://api.zarinpal.com/pg/v4/payment/request.json';
    private $zarinpal_verify = 'https://api.zarinpal.com/pg/v4/payment/verify.json';
    private $zarinpal_start = 'https://www.zarinpal.com/pg/StartPay/';
    
    public function __construct() {
        $this->merchant_id = 'c5196eca-e170-48f0-a0e6-b38531087bfa';
        
        add_action('init', array($this, 'handle_callback'));
        add_action('wp_ajax_mg_create_payment', array($this, 'ajax_create_payment'));
        add_action('wp_ajax_nopriv_mg_create_payment', array($this, 'ajax_create_payment'));
        add_action('wp_ajax_mg_verify_payment', array($this, 'verify_payment'));
        add_action('wp_ajax_nopriv_mg_verify_payment', array($this, 'verify_payment'));
    }
    
    private function toman_to_rial($toman) {
        return intval($toman) * 10;
    }
    
    public function ajax_create_payment() {
        check_ajax_referer('mg_nonce', 'nonce');
        
        $plan = sanitize_text_field($_POST['plan']);
        $user_id = get_current_user_id();
        
        // For guest users, create temporary user record
        if (!$user_id) {
            $phone = sanitize_text_field($_POST['phone'] ?? '');
            $first_name = sanitize_text_field($_POST['first_name'] ?? '');
            $last_name = sanitize_text_field($_POST['last_name'] ?? '');
            
            if (empty($phone)) {
                wp_send_json_error('شماره تلفن الزامی است.');
            }
            
            // Create guest user record
            $user_id = $this->create_guest_user($phone, $first_name, $last_name);
        }
        
        $result = $this->create_payment($user_id, $plan);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    private function create_guest_user($phone, $first_name, $last_name) {
        global $wpdb;
        $users_table = $wpdb->prefix . 'musicgate_users';
        
        // Check if user exists
        $existing_user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $users_table WHERE phone = %s",
            $phone
        ));
        
        if ($existing_user) {
            return $existing_user->id;
        }
        
        // Create new guest user
        $wpdb->insert(
            $users_table,
            array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'phone' => $phone,
                'status' => 'guest',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    public function create_payment($user_id, $plan) {
        $plan_prices = array(
            'month1' => 43000,
            'month3' => 98000,
            'year1' => 198000
        );
        
        $plan_names = array(
            'month1' => get_option('music_gate_plan_month1_name', 'اشتراک یک ماهه'),
            'month3' => get_option('music_gate_plan_month3_name', 'اشتراک سه ماهه'),
            'year1' => get_option('music_gate_plan_year1_name', 'اشتراک سالانه')
        );
        
        if (!isset($plan_prices[$plan])) {
            return array('success' => false, 'message' => 'پلن انتخابی معتبر نیست.');
        }
        
        $amount_toman = $plan_prices[$plan];
        $amount_rial = $this->toman_to_rial($amount_toman); // Convert to Rial
        $description = $plan_names[$plan];
        $order_key = mg_generate_order_key();
        
        // Save order to database
        global $wpdb;
        $orders_table = $wpdb->prefix . 'musicgate_orders';
        
        $wpdb->insert(
            $orders_table,
            array(
                'order_key' => $order_key,
                'user_id' => $user_id,
                'plan' => $plan,
                'price_toman' => $amount_toman, // Store both Toman and Rial amounts
                'price_rial' => $amount_rial,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%d', '%d', '%s', '%s')
        );
        
        $order_id = $wpdb->insert_id;
        
        $callback_url = 'https://melodicc.com/?zarinpal_return=1&order_key=' . $order_key;
        
        $data = array(
            'merchant_id' => $this->merchant_id,
            'amount' => $amount_rial, // Send in Rial
            'description' => $description,
            'callback_url' => $callback_url,
            'metadata' => array(
                'order_key' => $order_key,
                'plan' => $plan
            )
        );
        
        $response = wp_remote_post($this->zarinpal_gate, array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'ZarinPal Rest Api v4'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('Zarinpal Request Error: ' . $response->get_error_message());
            return array('success' => false, 'message' => 'خطا در اتصال به درگاه پرداخت.');
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        error_log('Zarinpal Response: ' . $body);
        
        if (isset($result['data']['code']) && intval($result['data']['code']) === 100) {
            $authority = $result['data']['authority'];
            
            // Update order with authority
            $wpdb->update(
                $orders_table,
                array(
                    'authority' => $authority,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $order_id),
                array('%s', '%s'),
                array('%d')
            );
            
            return array(
                'success' => true,
                'redirect_url' => $this->zarinpal_start . $authority
            );
        } else {
            $error_message = isset($result['errors']) ? implode(', ', $result['errors']) : 'خطا در ایجاد درخواست پرداخت.';
            error_log('Zarinpal Error: ' . $error_message);
            return array('success' => false, 'message' => $error_message);
        }
    }
    
    public function handle_callback() {
        if ((isset($_GET['zarinpal_return']) && $_GET['zarinpal_return'] == '1') || 
            (isset($_GET['musicgate_zarinpal_callback']) && $_GET['musicgate_zarinpal_callback'] == '1')) {
            $this->process_callback();
        }
    }
    
    private function process_callback() {
        if (!isset($_GET['order_key']) || !isset($_GET['Authority'])) {
            wp_die('پارامترهای callback نامعتبر است.');
        }
        
        $order_key = sanitize_text_field($_GET['order_key']);
        $authority = sanitize_text_field($_GET['Authority']);
        $status = sanitize_text_field($_GET['Status'] ?? '');
        
        global $wpdb;
        $orders_table = $wpdb->prefix . 'musicgate_orders';
        
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $orders_table WHERE order_key = %s",
            $order_key
        ));
        
        if (!$order) {
            wp_die('سفارش یافت نشد.');
        }
        
        if ($order->status === 'success') {
            $this->redirect_with_welcome();
            return;
        }
        
        if ($status !== 'OK') {
            // Payment cancelled or failed
            $wpdb->update(
                $orders_table,
                array(
                    'status' => 'failed',
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $order->id),
                array('%s', '%s'),
                array('%d')
            );
            
            wp_redirect(home_url('?mg_error=payment_failed'));
            exit;
        }
        
        // Verify payment
        $verify_result = $this->verify_payment_internal($authority, $order->price_rial);
        
        if ($verify_result['success']) {
            // Update order status
            $wpdb->update(
                $orders_table,
                array(
                    'status' => 'success',
                    'ref_id' => $verify_result['ref_id'],
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $order->id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            
            // Create subscription
            $this->create_subscription($order->user_id, $order->plan, $order->id);
            
            $this->redirect_with_welcome();
        } else {
            // Payment verification failed
            $wpdb->update(
                $orders_table,
                array(
                    'status' => 'failed',
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $order->id),
                array('%s', '%s'),
                array('%d')
            );
            
            wp_redirect(home_url('?mg_error=verify_failed'));
            exit;
        }
    }
    
    private function redirect_with_welcome() {
        // Set welcome message in transient (more reliable than session)
        $user_id = get_current_user_id();
        if (!$user_id) {
            // For guest users, use a session-based approach
            if (!session_id()) {
                session_start();
            }
            $_SESSION['mg_welcome_message'] = 'به خانواده بزرگ ملودیک خوش‌آمدید! لذت دسترسی به دنیای نامحدود موزیک‌ها را تجربه کنید.';
        } else {
            set_transient('mg_welcome_message_' . $user_id, 'به خانواده بزرگ ملودیک خوش‌آمدید! لذت دسترسی به دنیای نامحدود موزیک‌ها را تجربه کنید.', 300);
        }
        wp_redirect(home_url('?mg_success=1'));
        exit;
    }
    
    private function verify_payment_internal($authority, $amount_rial) {
        $data = array(
            'merchant_id' => $this->merchant_id,
            'authority' => $authority,
            'amount' => $amount_rial // Use Rial amount for verification
        );
        
        $response = wp_remote_post($this->zarinpal_verify, array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('Zarinpal Verify Error: ' . $response->get_error_message());
            return array('success' => false, 'message' => 'خطا در تأیید پرداخت.');
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        error_log('Zarinpal Verify Response: ' . $body);
        
        if (isset($result['data']['code']) && intval($result['data']['code']) === 100) {
            return array('success' => true, 'ref_id' => $result['data']['ref_id']);
        } else {
            $error_message = isset($result['errors']) ? implode(', ', $result['errors']) : 'پرداخت تأیید نشد.';
            return array('success' => false, 'message' => $error_message);
        }
    }
    
    private function create_subscription($user_id, $plan, $order_id) {
        $plan_durations = array(
            'month1' => '+1 month',
            'month3' => '+3 months',
            'year1' => '+1 year'
        );
        
        $duration = $plan_durations[$plan] ?? '+1 month';
        $start_date = current_time('mysql');
        $end_date = date('Y-m-d H:i:s', strtotime($duration, current_time('timestamp')));
        
        global $wpdb;
        $subscriptions_table = $wpdb->prefix . 'musicgate_subscriptions';
        
        // Deactivate existing subscriptions
        $wpdb->update(
            $subscriptions_table,
            array('status' => 'inactive'),
            array('user_id' => $user_id, 'status' => 'active'),
            array('%s'),
            array('%d', '%s')
        );
        
        // Create new subscription
        $wpdb->insert(
            $subscriptions_table,
            array(
                'user_id' => $user_id,
                'plan' => $plan,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => 'active',
                'order_id' => $order_id,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s')
        );
    }
}
