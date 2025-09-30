<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MGM_Frontend {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'add_content_overlay'));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('mgm-style', MGM_PLUGIN_URL . 'assets/style.css', array(), MGM_VERSION);
        wp_enqueue_script('mgm-script', MGM_PLUGIN_URL . 'assets/script.js', array('jquery'), MGM_VERSION, true);
        
        // Localize script
        wp_localize_script('mgm-script', 'mgm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mgm_nonce'),
            'restriction_enabled' => get_option('mgm_enable_restriction', '1'),
            'restriction_percentage' => get_option('mgm_restriction_percentage', '60'),
            'has_subscription' => mgm_user_has_active_subscription() ? '1' : '0'
        ));
    }
    
    public function add_content_overlay() {
        // Only add overlay if restriction is enabled and user doesn't have subscription
        if (get_option('mgm_enable_restriction') != '1' || mgm_user_has_active_subscription()) {
            return;
        }
        
        $message = get_option('mgm_overlay_message', 'برای دسترسی به محتوای کامل، اشتراک پریمیوم تهیه کنید.');
        $image = get_option('mgm_overlay_image', '');
        $link = get_option('mgm_overlay_link', home_url('/subscription'));
        ?>
        <div id="mgm-content-overlay" style="display: none;">
            <div class="mgm-overlay-content">
                <p><?php echo esc_html($message); ?></p>
                <?php if ($image): ?>
                    <a href="<?php echo esc_url($link); ?>" class="mgm-overlay-link">
                        <img src="<?php echo esc_url($image); ?>" alt="اشتراک پریمیوم" />
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
?>
