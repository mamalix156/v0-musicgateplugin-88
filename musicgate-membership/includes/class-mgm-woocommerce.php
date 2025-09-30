<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MGM_WooCommerce_Integration {
    
    public function __construct() {
        add_action('woocommerce_thankyou', array($this, 'process_subscription_purchase'));
        add_action('woocommerce_order_status_completed', array($this, 'activate_subscription'));
    }
    
    public function process_subscription_purchase($order_id) {
        if (!$order_id) return;
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        // Check if this order has already been processed
        if (get_post_meta($order_id, '_mgm_processed', true)) {
            return;
        }
        
        $this->assign_subscription($order);
        
        // Mark order as processed
        update_post_meta($order_id, '_mgm_processed', '1');
    }
    
    public function activate_subscription($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        // Check if this order has already been processed
        if (get_post_meta($order_id, '_mgm_processed', true)) {
            return;
        }
        
        $this->assign_subscription($order);
        
        // Mark order as processed
        update_post_meta($order_id, '_mgm_processed', '1');
    }
    
    private function assign_subscription($order) {
        $user_id = $order->get_user_id();
        $order_id = $order->get_id();
        
        // Get product IDs from settings
        $product_mapping = array(
            get_option('mgm_product_1month', '31314') => '1month',
            get_option('mgm_product_6months', '31316') => '6months',
            get_option('mgm_product_1year', '31317') => '1year'
        );
        
        // Check order items for subscription products
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            
            if (isset($product_mapping[$product_id])) {
                $plan = $product_mapping[$product_id];
                
                // Add subscription
                $subscription_id = mgm_add_user_subscription($user_id, $plan, $order_id);
                
                if ($subscription_id) {
                    // Add order note
                    $order->add_order_note(sprintf(
                        'اشتراک %s برای کاربر اضافه شد (ID: %d)',
                        mgm_get_plan_name($plan),
                        $subscription_id
                    ));
                    
                    break; // Only process first matching product
                }
            }
        }
    }
}
?>
