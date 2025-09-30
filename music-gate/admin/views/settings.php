<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit'])) {
    check_admin_referer('music_gate_settings');
    
    update_option('music_gate_enabled', isset($_POST['music_gate_enabled']) ? '1' : '0');
    update_option('music_gate_restriction_percentage', intval($_POST['music_gate_restriction_percentage']));
    update_option('music_gate_overlay_text', sanitize_textarea_field($_POST['music_gate_overlay_text']));
    update_option('music_gate_overlay_image', esc_url_raw($_POST['music_gate_overlay_image']));
    update_option('music_gate_overlay_link', esc_url_raw($_POST['music_gate_overlay_link']));
    
    // Plan settings
    update_option('music_gate_plan_month1_name', sanitize_text_field($_POST['music_gate_plan_month1_name']));
    update_option('music_gate_plan_month1_price', intval($_POST['music_gate_plan_month1_price']));
    update_option('music_gate_plan_month1_enabled', isset($_POST['music_gate_plan_month1_enabled']) ? '1' : '0');
    update_option('music_gate_plan_month1_image', esc_url_raw($_POST['music_gate_plan_month1_image']));
    
    update_option('music_gate_plan_month3_name', sanitize_text_field($_POST['music_gate_plan_month3_name']));
    update_option('music_gate_plan_month3_price', intval($_POST['music_gate_plan_month3_price']));
    update_option('music_gate_plan_month3_enabled', isset($_POST['music_gate_plan_month3_enabled']) ? '1' : '0');
    update_option('music_gate_plan_month3_image', esc_url_raw($_POST['music_gate_plan_month3_image']));
    
    update_option('music_gate_plan_year1_name', sanitize_text_field($_POST['music_gate_plan_year1_name']));
    update_option('music_gate_plan_year1_price', intval($_POST['music_gate_plan_year1_price']));
    update_option('music_gate_plan_year1_enabled', isset($_POST['music_gate_plan_year1_enabled']) ? '1' : '0');
    update_option('music_gate_plan_year1_image', esc_url_raw($_POST['music_gate_plan_year1_image']));
    
    update_option('music_gate_zarinpal_merchant', sanitize_text_field($_POST['music_gate_zarinpal_merchant']));
    
    echo '<div class="notice notice-success"><p>تنظیمات ذخیره شد.</p></div>';
}

// Get current values
$enabled = get_option('music_gate_enabled', '1');
$restriction_percentage = get_option('music_gate_restriction_percentage', '60');
$overlay_text = get_option('music_gate_overlay_text', 'برای دسترسی به محتوای کامل، نیاز به اشتراک دارید.');
$overlay_image = get_option('music_gate_overlay_image', '');
$overlay_link = get_option('music_gate_overlay_link', '');

$plan_month1_name = get_option('music_gate_plan_month1_name', 'اشتراک یک ماهه');
$plan_month1_price = get_option('music_gate_plan_month1_price', '50000');
$plan_month1_enabled = get_option('music_gate_plan_month1_enabled', '1');
$plan_month1_image = get_option('music_gate_plan_month1_image', '');

$plan_month3_name = get_option('music_gate_plan_month3_name', 'اشتراک سه ماهه');
$plan_month3_price = get_option('music_gate_plan_month3_price', '120000');
$plan_month3_enabled = get_option('music_gate_plan_month3_enabled', '1');
$plan_month3_image = get_option('music_gate_plan_month3_image', '');

$plan_year1_name = get_option('music_gate_plan_year1_name', 'اشتراک یک ساله');
$plan_year1_price = get_option('music_gate_plan_year1_price', '400000');
$plan_year1_enabled = get_option('music_gate_plan_year1_enabled', '1');
$plan_year1_image = get_option('music_gate_plan_year1_image', '');

$zarinpal_merchant = get_option('music_gate_zarinpal_merchant', '');
?>

