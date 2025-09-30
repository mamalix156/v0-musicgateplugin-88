<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MG_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'دروازه موسیقی',
            'دروازه موسیقی',
            'manage_options',
            'music-gate',
            array($this, 'admin_page'),
            'dashicons-unlock',
            30
        );
        
        add_submenu_page(
            'music-gate',
            'تنظیمات',
            'تنظیمات',
            'manage_options',
            'music-gate',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'music-gate',
            'اشتراک‌ها',
            'اشتراک‌ها',
            'manage_options',
            'music-gate-subscriptions',
            array($this, 'subscriptions_page')
        );
        
        add_submenu_page(
            'music-gate',
            'سفارشات',
            'سفارشات',
            'manage_options',
            'music-gate-orders',
            array($this, 'orders_page')
        );
    }
    
    public function register_settings() {
        register_setting('music_gate_settings', 'music_gate_enabled');
        register_setting('music_gate_settings', 'music_gate_restriction_percentage');
        register_setting('music_gate_settings', 'music_gate_overlay_text');
        register_setting('music_gate_settings', 'music_gate_overlay_image');
        register_setting('music_gate_settings', 'music_gate_overlay_link');
        register_setting('music_gate_settings', 'music_gate_plan_month1_price');
        register_setting('music_gate_settings', 'music_gate_plan_month1_enabled');
        register_setting('music_gate_settings', 'music_gate_plan_month1_image');
        register_setting('music_gate_settings', 'music_gate_plan_month3_price');
        register_setting('music_gate_settings', 'music_gate_plan_month3_enabled');
        register_setting('music_gate_settings', 'music_gate_plan_month3_image');
        register_setting('music_gate_settings', 'music_gate_plan_year1_price');
        register_setting('music_gate_settings', 'music_gate_plan_year1_enabled');
        register_setting('music_gate_settings', 'music_gate_plan_year1_image');
        register_setting('music_gate_settings', 'music_gate_zarinpal_merchant');
    }
    
    public function admin_page() {
        include MUSIC_GATE_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    public function subscriptions_page() {
        include MUSIC_GATE_PLUGIN_DIR . 'admin/views/subscriptions.php';
    }
    
    public function orders_page() {
        include MUSIC_GATE_PLUGIN_DIR . 'admin/views/orders.php';
    }
}
