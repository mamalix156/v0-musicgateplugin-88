<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper functions for Music Gate plugin
 */

/**
 * Check if user has active subscription
 */
function mg_user_has_active_subscription($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'musicgate_subscriptions';
    
    $subscription = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d AND status = 'active' AND end_date > NOW() ORDER BY end_date DESC LIMIT 1",
        $user_id
    ));
    
    return !empty($subscription);
}

/**
 * Get user's active subscription
 */
function mg_get_user_subscription($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return null;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'musicgate_subscriptions';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d AND status = 'active' AND end_date > NOW() ORDER BY end_date DESC LIMIT 1",
        $user_id
    ));
}

/**
 * Get remaining subscription days
 */
function mg_get_remaining_days($user_id = null) {
    $subscription = mg_get_user_subscription($user_id);
    
    if (!$subscription) {
        return 0;
    }
    
    $end_date = new DateTime($subscription->end_date);
    $now = new DateTime();
    $diff = $now->diff($end_date);
    
    return $diff->days;
}

/**
 * Format Persian numbers
 */
function mg_persian_numbers($string) {
    $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    $english = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    
    return str_replace($english, $persian, $string);
}

/**
 * Format price with Persian numbers and currency
 */
function mg_format_price($price) {
    return mg_persian_numbers(number_format($price)) . ' تومان';
}

/**
 * Generate unique order key
 */
function mg_generate_order_key() {
    return 'MG_' . time() . '_' . wp_rand(1000, 9999);
}