<div class="wrap">
    <h1>تنظیمات دروازه موسیقی</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('music_gate_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">فعال‌سازی افزونه</th>
                <td>
                    <label>
                        <input type="checkbox" name="music_gate_enabled" value="1" <?php checked($enabled, '1'); ?>>
                        افزونه فعال باشد
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">درصد محدودیت محتوا</th>
                <td>
                    <input type="number" name="music_gate_restriction_percentage" value="<?php echo esc_attr($restriction_percentage); ?>" min="10" max="90" class="small-text">
                    <p class="description">درصد محتوایی که برای کاربران غیر مشترک نمایش داده می‌شود (۱۰ تا ۹۰)</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">متن پوشش محدودیت</th>
                <td>
                    <textarea name="music_gate_overlay_text" rows="3" cols="50"><?php echo esc_textarea($overlay_text); ?></textarea>
                    <p class="description">متنی که در پوشش محدودیت نمایش داده می‌شود</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">تصویر پوشش محدودیت</th>
                <td>
                    <input type="url" name="music_gate_overlay_image" value="<?php echo esc_attr($overlay_image); ?>" class="regular-text">
                    <p class="description">URL تصویر پوشش محدودیت (اختیاری)</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">لینک پوشش محدودیت</th>
                <td>
                    <input type="url" name="music_gate_overlay_link" value="<?php echo esc_attr($overlay_link); ?>" class="regular-text">
                    <p class="description">لینک دکمه پوشش محدودیت (اختیاری)</p>
                </td>
            </tr>
        </table>
        
        <h2>تنظیمات پلن‌های اشتراک</h2>
        
        <h3>پلن یک ماهه</h3>
        <table class="form-table">
            <tr>
                <th scope="row">فعال</th>
                <td>
                    <label>
                        <input type="checkbox" name="music_gate_plan_month1_enabled" value="1" <?php checked($plan_month1_enabled, '1'); ?>>
                        پلن یک ماهه فعال باشد
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">نام پلن</th>
                <td>
                    <input type="text" name="music_gate_plan_month1_name" value="<?php echo esc_attr($plan_month1_name); ?>" class="regular-text">
                    <p class="description">نام نمایشی پلن یک ماهه</p>
                </td>
            </tr>
            <tr>
                <th scope="row">قیمت (تومان)</th>
                <td>
                    <input type="number" name="music_gate_plan_month1_price" value="<?php echo esc_attr($plan_month1_price); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">تصویر پلن</th>
                <td>
                    <input type="url" name="music_gate_plan_month1_image" value="<?php echo esc_attr($plan_month1_image); ?>" class="regular-text">
                    <p class="description">URL تصویر پلن یک ماهه</p>
                </td>
            </tr>
        </table>
        
        <h3>پلن سه ماهه</h3>
        <table class="form-table">
            <tr>
                <th scope="row">فعال</th>
                <td>
                    <label>
                        <input type="checkbox" name="music_gate_plan_month3_enabled" value="1" <?php checked($plan_month3_enabled, '1'); ?>>
                        پلن سه ماهه فعال باشد
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">نام پلن</th>
                <td>
                    <input type="text" name="music_gate_plan_month3_name" value="<?php echo esc_attr($plan_month3_name); ?>" class="regular-text">
                    <p class="description">نام نمایشی پلن سه ماهه</p>
                </td>
            </tr>
            <tr>
                <th scope="row">قیمت (تومان)</th>
                <td>
                    <input type="number" name="music_gate_plan_month3_price" value="<?php echo esc_attr($plan_month3_price); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">تصویر پلن</th>
                <td>
                    <input type="url" name="music_gate_plan_month3_image" value="<?php echo esc_attr($plan_month3_image); ?>" class="regular-text">
                    <p class="description">URL تصویر پلن سه ماهه</p>
                </td>
            </tr>
        </table>
        
        <h3>پلن یک ساله</h3>
        <table class="form-table">
            <tr>
                <th scope="row">فعال</th>
                <td>
                    <label>
                        <input type="checkbox" name="music_gate_plan_year1_enabled" value="1" <?php checked($plan_year1_enabled, '1'); ?>>
                        پلن یک ساله فعال باشد
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">نام پلن</th>
                <td>
                    <input type="text" name="music_gate_plan_year1_name" value="<?php echo esc_attr($plan_year1_name); ?>" class="regular-text">
                    <p class="description">نام نمایشی پلن یک ساله</p>
                </td>
            </tr>
            <tr>
                <th scope="row">قیمت (تومان)</th>
                <td>
                    <input type="number" name="music_gate_plan_year1_price" value="<?php echo esc_attr($plan_year1_price); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">تصویر پلن</th>
                <td>
                    <input type="url" name="music_gate_plan_year1_image" value="<?php echo esc_attr($plan_year1_image); ?>" class="regular-text">
                    <p class="description">URL تصویر پلن یک ساله</p>
                </td>
            </tr>
        </table>
        
        <h2>تنظیمات زرین‌پال</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Merchant ID</th>
                <td>
                    <input type="text" name="music_gate_zarinpal_merchant" value="<?php echo esc_attr($zarinpal_merchant); ?>" class="regular-text">
                    <p class="description">شناسه پذیرنده زرین‌پال</p>
                </td>
            </tr>
        </table>
        
        <?php submit_button('ذخیره تنظیمات'); ?>
    </form>
</div>
