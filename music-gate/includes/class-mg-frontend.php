<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MG_Frontend {
    
    public function __construct() {
        add_action('wp_footer', array($this, 'add_content_overlay'));
        add_action('wp_footer', array($this, 'add_user_dropdown'));
        add_shortcode('musicgate_register', array($this, 'register_shortcode'));
        add_shortcode('musicgate_user_dropdown', array($this, 'user_dropdown_shortcode'));
        add_shortcode('musicgate_buy_button', array($this, 'buy_button_shortcode'));
        
        add_action('wp_ajax_mg_register_user', array($this, 'handle_register'));
        add_action('wp_ajax_nopriv_mg_register_user', array($this, 'handle_register'));
        add_action('wp_ajax_mg_login_user', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_mg_login_user', array($this, 'handle_login'));
        add_action('wp_ajax_mg_create_payment', array($this, 'handle_purchase'));
        add_action('wp_ajax_nopriv_mg_create_payment', array($this, 'handle_purchase'));
    }
    
    public function add_content_overlay() {
        if (!get_option('music_gate_enabled')) {
            return;
        }
        
        if (is_admin() || !is_singular()) {
            return;
        }
        
        if (is_page('checkout') || is_page('پرداخت')) {
            return;
        }
        
        if (mg_user_has_active_subscription()) {
            return;
        }
        
        $percentage = get_option('music_gate_restriction_percentage', 60);
        $overlay_text = get_option('music_gate_overlay_text', 'برای دسترسی به محتوای کامل، نیاز به اشتراک دارید.');
        
        ?>
        <div id="mg-content-overlay" style="display: none;">
            <div class="mg-overlay-content">
                <div class="mg-overlay-icon">🔒</div>
                <h3>محتوای محدود شده</h3>
                <p><?php echo esc_html($overlay_text); ?></p>
                <button class="mg-btn mg-btn-primary mg-show-subscription-popup">
                    تهیه اشتراک
                </button>
            </div>
        </div>
        
        <script>
        (function() {
            function updateLockPosition() {
                var overlay = document.getElementById('mg-content-overlay');
                if (!overlay) return;
                
                var totalHeight = Math.max(
                    document.body.scrollHeight,
                    document.body.offsetHeight,
                    document.documentElement.scrollHeight,
                    document.documentElement.offsetHeight
                );
                
                var freeHeight = Math.round(totalHeight * 0.4);
                var lockedHeight = totalHeight - freeHeight;
                
                overlay.style.position = 'absolute';
                overlay.style.top = freeHeight + 'px';
                overlay.style.left = '0';
                overlay.style.right = '0';
                overlay.style.width = '100%';
                overlay.style.height = lockedHeight + 'px';
                overlay.style.display = 'block';
                overlay.style.zIndex = '999999';
                overlay.style.background = 'linear-gradient(to top, rgba(20, 24, 42, 0.98) 0%, rgba(20, 24, 42, 0.95) 50%, rgba(20, 24, 42, 0.85) 100%)';
                overlay.style.backdropFilter = 'blur(10px)';
                
                var maxScroll = Math.max(0, freeHeight - window.innerHeight);
                
                function restrictScroll() {
                    if (window.pageYOffset > maxScroll) {
                        window.scrollTo(0, maxScroll);
                    }
                }
                
                window.removeEventListener('scroll', window.mgScrollRestriction);
                window.mgScrollRestriction = restrictScroll;
                window.addEventListener('scroll', restrictScroll, { passive: false });
                
                console.log('[v0] Lock position updated - Total:', totalHeight, 'Free (40%):', freeHeight, 'Locked (60%):', lockedHeight);
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(updateLockPosition, 500);
                setTimeout(updateLockPosition, 2000);
            });
            
            window.addEventListener('load', updateLockPosition);
            window.addEventListener('resize', updateLockPosition);
            
            if (window.MutationObserver) {
                var observer = new MutationObserver(function() {
                    setTimeout(updateLockPosition, 500);
                });
                
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
        })();
        </script>
        
        <script>
        window.musicGate = {
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('mg_nonce'); ?>',
            isLoggedIn: <?php echo is_user_logged_in() ? 'true' : 'false'; ?>,
            hasSubscription: <?php echo mg_user_has_active_subscription() ? 'true' : 'false'; ?>,
            restrictionEnabled: true,
            restrictionPercentage: <?php echo intval($percentage); ?>
        };
        </script>
        <?php
    }
    
    public function add_user_dropdown() {
        ?>
        <div id="mg-user-dropdown" style="display: none;">
            <?php if (is_user_logged_in()): ?>
                <?php $user = wp_get_current_user(); ?>
                <?php $subscription = mg_get_user_subscription(); ?>
                <?php $remaining_days = mg_get_remaining_days(); ?>
                
                <div class="mg-dropdown-header">
                    <span class="mg-user-name"><?php echo esc_html($user->display_name); ?></span>
                    <?php if ($subscription): ?>
                        <div class="mg-subscription-circle">
                            <svg class="mg-circle-progress" viewBox="0 0 36 36">
                                <path class="mg-circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <path class="mg-circle-progress-bar" stroke-dasharray="<?php echo ($remaining_days / 365) * 100; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            </svg>
                            <div class="mg-circle-text"><?php echo mg_persian_numbers($remaining_days); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="mg-dropdown-menu">
                    <?php if ($subscription): ?>
                        <a href="#" onclick="window.mgOpenProfilePopup(); return false;">پروفایل من</a>
                        <a href="#" onclick="window.mgOpenSubscriptionHistory(); return false;">تاریخچه اشتراک</a>
                    <?php else: ?>
                        <div class="mg-no-subscription">شما اشتراک فعالی ندارید</div>
                        <a href="#" class="mg-get-subscription mg-show-subscription-popup">تهیه اشتراک</a>
                    <?php endif; ?>
                    <a href="<?php echo wp_logout_url(); ?>">خروج</a>
                </div>
            <?php else: ?>
                <div class="mg-dropdown-menu">
                    <a href="#" onclick="window.mgOpenLoginPopup(); return false;">ورود/ثبت نام</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="mg-popup-overlay" class="mg-popup-overlay" style="display: none;">
            <div class="mg-popup-content">
                <span class="mg-popup-close" onclick="window.mgClosePopup(); return false;">&times;</span>
                <div id="mg-popup-body"></div>
            </div>
        </div>
        
        <div id="mg-subscription-popup-template" style="display: none;">
            <div class="mg-subscription-popup">
                <h2>انتخاب پلن اشتراک</h2>
                <div class="mg-plans-container">
                    <?php
                    $monthly_price = 43000;
                    $plans = array(
                        'month1' => array(
                            'name' => get_option('music_gate_plan_month1_name', 'اشتراک یک ماهه'),
                            'price' => $monthly_price,
                            'original_price' => $monthly_price,
                            'discount' => 0,
                            'image' => get_option('music_gate_plan_month1_image', ''),
                            'enabled' => get_option('music_gate_plan_month1_enabled', '1'),
                            'popular' => false
                        ),
                        'month3' => array(
                            'name' => get_option('music_gate_plan_month3_name', 'اشتراک سه ماهه'),
                            'price' => 98000,
                            'original_price' => $monthly_price * 3,
                            'discount' => round((($monthly_price * 3 - 98000) / ($monthly_price * 3)) * 100),
                            'image' => get_option('music_gate_plan_month3_image', ''),
                            'enabled' => get_option('music_gate_plan_month3_enabled', '1'),
                            'popular' => false
                        ),
                        'year1' => array(
                            'name' => get_option('music_gate_plan_year1_name', 'اشتراک سالانه'),
                            'price' => 198000,
                            'original_price' => $monthly_price * 12,
                            'discount' => round((($monthly_price * 12 - 198000) / ($monthly_price * 12)) * 100),
                            'image' => get_option('music_gate_plan_year1_image', ''),
                            'enabled' => get_option('music_gate_plan_year1_enabled', '1'),
                            'popular' => true
                        )
                    );
                    
                    foreach ($plans as $plan_key => $plan): 
                        if ($plan['enabled'] != '1') continue;
                    ?>
                        <div class="mg-plan-card <?php echo $plan['popular'] ? 'mg-plan-popular' : ''; ?>" data-plan="<?php echo esc_attr($plan_key); ?>">
                            <?php if ($plan['popular']): ?>
                                <div class="mg-plan-badge">محبوب</div>
                            <?php endif; ?>
                            
                            <?php if ($plan['image']): ?>
                                <img src="<?php echo esc_url($plan['image']); ?>" alt="<?php echo esc_attr($plan['name']); ?>" class="mg-plan-image">
                            <?php endif; ?>
                            
                            <h3><?php echo esc_html($plan['name']); ?></h3>
                            
                            <div class="mg-plan-pricing">
                                <div class="mg-plan-price"><?php echo mg_format_price($plan['price']); ?></div>
                                <?php if ($plan['discount'] > 0): ?>
                                    <div class="mg-plan-original-price"><?php echo mg_format_price($plan['original_price']); ?></div>
                                    <div class="mg-plan-discount"><?php echo mg_persian_numbers($plan['discount']); ?>% تخفیف</div>
                                <?php endif; ?>
                            </div>
                            
                            <button class="mg-btn mg-btn-primary mg-purchase-plan mg-select-plan" data-plan="<?php echo esc_attr($plan_key); ?>">
                                انتخاب پلن
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function register_shortcode($atts) {
        if (is_user_logged_in()) {
            return '<p>شما قبلاً وارد شده‌اید.</p>';
        }
        
        ob_start();
        ?>
        <div class="mg-register-form">
            <h3>ثبت نام در دروازه موسیقی</h3>
            <form id="mg-register-form">
                <div class="mg-form-group">
                    <input type="text" name="first_name" placeholder="نام" required>
                </div>
                <div class="mg-form-group">
                    <input type="text" name="last_name" placeholder="نام خانوادگی" required>
                </div>
                <div class="mg-form-group">
                    <input type="tel" name="phone" placeholder="شماره تلفن" required>
                </div>
                <div class="mg-form-group">
                    <input type="password" name="password" placeholder="رمز عبور" required>
                </div>
                <button type="submit" class="mg-btn mg-btn-primary">ثبت نام</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function user_dropdown_shortcode($atts) {
        return '<div class="mg-user-dropdown-trigger" onclick="mgToggleDropdown()">حساب کاربری</div>';
    }
    
    public function buy_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'plan' => 'month1'
        ), $atts);
        
        $plan_prices = array(
            'month1' => 43000,
            'month3' => 98000,
            'year1' => 198000
        );
        
        $plan_names = array(
            'month1' => get_option('music_gate_plan_month1_name', 'اشتراک یک ماهه'),
            'month3' => get_option('music_gate_plan_month3_name', 'اشتراک سه ماهه'),
            'year1' => get_option('music_gate_plan_year1_name', 'اشتراک سالانه')
        );
        
        $price = $plan_prices[$atts['plan']] ?? 0;
        $name = $plan_names[$atts['plan']] ?? 'اشتراک';
        
        return sprintf(
            '<button class="mg-btn mg-btn-primary" onclick="window.mgPurchasePlan(\'%s\'); return false;">%s - %s</button>',
            esc_attr($atts['plan']),
            esc_html($name),
            mg_format_price($price)
        );
    }
    
    public function handle_register() {
        check_ajax_referer('mg_nonce', 'nonce');
        
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $phone = sanitize_text_field($_POST['phone']);
        $password = $_POST['password'];
        
        if (empty($first_name) || empty($last_name) || empty($phone) || empty($password)) {
            wp_send_json_error('تمام فیلدها الزامی هستند.');
        }
        
        // Check if user already exists
        if (username_exists($phone)) {
            wp_send_json_error('این شماره تلفن قبلاً ثبت شده است.');
        }
        
        // Create user
        $user_id = wp_create_user($phone, $password, $phone . '@musicgate.local');
        
        if (is_wp_error($user_id)) {
            wp_send_json_error('خطا در ایجاد حساب کاربری.');
        }
        
        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        ));
        
        // Login user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        wp_send_json_success('ثبت نام با موفقیت انجام شد.');
    }
    
    public function handle_login() {
        check_ajax_referer('mg_nonce', 'nonce');
        
        $phone = sanitize_text_field($_POST['phone']);
        $password = $_POST['password'];
        
        $user = wp_authenticate($phone, $password);
        
        if (is_wp_error($user)) {
            wp_send_json_error('نام کاربری یا رمز عبور اشتباه است.');
        }
        
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        
        wp_send_json_success('ورود با موفقیت انجام شد.');
    }
    
    public function handle_purchase() {
        check_ajax_referer('mg_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            // Check if guest info is provided
            if (isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['phone'])) {
                $first_name = sanitize_text_field($_POST['first_name']);
                $last_name = sanitize_text_field($_POST['last_name']);
                $phone = sanitize_text_field($_POST['phone']);
                
                if (empty($first_name) || empty($last_name) || empty($phone)) {
                    wp_send_json_error('تمام فیلدها الزامی هستند.');
                }
                
                // Check if user exists, if not create one
                if (!username_exists($phone)) {
                    $random_password = wp_generate_password(12, false);
                    $user_id = wp_create_user($phone, $random_password, $phone . '@musicgate.local');
                    
                    if (is_wp_error($user_id)) {
                        wp_send_json_error('خطا در ایجاد حساب کاربری.');
                    }
                    
                    // Update user meta
                    wp_update_user(array(
                        'ID' => $user_id,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'display_name' => $first_name . ' ' . $last_name
                    ));
                } else {
                    $user = get_user_by('login', $phone);
                    $user_id = $user->ID;
                }
                
                // Login the user
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
            } else {
                wp_send_json_error('اطلاعات کاربری الزامی است.');
            }
        } else {
            $user_id = get_current_user_id();
        }
        
        $plan = sanitize_text_field($_POST['plan']);
        
        // Create order and redirect to Zarinpal
        $payments = new MG_Payments();
        $result = $payments->create_payment($user_id, $plan);
        
        if ($result['success']) {
            wp_send_json_success(array('redirect_url' => $result['redirect_url']));
        } else {
            wp_send_json_error($result['message']);
        }
    }
}
