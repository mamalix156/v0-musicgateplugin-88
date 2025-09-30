<?php
/**
 * Plugin Name: دروازه موسیقی
 * Plugin URI: https://example.com/music-gate
 * Description: سیستم اشتراک پریمیوم با پوشش محدودیت محتوا و یکپارچگی پرداخت زرین‌پال
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: music-gate
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MUSIC_GATE_VERSION', '1.0.0');
define('MUSIC_GATE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MUSIC_GATE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MUSIC_GATE_TEXT_DOMAIN', 'music-gate');

// Main plugin class
class MusicGate {
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain(MUSIC_GATE_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_components();
        
        // Add hooks
        $this->add_hooks();
    }
    
    private function includes() {
        $required_files = array(
            'includes/helpers.php',
            'includes/class-mg-admin.php',
            'includes/class-mg-frontend.php',
            'includes/class-mg-payments.php',
            'includes/class-mg-rest.php',
            'includes/class-mg-subscriptions.php'
        );
        
        if (class_exists('WooCommerce')) {
            $required_files[] = 'includes/class-mg-woocommerce.php';
        }
        
        foreach ($required_files as $file) {
            $file_path = MUSIC_GATE_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log('Music Gate: Missing file - ' . $file);
            }
        }
    }
    
    private function init_components() {
        if (class_exists('MG_Admin')) {
            new MG_Admin();
        }
        if (class_exists('MG_Frontend')) {
            new MG_Frontend();
        }
        if (class_exists('MG_Payments')) {
            new MG_Payments();
        }
        if (class_exists('MG_REST')) {
            new MG_REST();
        }
        if (class_exists('MG_Subscriptions')) {
            new MG_Subscriptions();
        }
        if (class_exists('MG_WooCommerce_Integration')) {
            new MG_WooCommerce_Integration();
        }
    }
    
    private function add_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    public function enqueue_public_assets() {
        wp_enqueue_style('music-gate-style', MUSIC_GATE_PLUGIN_URL . 'music-gate/public/assets/style.css', array(), MUSIC_GATE_VERSION);
        wp_enqueue_script('music-gate-script', MUSIC_GATE_PLUGIN_URL . 'music-gate/public/assets/script.js', array('jquery'), MUSIC_GATE_VERSION, true);
        
        // Localize script
        $user_has_subscription = is_user_logged_in() && mg_user_has_subscription();
        
        wp_localize_script('music-gate-script', 'musicGate', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('music_gate_nonce'),
            'restUrl' => rest_url('musicgate/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'isLoggedIn' => is_user_logged_in(),
            'hasSubscription' => $user_has_subscription,
            'restrictionEnabled' => !$user_has_subscription && get_option('music_gate_enabled', '1'),
            'restrictionPercentage' => get_option('mg_lock_percentage', '60')
        ));
        
        // Localize mg_vars for new overlay system
        wp_localize_script('music-gate-script', 'mg_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('music_gate_nonce'),
            'lock_percentage' => get_option('mg_lock_percentage', '60')
        ));
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'music-gate') !== false) {
            wp_enqueue_style('music-gate-admin', MUSIC_GATE_PLUGIN_URL . 'admin/assets/admin.css', array(), MUSIC_GATE_VERSION);
            wp_enqueue_script('music-gate-admin', MUSIC_GATE_PLUGIN_URL . 'admin/assets/admin.js', array('jquery'), MUSIC_GATE_VERSION, true);
        }
    }
    
    public function activate() {
        $this->create_tables();
        $this->set_default_options();
        
        // Schedule cron job
        if (!wp_next_scheduled('music_gate_daily_check')) {
            wp_schedule_event(time(), 'daily', 'music_gate_daily_check');
        }
        
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('music_gate_daily_check');
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Subscriptions table
        $subscriptions_table = $wpdb->prefix . 'musicgate_subscriptions';
        $subscriptions_sql = "CREATE TABLE $subscriptions_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            plan varchar(20) NOT NULL,
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            status varchar(20) DEFAULT 'active',
            order_id int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Orders table
        $orders_table = $wpdb->prefix . 'musicgate_orders';
        $orders_sql = "CREATE TABLE $orders_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            order_key varchar(50) NOT NULL UNIQUE,
            user_id bigint(20) NOT NULL,
            plan varchar(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            zarinpal_ref_id varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY order_key (order_key),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($subscriptions_sql);
        dbDelta($orders_sql);
    }
    
    private function set_default_options() {
        $default_options = array(
            'music_gate_enabled' => '1',
            'music_gate_restriction_percentage' => '60',
            'mg_lock_percentage' => '60',
            'music_gate_overlay_text' => 'برای دسترسی به محتوای کامل، نیاز به اشتراک دارید.',
            'music_gate_overlay_image' => '',
            'music_gate_overlay_link' => '',
            'music_gate_plan_month1_price' => '50000',
            'music_gate_plan_month1_enabled' => '1',
            'music_gate_plan_month3_price' => '120000',
            'music_gate_plan_month3_enabled' => '1',
            'music_gate_plan_year1_price' => '400000',
            'music_gate_plan_year1_enabled' => '1',
            'music_gate_zarinpal_merchant' => 'c5196eca-e170-48f0-a0e6-b38531087bfa'
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
}

// Initialize the plugin
new MusicGate();
