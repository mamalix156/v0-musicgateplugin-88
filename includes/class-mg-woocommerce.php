<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MG_WooCommerce_Integration {
    
    public function __construct() {
        // Hook into WooCommerce order completion
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
        if (get_post_meta($order_id, '_mg_processed', true)) {
            return;
        }
        
        $this->assign_subscription($order);
        
        // Mark order as processed
        update_post_meta($order_id, '_mg_processed', '1');
    }
    
    public function activate_subscription($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        // Check if this order has already been processed
        if (get_post_meta($order_id, '_mg_processed', true)) {
            return;
        }
        
        $this->assign_subscription($order);
        
        // Mark order as processed
        update_post_meta($order_id, '_mg_processed', '1');
    }
    
    private function assign_subscription($order) {
        $user_id = $order->get_user_id();
        $order_id = $order->get_id();
        
        // Product ID mapping for subscription plans
        $product_mapping = array(
            '31314' => 'month1',  // 1 month
            '31316' => 'month3',  // 3 months  
            '31317' => 'year1'    // 1 year
        );
        
        // Check order items for subscription products
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            
            if (isset($product_mapping[$product_id])) {
                $plan = $product_mapping[$product_id];
                
                // Add subscription using existing helper function
                $subscription_id = mg_add_user_subscription($user_id, $plan, $order_id);
                
                if ($subscription_id) {
                    // Add order note
                    $order->add_order_note(sprintf(
                        'اشتراک %s برای کاربر فعال شد (ID: %d)',
                        $this->get_plan_name($plan),
                        $subscription_id
                    ));
                    
                    break; // Only process first matching product
                }
            }
        }
    }
    
    private function get_plan_name($plan) {
        $names = array(
            'month1' => 'یک ماهه',
            'month3' => 'سه ماهه', 
            'year1' => 'یک ساله'
        );
        
        return isset($names[$plan]) ? $names[$plan] : $plan;
    }
}
