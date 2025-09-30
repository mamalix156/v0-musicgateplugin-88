<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if user has active subscription
 */
function mgm_user_has_active_subscription($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'musicgate_subscriptions';
    
    $subscription = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name 
         WHERE user_id = %d 
         AND status = 'active' 
         AND end_date > NOW() 
         ORDER BY end_date DESC 
         LIMIT 1",
        $user_id
    ));
    
    return $subscription ? $subscription : false;
}

/**
 * Get user's subscription details
 */
function mgm_get_user_subscription($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    return mgm_user_has_active_subscription($user_id);
}

/**
 * Add or extend user subscription
 */
function mgm_add_user_subscription($user_id, $plan, $order_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'musicgate_subscriptions';
    
    // Duration mapping
    $durations = array(
        '1month' => 30,
        '6months' => 180,
        '1year' => 365
    );
    
    if (!isset($durations[$plan])) {
        return false;
    }
    
    $days = $durations[$plan];
    
    // Check if user has active subscription
    $existing = mgm_user_has_active_subscription($user_id);
    
    if ($existing) {
        // Extend existing subscription
        $new_end_date = date('Y-m-d H:i:s', strtotime($existing->end_date . " +{$days} days"));
        
        $wpdb->update(
            $table_name,
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
            $table_name,
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
function mgm_get_remaining_days($user_id = null) {
    $subscription = mgm_get_user_subscription($user_id);
    
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
function mgm_get_plan_name($plan) {
    $names = array(
        '1month' => 'یک ماهه',
        '6months' => 'شش ماهه', 
        '1year' => 'یک ساله'
    );
    
    return isset($names[$plan]) ? $names[$plan] : $plan;
}

/**
 * Get plan price
 */
function mgm_get_plan_price($plan) {
    $product_ids = array(
        '1month' => get_option('mgm_product_1month', '31314'),
        '6months' => get_option('mgm_product_6months', '31316'),
        '1year' => get_option('mgm_product_1year', '31317')
    );
    
    if (!isset($product_ids[$plan])) {
        return 0;
    }
    
    $product = wc_get_product($product_ids[$plan]);
    return $product ? $product->get_price() : 0;
}
?>
