<?php
/**
 * Subscription management for Music Gate plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class MG_Subscriptions {
    
    public function __construct() {
        add_action('music_gate_daily_check', array($this, 'check_expired_subscriptions'));
    }
    
    public function create_subscription($user_id, $plan, $order_id = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'musicgate_subscriptions';
        
        // Calculate end date based on plan
        $start_date = current_time('mysql');
        $end_date = $this->calculate_end_date($plan);
        
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
        
        return $result !== false;
    }
    
    public function get_user_subscription($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'musicgate_subscriptions';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND status = 'active' AND end_date > NOW() ORDER BY end_date DESC LIMIT 1",
            $user_id
        ));
    }
    
    public function is_user_subscribed($user_id) {
        $subscription = $this->get_user_subscription($user_id);
        return !empty($subscription);
    }
    
    public function check_expired_subscriptions() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'musicgate_subscriptions';
        
        $wpdb->update(
            $table,
            array('status' => 'expired'),
            array('status' => 'active'),
            array('%s'),
            array('%s')
        );
        
        $wpdb->query("UPDATE $table SET status = 'expired' WHERE status = 'active' AND end_date < NOW()");
    }
    
    private function calculate_end_date($plan) {
        $start = new DateTime();
        
        switch ($plan) {
            case 'month1':
                $start->add(new DateInterval('P1M'));
                break;
            case 'month3':
                $start->add(new DateInterval('P3M'));
                break;
            case 'year1':
                $start->add(new DateInterval('P1Y'));
                break;
            default:
                $start->add(new DateInterval('P1M'));
        }
        
        return $start->format('Y-m-d H:i:s');
    }
    
    public function get_all_subscriptions() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'musicgate_subscriptions';
        
        return $wpdb->get_results("
            SELECT s.*, u.display_name, u.user_email 
            FROM $table s 
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID 
            ORDER BY s.created_at DESC
        ");
    }
}
