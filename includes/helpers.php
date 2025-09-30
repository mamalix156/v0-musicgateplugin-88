<?php
/**
 * Helper functions for Music Gate plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if user has active subscription
 */
function mg_user_has_subscription($user_id = null) {
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
 * Get plan details
 */
function mg_get_plan_details($plan) {
    $plans = array(
        'month1' => array(
            'name' => __('1 Month', MUSIC_GATE_TEXT_DOMAIN),
            'duration' => '+1 month'
        ),
        'month3' => array(
            'name' => __('3 Months', MUSIC_GATE_TEXT_DOMAIN),
            'duration' => '+3 months'
        ),
        'year1' => array(
            'name' => __('1 Year', MUSIC_GATE_TEXT_DOMAIN),
            'duration' => '+1 year'
        )
    );
    
    return isset($plans[$plan]) ? $plans[$plan] : null;
}

/**
 * Format price
 */
function mg_format_price($amount) {
    return number_format($amount) . ' ' . __('Toman', MUSIC_GATE_TEXT_DOMAIN);
}

/**
 * Generate unique order key
 */
function mg_generate_order_key() {
    return 'MG_' . time() . '_' . wp_generate_password(8, false);
}

/**
 * Sanitize phone number
 */
function mg_sanitize_phone($phone) {
    return preg_replace('/[^0-9+]/', '', $phone);
}

/**
 * Add or extend user subscription
 */
function mg_add_user_subscription($user_id, $plan, $order_id = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'musicgate_subscriptions';
    
    // Duration mapping
    $durations = array(
        'month1' => 30,
        'month3' => 90,
        'year1' => 365
    );
    
    if (!isset($durations[$plan])) {
        return false;
    }
    
    $days = $durations[$plan];
    
    // Check if user has active subscription
    $existing = mg_get_user_subscription($user_id);
    
    if ($existing) {
        // Extend existing subscription
        $new_end_date = date('Y-m-d H:i:s', strtotime($existing->end_date . " +{$days} days"));
        
        $wpdb->update(
            $table,
            array('end_date' => $new_end_date),
            array('id' => $existing->id),
            array('%s'),
            array('%d')
        );
        
        return $existing->id;
    } else {
        // Create new subscription
        $start_date = current_time('mysql');
        $end_date = date('Y-m-d H:i:s', strtotime($start_date . " +{$days} days"));
        
        $result = $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'plan' => $plan,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => 'active',
                'order_id' => $order_id
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
}

/**
 * Get remaining days for user subscription
 */
function mg_get_remaining_days($user_id = null) {
    $subscription = mg_get_user_subscription($user_id);
    
    if (!$subscription) {
        return 0;
    }
    
    $end_date = new DateTime($subscription->end_date);
    $now = new DateTime();
    $diff = $now->diff($end_date);
    
    return $diff->invert ? 0 : $diff->days;
}

/**
 * Get plan name in Persian
 */
function mg_get_plan_name($plan) {
    $names = array(
        'month1' => 'یک ماهه',
        'month3' => 'سه ماهه', 
        'year1' => 'یک ساله',
        '1' => 'یک ماهه',
        '6' => 'شش ماهه',
        '12' => 'یک ساله'
    );
    
    return isset($names[$plan]) ? $names[$plan] : $plan;
}

/**
 * Get plan days
 */
function mg_get_plan_days($plan) {
    $days = array(
        'month1' => 30,
        'month3' => 90,
        'year1' => 365,
        '1' => 30,
        '6' => 180,
        '12' => 365
    );
    
    return isset($days[$plan]) ? $days[$plan] : 30;
}

/**
 * Check if current page should be restricted
 */
function mg_should_restrict_content() {
    // Don't restrict checkout page
    if (function_exists('is_checkout') && is_checkout()) {
        return false;
    }
    
    // Don't restrict admin pages
    if (is_admin()) {
        return false;
    }
    
    // Don't restrict for users with active subscription
    if (mg_user_has_subscription()) {
        return false;
    }
    
    return true;
}
