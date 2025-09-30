<?php
/**
 * REST API functionality for Music Gate plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class MG_REST {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route('musicgate/v1', '/register', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_registration'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('musicgate/v1', '/purchase', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_purchase'),
            'permission_callback' => '__return_true'
        ));
    }
    
    public function handle_registration($request) {
        $first_name = sanitize_text_field($request->get_param('first_name'));
        $last_name = sanitize_text_field($request->get_param('last_name'));
        $phone = sanitize_text_field($request->get_param('phone'));
        $password = $request->get_param('password');
        
        if (empty($first_name) || empty($last_name) || empty($phone) || empty($password)) {
            return new WP_Error('missing_fields', __('All fields are required.', MUSIC_GATE_TEXT_DOMAIN), array('status' => 400));
        }
        
        // Create user
        $username = $phone; // Use phone as username
        $email = $phone . '@musicgate.local'; // Generate email from phone
        
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Update user meta
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, 'phone', $phone);
        
        return array('success' => true, 'user_id' => $user_id);
    }
    
    public function handle_purchase($request) {
        $plan = sanitize_text_field($request->get_param('plan'));
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error('not_logged_in', __('You must be logged in to purchase.', MUSIC_GATE_TEXT_DOMAIN), array('status' => 401));
        }
        
        // Handle purchase logic here
        $payments = new MG_Payments();
        return $payments->create_payment($user_id, $plan);
    }
}
