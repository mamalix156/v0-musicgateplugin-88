<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MGM_Shortcodes {
    
    public function __construct() {
        add_shortcode('mg_subscription_buttons', array($this, 'subscription_buttons_shortcode'));
        add_shortcode('mg_user_dashboard', array($this, 'user_dashboard_shortcode'));
    }
    
    public function subscription_buttons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'default'
        ), $atts);
        
        $product_ids = array(
            '1month' => get_option('mgm_product_1month', '31314'),
            '6months' => get_option('mgm_product_6months', '31316'),
            '1year' => get_option('mgm_product_1year', '31317')
        );
        
        ob_start();
        ?>
        <div class="mgm-subscription-buttons">
            <?php foreach ($product_ids as $plan => $product_id): ?>
                <?php
                $product = wc_get_product($product_id);
                if (!$product) continue;
                
                $price = $product->get_price();
                $plan_name = mgm_get_plan_name($plan);
                $checkout_url = wc_get_checkout_url() . '?add-to-cart=' . $product_id;
                
                // Calculate discount for 6months and 1year plans
                $discount = '';
                if ($plan == '6months') {
                    $monthly_price = mgm_get_plan_price('1month');
                    if ($monthly_price > 0) {
                        $monthly_total = $monthly_price * 6;
                        $discount_percent = round((($monthly_total - $price) / $monthly_total) * 100);
                        $discount = $discount_percent . '% تخفیف';
                    }
                } elseif ($plan == '1year') {
                    $monthly_price = mgm_get_plan_price('1month');
                    if ($monthly_price > 0) {
                        $monthly_total = $monthly_price * 12;
                        $discount_percent = round((($monthly_total - $price) / $monthly_total) * 100);
                        $discount = $discount_percent . '% تخفیف';
                    }
                }
                ?>
                <div class="mgm-subscription-card <?php echo $plan == '1year' ? 'mgm-popular' : ''; ?>">
                    <?php if ($plan == '1year'): ?>
                        <div class="mgm-popular-badge">محبوب</div>
                    <?php endif; ?>
                    
                    <h3><?php echo esc_html($plan_name); ?></h3>
                    
                    <div class="mgm-price">
                        <?php echo wc_price($price); ?>
                    </div>
                    
                    <?php if ($discount): ?>
                        <div class="mgm-discount"><?php echo esc_html($discount); ?></div>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url($checkout_url); ?>" class="mgm-subscribe-btn">
                        انتخاب پلن
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function user_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>برای مشاهده داشبورد، وارد حساب کاربری خود شوید.</p>';
        }
        
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $subscription = mgm_get_user_subscription($user_id);
        $remaining_days = mgm_get_remaining_days($user_id);
        
        ob_start();
        ?>
        <div class="mgm-user-dashboard">
            <div class="mgm-dashboard-header">
                <h3>داشبورد کاربری</h3>
            </div>
            
            <div class="mgm-dashboard-content">
                <div class="mgm-user-info">
                    <h4>اطلاعات کاربری</h4>
                    <p><strong>نام:</strong> <?php echo esc_html($user->display_name); ?></p>
                    <p><strong>ایمیل:</strong> <?php echo esc_html($user->user_email); ?></p>
                    
                    <?php if ($subscription): ?>
                        <div class="mgm-subscription-info">
                            <h4>اشتراک فعال</h4>
                            <p><strong>پلن:</strong> <?php echo esc_html(mgm_get_plan_name($subscription->plan)); ?></p>
                            <p><strong>روزهای باقی‌مانده:</strong> 
                                <span class="mgm-remaining-days"><?php echo esc_html($remaining_days); ?> روز</span>
                            </p>
                            <p><strong>تاریخ پایان:</strong> <?php echo esc_html(date_i18n('Y/m/d', strtotime($subscription->end_date))); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="mgm-no-subscription">
                            <p>شما اشتراک فعالی ندارید.</p>
                            <a href="<?php echo esc_url(get_option('mgm_overlay_link', home_url('/subscription'))); ?>" class="mgm-subscribe-btn">
                                تهیه اشتراک
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="mgm-profile-edit">
                    <h4>ویرایش پروفایل</h4>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="mgm_update_profile">
                        <?php wp_nonce_field('mgm_update_profile', 'mgm_nonce'); ?>
                        
                        <p>
                            <label>نام نمایشی:</label>
                            <input type="text" name="display_name" value="<?php echo esc_attr($user->display_name); ?>" />
                        </p>
                        
                        <p>
                            <label>رمز عبور جدید:</label>
                            <input type="password" name="new_password" placeholder="در صورت تمایل به تغییر وارد کنید" />
                        </p>
                        
                        <p>
                            <input type="submit" value="بروزرسانی" class="mgm-update-btn" />
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Handle profile update
add_action('admin_post_mgm_update_profile', 'mgm_handle_profile_update');
add_action('admin_post_nopriv_mgm_update_profile', 'mgm_handle_profile_update');

function mgm_handle_profile_update() {
    if (!wp_verify_nonce($_POST['mgm_nonce'], 'mgm_update_profile')) {
        wp_die('Security check failed');
    }
    
    if (!is_user_logged_in()) {
        wp_die('You must be logged in');
    }
    
    $user_id = get_current_user_id();
    $display_name = sanitize_text_field($_POST['display_name']);
    $new_password = $_POST['new_password'];
    
    // Update display name
    if ($display_name) {
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $display_name
        ));
    }
    
    // Update password if provided
    if ($new_password) {
        wp_set_password($new_password, $user_id);
    }
    
    wp_redirect(wp_get_referer());
    exit;
}
?>
