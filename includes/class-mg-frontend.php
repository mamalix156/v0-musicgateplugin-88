<?php
/**
 * Frontend functionality for Music Gate plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class MG_Frontend {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Content restriction
        add_filter('the_content', array($this, 'restrict_content'));
        
        // Shortcodes
        add_shortcode('musicgate_register', array($this, 'registration_form_shortcode'));
        add_shortcode('musicgate_buy_button', array($this, 'buy_button_shortcode'));
        add_shortcode('musicgate_user_dropdown', array($this, 'user_dropdown_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_mg_register_user', array($this, 'handle_registration'));
        add_action('wp_ajax_nopriv_mg_register_user', array($this, 'handle_registration'));
        add_action('wp_ajax_mg_update_profile', array($this, 'handle_profile_update'));
        add_action('wp_ajax_mg_login_user', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_mg_login_user', array($this, 'handle_login'));
        add_action('wp_ajax_mg_purchase_plan', array($this, 'handle_purchase_plan'));
        add_action('wp_ajax_nopriv_mg_purchase_plan', array($this, 'handle_purchase_plan'));
        
        // Custom login redirect
        add_filter('login_redirect', array($this, 'login_redirect'), 10, 3);
        
        // Payment success message
        add_action('wp_footer', array($this, 'show_payment_messages'));
        
        add_action('wp_footer', array($this, 'add_registration_popup'));
        add_action('wp_footer', array($this, 'add_subscription_popup'));
    }
    
    public function restrict_content($content) {
        
        // اگر پلاگین غیرفعال است
        if (!get_option('music_gate_enabled')) {
            return $content;
        }
        
        // در صفحه اصلی محدودیت نداریم
        if (is_front_page() || is_home()) {
            return $content;
        }
        
        // در صفحه checkout محدودیت نداریم
        if (is_page('checkout') || strpos($_SERVER['REQUEST_URI'], '/checkout') !== false) {
            return $content;
        }
        
        // اگر کاربر اشتراک دارد
        if (mg_user_has_subscription()) {
            return $content;
        }
        
        // برای ادمین محدودیت نداریم
        if (is_admin() || current_user_can('manage_options')) {
            return $content;
        }
        
        if (!is_single() && !is_singular('post')) {
            return $content;
        }
        
        // اعمال محدودیت
        $percentage = get_option('music_gate_restriction_percentage', 60);
        $overlay_text = get_option('music_gate_overlay_text', 'برای دسترسی به محتوای کامل، نیاز به اشتراک دارید.');
        
        return '<div class="mg-content-wrapper" data-restriction="' . esc_attr($percentage) . '">' . $content . '</div>';
    }
    
    public function registration_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect' => ''
        ), $atts);
        
        if (is_user_logged_in()) {
            return '<p>شما قبلاً وارد شده‌اید.</p>';
        }
        
        ob_start();
        ?>
        <div class="mg-registration-form">
            <form id="mg-register-form" class="mg-form">
                <div class="mg-form-group">
                    <label for="mg-first-name">نام</label>
                    <input type="text" id="mg-first-name" name="first_name" required>
                </div>
                
                <div class="mg-form-group">
                    <label for="mg-last-name">نام خانوادگی</label>
                    <input type="text" id="mg-last-name" name="last_name" required>
                </div>
                
                <div class="mg-form-group">
                    <label for="mg-phone">شماره تلفن</label>
                    <input type="tel" id="mg-phone" name="phone" required>
                </div>
                
                <div class="mg-form-group">
                    <label for="mg-password">رمز عبور</label>
                    <input type="password" id="mg-password" name="password" required>
                </div>
                
                <button type="submit" class="mg-btn mg-btn-primary">
                    ثبت نام
                </button>
                
                <div class="mg-form-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function buy_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'plan' => 'month1'
        ), $atts);
        
        $plan = sanitize_text_field($atts['plan']);
        $plan_details = mg_get_plan_details($plan);
        
        if (!$plan_details) {
            return '';
        }
        
        $price = get_option('music_gate_plan_' . $plan . '_price', 0);
        $enabled = get_option('music_gate_plan_' . $plan . '_enabled', 0);
        
        if (!$enabled) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="mg-buy-button">
            <button class="mg-btn mg-btn-primary mg-btn-large mg-show-subscription-popup" data-plan="<?php echo esc_attr($plan); ?>">
                خرید <?php echo $plan_details['name']; ?> - <?php echo mg_format_price($price); ?> تومان
            </button>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function user_dropdown_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<button class="mg-btn mg-btn-minimal mg-show-register-popup">ورود/ثبت نام</button>';
        }
        
        $user = wp_get_current_user();
        $user_id = get_current_user_id();
        $first_name = get_user_meta($user_id, 'first_name', true) ?: '';
        $last_name = get_user_meta($user_id, 'last_name', true) ?: '';
        $name = trim($first_name . ' ' . $last_name) ?: $user->display_name;
        
        $sub_end = get_user_meta($user_id, 'mg_sub_end', true);
        $now = current_time('timestamp');
        $remaining_days = 0;
        
        if ($sub_end) {
            $end_ts = strtotime($sub_end);
            if ($end_ts > $now) {
                $remaining_days = ceil(($end_ts - $now) / DAY_IN_SECONDS);
            }
        }
        
        ob_start();
        ?>
        <div class="mg-user-dropdown mg-minimal">
            <button class="mg-dropdown-toggle mg-btn mg-btn-minimal">
                <?php echo esc_html($name); ?>
                <span class="mg-dropdown-arrow">▼</span>
            </button>
            
            <div class="mg-dropdown-menu">
                <?php if ($remaining_days > 0): ?>
                    <div class="mg-dropdown-header">
                        <!-- Added circular progress indicator with remaining days -->
                        <div class="mg-circle-progress" style="--pct:<?php echo esc_attr((int)(($remaining_days / max(1, 30)) * 100)); ?>%">
                            <?php echo intval($remaining_days); ?>
                        </div>
                        <div class="mg-sub-text"><?php echo $remaining_days; ?> روز باقی‌مانده</div>
                    </div>
                    <a href="#" class="mg-dropdown-item mg-manage-sub" data-action="manage-subscription">
                        مدیریت اشتراک
                    </a>
                <?php else: ?>
                    <div class="mg-no-subscription">شما اشتراک فعالی ندارید</div>
                    <!-- Added purchase subscription link that opens modal -->
                    <a href="#" class="mg-dropdown-item mg-purchase-link mg-show-subscription-popup">
                        تهیه اشتراک
                    </a>
                <?php endif; ?>
                <a href="#" class="mg-dropdown-item" data-action="edit-profile">
                    ویرایش پروفایل
                </a>
                <a href="#" class="mg-dropdown-item" data-action="change-password">
                    تغییر رمز عبور
                </a>
                <a href="<?php echo esc_url(wp_logout_url()); ?>" class="mg-dropdown-item">
                    خروج
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function handle_registration() {
        check_ajax_referer('music_gate_nonce', 'nonce');
        
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $phone = mg_sanitize_phone($_POST['phone']);
        $password = $_POST['password'];
        
        // Validate
        if (empty($first_name) || empty($last_name) || empty($phone) || empty($password)) {
            wp_send_json_error('تمام فیلدها الزامی هستند.');
        }
        
        // Check if phone already exists
        $existing_user = get_users(array(
            'meta_key' => 'phone',
            'meta_value' => $phone,
            'number' => 1
        ));
        
        if (!empty($existing_user)) {
            wp_send_json_error('این شماره تلفن قبلاً ثبت شده است.');
        }
        
        // Create user
        $username = 'user_' . $phone;
        $email = $phone . '@musicgate.local'; // Dummy email
        
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }
        
        // Update user meta
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, 'phone', $phone);
        
        // Auto login
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        wp_send_json_success('ثبت نام با موفقیت انجام شد!');
    }
    
    public function handle_profile_update() {
        check_ajax_referer('music_gate_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('لطفاً ابتدا وارد شوید.');
        }
        
        $user_id = get_current_user_id();
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $phone = mg_sanitize_phone($_POST['phone']);
        
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, 'phone', $phone);
        
        wp_send_json_success('پروفایل با موفقیت به‌روزرسانی شد!');
    }
    
    public function login_redirect($redirect_to, $request, $user) {
        if (isset($user->roles) && is_array($user->roles)) {
            if (in_array('administrator', $user->roles)) {
                return admin_url();
            } else {
                return home_url();
            }
        }
        return $redirect_to;
    }
    
    public function show_payment_messages() {
        if (isset($_GET['payment'])) {
            $status = sanitize_text_field($_GET['payment']);
            
            if ($status === 'success') {
                ?>
                <div id="mg-payment-success" class="mg-popup-overlay">
                    <div class="mg-popup-content mg-success-popup">
                        <div class="mg-popup-header">
                            <h3>🎉 خوش آمدید!</h3>
                            <button class="mg-popup-close">&times;</button>
                        </div>
                        <div class="mg-popup-body">
                            <div class="mg-success-message">
                                <h4>به خانواده بزرگ ملودیک خوش‌آمدید!</h4>
                                <p>لذت دسترسی به دنیای نامحدود موزیک‌ها را تجربه کنید.</p>
                                <p>اشتراک شما با موفقیت فعال شد و اکنون می‌توانید از تمام محتوای سایت استفاده کنید.</p>
                            </div>
                            <button class="mg-btn mg-btn-primary" onclick="location.reload()">
                                شروع کنید
                            </button>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
    }
    
    public function add_registration_popup() {
        if (is_user_logged_in()) {
            return;
        }
        ?>
        <div id="mg-register-popup" class="mg-popup-overlay">
            <div class="mg-popup-content">
                <div class="mg-popup-header">
                    <h3>ورود / ثبت نام</h3>
                    <button class="mg-popup-close">&times;</button>
                </div>
                
                <div class="mg-popup-body">
                    <div class="mg-auth-tabs">
                        <button class="mg-tab-btn active" data-tab="login">ورود</button>
                        <button class="mg-tab-btn" data-tab="register">ثبت نام</button>
                    </div>
                    
                    <!-- Login Form -->
                    <div id="mg-login-tab" class="mg-tab-content active">
                        <form id="mg-login-form" class="mg-form">
                            <div class="mg-form-group">
                                <label for="mg-login-phone">شماره تلفن</label>
                                <input type="tel" id="mg-login-phone" name="phone" required>
                            </div>
                            
                            <div class="mg-form-group">
                                <label for="mg-login-password">رمز عبور</label>
                                <input type="password" id="mg-login-password" name="password" required>
                            </div>
                            
                            <button type="submit" class="mg-btn mg-btn-primary mg-btn-full">
                                ورود
                            </button>
                            
                            <div class="mg-form-message"></div>
                        </form>
                    </div>
                    
                    <!-- Registration Form -->
                    <div id="mg-register-tab" class="mg-tab-content">
                        <form id="mg-register-form" class="mg-form">
                            <div class="mg-form-group">
                                <label for="mg-first-name">نام</label>
                                <input type="text" id="mg-first-name" name="first_name" required>
                            </div>
                            
                            <div class="mg-form-group">
                                <label for="mg-last-name">نام خانوادگی</label>
                                <input type="text" id="mg-last-name" name="last_name" required>
                            </div>
                            
                            <div class="mg-form-group">
                                <label for="mg-phone">شماره تلفن</label>
                                <input type="tel" id="mg-phone" name="phone" required>
                            </div>
                            
                            <div class="mg-form-group">
                                <label for="mg-password">رمز عبور</label>
                                <input type="password" id="mg-password" name="password" required>
                            </div>
                            
                            <button type="submit" class="mg-btn mg-btn-primary mg-btn-full">
                                ثبت نام
                            </button>
                            
                            <div class="mg-form-message"></div>
                        </form>
                        
                        <!-- Subscription Plans -->
                        <div class="mg-subscription-plans">
                            <h4>پلان‌های اشتراک</h4>
                            <div class="mg-plans-grid">
                                <?php
                                $plans = array('month1', 'month3', 'year1');
                                foreach ($plans as $plan) {
                                    $enabled = get_option('music_gate_plan_' . $plan . '_enabled', 0);
                                    if (!$enabled) continue;
                                    
                                    $price = get_option('music_gate_plan_' . $plan . '_price', 0);
                                    $image = get_option('music_gate_plan_' . $plan . '_image', '');
                                    $plan_details = mg_get_plan_details($plan);
                                    ?>
                                    <div class="mg-plan-card" data-plan="<?php echo $plan; ?>">
                                        <?php if ($image): ?>
                                            <div class="mg-plan-image">
                                                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($plan_details['name']); ?>">
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mg-plan-content">
                                            <h4><?php echo $plan_details['name']; ?></h4>
                                            <div class="mg-plan-price"><?php echo mg_format_price($price); ?> <span>تومان</span></div>
                                            <div class="mg-plan-duration"><?php echo $plan_details['description']; ?></div>
                                            
                                            <button class="mg-btn mg-btn-primary mg-purchase-plan" data-plan="<?php echo $plan; ?>">
                                                انتخاب و پرداخت
                                            </button>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function add_subscription_popup() {
        ?>
        <div id="mg-subscription-popup" class="mg-popup-overlay">
            <div class="mg-popup-content mg-subscription-popup">
                <div class="mg-popup-header">
                    <h3>انتخاب پلان اشتراک</h3>
                    <button class="mg-popup-close">&times;</button>
                </div>
                
                <div class="mg-popup-body">
                    <div class="mg-plans-grid">
                        <?php
                        $plans = array('month1', 'month3', 'year1');
                        foreach ($plans as $plan) {
                            $enabled = get_option('music_gate_plan_' . $plan . '_enabled', 0);
                            if (!$enabled) continue;
                            
                            $price = get_option('music_gate_plan_' . $plan . '_price', 0);
                            $image = get_option('music_gate_plan_' . $plan . '_image', '');
                            $plan_details = mg_get_plan_details($plan);
                            ?>
                            <div class="mg-plan-card" data-plan="<?php echo $plan; ?>">
                                <?php if ($image): ?>
                                    <div class="mg-plan-image">
                                        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($plan_details['name']); ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mg-plan-content">
                                    <h4><?php echo $plan_details['name']; ?></h4>
                                    <div class="mg-plan-price"><?php echo mg_format_price($price); ?> <span>تومان</span></div>
                                    <div class="mg-plan-duration"><?php echo $plan_details['description']; ?></div>
                                    
                                    <button class="mg-btn mg-btn-primary mg-purchase-plan" data-plan="<?php echo $plan; ?>">
                                        انتخاب و پرداخت
                                    </button>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Added 30-second popup for users without subscription -->
        <?php if (!is_user_logged_in() || !mg_user_has_subscription()): ?>
        <div id="mg-premium-reminder-popup" class="mg-popup-overlay mg-auto-popup">
            <div class="mg-popup-content mg-premium-popup">
                <div class="mg-popup-header">
                    <h3>برای دسترسی نامحدود به ملودیک نیاز به اشتراک پریمیوم دارید</h3>
                    <button class="mg-popup-close">&times;</button>
                </div>
                
                <div class="mg-popup-body">
                    <p>با خرید اشتراک پریمیوم، به تمام محتوای ملودیک دسترسی پیدا کنید.</p>
                    <button class="mg-btn mg-btn-primary mg-show-plans">مشاهده پلان‌ها</button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php
    }
    
    public function handle_purchase_plan() {
        check_ajax_referer('music_gate_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('لطفاً ابتدا وارد شوید.');
        }
        
        $plan = sanitize_text_field($_POST['plan']);
        $plan_details = mg_get_plan_details($plan);
        
        if (!$plan_details) {
            wp_send_json_error('پلان انتخابی معتبر نیست.');
        }
        
        $enabled = get_option('music_gate_plan_' . $plan . '_enabled', 0);
        if (!$enabled) {
            wp_send_json_error('این پلان در حال حاضر غیرفعال است.');
        }
        
        // Create payment URL
        $payment_url = add_query_arg(array(
            'action' => 'mg_buy',
            'plan' => $plan,
            'nonce' => wp_create_nonce('mg_buy_' . $plan)
        ), home_url());
        
        wp_send_json_success(array(
            'redirect_url' => $payment_url
        ));
    }
    
    public function handle_login() {
        check_ajax_referer('music_gate_nonce', 'nonce');
        
        $phone = mg_sanitize_phone($_POST['phone']);
        $password = $_POST['password'];
        
        if (empty($phone) || empty($password)) {
            wp_send_json_error('تمام فیلدها الزامی هستند.');
        }
        
        // Find user by phone
        $users = get_users(array(
            'meta_key' => 'phone',
            'meta_value' => $phone,
            'number' => 1
        ));
        
        if (empty($users)) {
            wp_send_json_error('کاربری با این شماره تلفن یافت نشد.');
        }
        
        $user = $users[0];
        
        // Check password
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            wp_send_json_error('رمز عبور اشتباه است.');
        }
        
        // Login user
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        
        wp_send_json_success('ورود با موفقیت انجام شد!');
    }
}
