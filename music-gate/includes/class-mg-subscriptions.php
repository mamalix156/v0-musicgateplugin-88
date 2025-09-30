<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MG_Subscriptions {
    
    public function __construct() {
        add_action('music_gate_daily_check', array($this, 'check_expired_subscriptions'));
        add_action('wp_footer', array($this, 'add_success_message'));
    }
    
    public function check_expired_subscriptions() {
        global $wpdb;
        $subscriptions_table = $wpdb->prefix . 'musicgate_subscriptions';
        
        // Update expired subscriptions
        $wpdb->query(
            "UPDATE $subscriptions_table SET status = 'expired' WHERE status = 'active' AND end_date < NOW()"
        );
    }
    
    public function add_success_message() {
        if (isset($_GET['mg_success']) && $_GET['mg_success'] == '1') {
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                mgShowWelcomeMessage();
            });
            
            function mgShowWelcomeMessage() {
                const overlay = document.createElement('div');
                overlay.className = 'mg-popup-overlay';
                overlay.style.display = 'flex';
                
                const content = document.createElement('div');
                content.className = 'mg-popup-content mg-welcome-popup';
                content.innerHTML = `
                    <div class="mg-welcome-content">
                        <div class="mg-welcome-icon">🎵</div>
                        <h2>به خانواده بزرگ ملودیک خوشآمدید</h2>
                        <p>لذت دسترسی به دنیای نامحدود موزیک‌ها</p>
                        <button class="mg-btn mg-btn-primary" onclick="this.closest('.mg-popup-overlay').remove()">
                            شروع کنید
                        </button>
                    </div>
                `;
                
                overlay.appendChild(content);
                document.body.appendChild(overlay);
                
                // Auto close after 5 seconds
                setTimeout(() => {
                    if (overlay.parentNode) {
                        overlay.remove();
                    }
                }, 5000);
            }
            </script>
            <?php
        }
        
        if (isset($_GET['mg_error'])) {
            $error_messages = array(
                'verify_failed' => 'تأیید پرداخت با خطا مواجه شد.',
                'cancelled' => 'پرداخت لغو شد.'
            );
            
            $error = sanitize_text_field($_GET['mg_error']);
            $message = $error_messages[$error] ?? 'خطای نامشخص';
            
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                alert('<?php echo esc_js($message); ?>');
            });
            </script>
            <?php
        }
    }
}
