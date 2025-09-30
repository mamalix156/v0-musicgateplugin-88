<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MG_REST {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route('musicgate/v1', '/subscription/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_subscription_status'),
            'permission_callback' => array($this, 'check_user_permission')
        ));
        
        register_rest_route('musicgate/v1', '/subscription/history', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_subscription_history'),
            'permission_callback' => array($this, 'check_user_permission')
        ));
    }
    
    public function check_user_permission() {
        return is_user_logged_in();
    }
    
    public function get_subscription_status($request) {
        $user_id = get_current_user_id();
        $subscription = mg_get_user_subscription($user_id);
        
        if ($subscription) {
            return rest_ensure_response(array(
                'has_subscription' => true,
                'plan' => $subscription->plan,
                'end_date' => $subscription->end_date,
                'remaining_days' => mg_get_remaining_days($user_id)
            ));
        } else {
            return rest_ensure_response(array(
                'has_subscription' => false
            ));
        }
    }
    
    public function get_subscription_history($request) {
        $user_id = get_current_user_id();
        
        global $wpdb;
        $subscriptions_table = $wpdb->prefix . 'musicgate_subscriptions';
        
        $subscriptions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $subscriptions_table WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
        
        return rest_ensure_response($subscriptions);
    }
}
