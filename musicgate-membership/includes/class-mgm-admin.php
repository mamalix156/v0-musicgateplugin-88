<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MGM_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'MusicGate Membership',
            'MusicGate Membership',
            'manage_options',
            'musicgate-membership',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
        
        add_submenu_page(
            'musicgate-membership',
            'تنظیمات',
            'تنظیمات',
            'manage_options',
            'musicgate-membership',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'musicgate-membership',
            'اشتراک‌ها',
            'اشتراک‌ها',
            'manage_options',
            'musicgate-subscriptions',
            array($this, 'subscriptions_page')
        );
    }
    
    public function register_settings() {
        register_setting('mgm_settings', 'mgm_enable_restriction');
        register_setting('mgm_settings', 'mgm_restriction_percentage');
        register_setting('mgm_settings', 'mgm_product_1month');
        register_setting('mgm_settings', 'mgm_product_6months');
        register_setting('mgm_settings', 'mgm_product_1year');
        register_setting('mgm_settings', 'mgm_overlay_message');
        register_setting('mgm_settings', 'mgm_overlay_image');
        register_setting('mgm_settings', 'mgm_overlay_link');
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'musicgate-membership') !== false) {
            wp_enqueue_style('mgm-admin-style', MGM_PLUGIN_URL . 'assets/admin.css', array(), MGM_VERSION);
            wp_enqueue_script('mgm-admin-script', MGM_PLUGIN_URL . 'assets/admin.js', array('jquery'), MGM_VERSION, true);
            wp_enqueue_media();
        }
    }
    
    public function admin_page() {
        ?>
        <div class="wrap mgm-admin">
            <h1>تنظیمات MusicGate Membership</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('mgm_settings'); ?>
                <?php do_settings_sections('mgm_settings'); ?>
                
                <div class="mgm-settings-section">
                    <h2>محدودیت محتوا</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">فعال‌سازی محدودیت</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="mgm_enable_restriction" value="1" <?php checked(get_option('mgm_enable_restriction'), '1'); ?> />
                                    محدودیت محتوا را فعال کن
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">درصد محدودیت</th>
                            <td>
                                <input type="number" name="mgm_restriction_percentage" value="<?php echo esc_attr(get_option('mgm_restriction_percentage', '60')); ?>" min="0" max="100" />
                                <p class="description">درصد محتوایی که از پایین صفحه محدود می‌شود</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="mgm-settings-section">
                    <h2>شناسه محصولات ووکامرس</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">اشتراک یک ماهه</th>
                            <td>
                                <input type="number" name="mgm_product_1month" value="<?php echo esc_attr(get_option('mgm_product_1month', '31314')); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">اشتراک شش ماهه</th>
                            <td>
                                <input type="number" name="mgm_product_6months" value="<?php echo esc_attr(get_option('mgm_product_6months', '31316')); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">اشتراک یک ساله</th>
                            <td>
                                <input type="number" name="mgm_product_1year" value="<?php echo esc_attr(get_option('mgm_product_1year', '31317')); ?>" />
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="mgm-settings-section">
                    <h2>تنظیمات اورلی</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">پیام اورلی</th>
                            <td>
                                <textarea name="mgm_overlay_message" rows="3" cols="50"><?php echo esc_textarea(get_option('mgm_overlay_message', 'برای دسترسی به محتوای کامل، اشتراک پریمیوم تهیه کنید.')); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">تصویر اورلی</th>
                            <td>
                                <input type="url" name="mgm_overlay_image" value="<?php echo esc_url(get_option('mgm_overlay_image', '')); ?>" class="regular-text" />
                                <button type="button" class="button mgm-upload-image">انتخاب تصویر</button>
                                <p class="description">تصویری که در وسط اورلی نمایش داده می‌شود</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">لینک اورلی</th>
                            <td>
                                <input type="url" name="mgm_overlay_link" value="<?php echo esc_url(get_option('mgm_overlay_link', home_url('/subscription'))); ?>" class="regular-text" />
                                <p class="description">لینکی که کاربر با کلیک روی تصویر به آن هدایت می‌شود</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button('ذخیره تنظیمات'); ?>
            </form>
        </div>
        <?php
    }
    
    public function subscriptions_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'musicgate_subscriptions';
        
        $subscriptions = $wpdb->get_results(
            "SELECT s.*, u.display_name, u.user_email 
             FROM $table_name s 
             LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID 
             WHERE s.status = 'active' AND s.end_date > NOW()
             ORDER BY s.end_date DESC"
        );
        ?>
        <div class="wrap mgm-admin">
            <h1>اشتراک‌های فعال</h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>کاربر</th>
                        <th>ایمیل</th>
                        <th>پلن</th>
                        <th>تاریخ شروع</th>
                        <th>تاریخ پایان</th>
                        <th>روزهای باقی‌مانده</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($subscriptions): ?>
                        <?php foreach ($subscriptions as $sub): ?>
                            <?php
                            $end_date = new DateTime($sub->end_date);
                            $now = new DateTime();
                            $diff = $now->diff($end_date);
                            $remaining_days = $diff->invert ? 0 : $diff->days;
                            ?>
                            <tr>
                                <td><?php echo esc_html($sub->display_name); ?></td>
                                <td><?php echo esc_html($sub->user_email); ?></td>
                                <td><?php echo esc_html(mgm_get_plan_name($sub->plan)); ?></td>
                                <td><?php echo esc_html(date_i18n('Y/m/d', strtotime($sub->start_date))); ?></td>
                                <td><?php echo esc_html(date_i18n('Y/m/d', strtotime($sub->end_date))); ?></td>
                                <td><?php echo esc_html($remaining_days . ' روز'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">هیچ اشتراک فعالی یافت نشد.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
?>
