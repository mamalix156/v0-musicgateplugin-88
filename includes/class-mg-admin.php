<?php
/**
 * Admin functionality for Music Gate plugin
 */

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
            __('Music Gate', MUSIC_GATE_TEXT_DOMAIN),
            __('Music Gate', MUSIC_GATE_TEXT_DOMAIN),
            'manage_options',
            'music-gate',
            array($this, 'settings_page'),
            'dashicons-lock',
            30
        );
        
        add_submenu_page(
            'music-gate',
            __('Settings', MUSIC_GATE_TEXT_DOMAIN),
            __('Settings', MUSIC_GATE_TEXT_DOMAIN),
            'manage_options',
            'music-gate',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'music-gate',
            __('Subscriptions', MUSIC_GATE_TEXT_DOMAIN),
            __('Subscriptions', MUSIC_GATE_TEXT_DOMAIN),
            'manage_options',
            'music-gate-subscriptions',
            array($this, 'subscriptions_page')
        );
        
        add_submenu_page(
            'music-gate',
            __('Orders', MUSIC_GATE_TEXT_DOMAIN),
            __('Orders', MUSIC_GATE_TEXT_DOMAIN),
            'manage_options',
            'music-gate-orders',
            array($this, 'orders_page')
        );
    }
    
    public function register_settings() {
        // General settings
        register_setting('music_gate_settings', 'music_gate_enabled');
        register_setting('music_gate_settings', 'music_gate_restriction_percentage');
        register_setting('music_gate_settings', 'mg_lock_percentage'); // Added mg_lock_percentage setting for new overlay system
        register_setting('music_gate_settings', 'music_gate_overlay_text');
        register_setting('music_gate_settings', 'music_gate_overlay_image');
        register_setting('music_gate_settings', 'music_gate_overlay_link');
        
        // Plan settings
        register_setting('music_gate_settings', 'music_gate_plan_month1_price');
        register_setting('music_gate_settings', 'music_gate_plan_month1_enabled');
        register_setting('music_gate_settings', 'music_gate_plan_month3_price');
        register_setting('music_gate_settings', 'music_gate_plan_month3_enabled');
        register_setting('music_gate_settings', 'music_gate_plan_year1_price');
        register_setting('music_gate_settings', 'music_gate_plan_year1_enabled');
        
        // Payment settings
        register_setting('music_gate_settings', 'music_gate_zarinpal_merchant');
    }
    
    public function settings_page() {
        include MUSIC_GATE_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    public function subscriptions_page() {
        include MUSIC_GATE_PLUGIN_DIR . 'admin/views/subscriptions.php';
    }
    
    public function orders_page() {
        include MUSIC_GATE_PLUGIN_DIR . 'admin/views/orders.php';
    }
}
