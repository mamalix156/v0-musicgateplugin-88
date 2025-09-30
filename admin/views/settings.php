<?php
/**
 * Admin settings page
 */

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
    update_option('music_gate_plan_month1_price', intval($_POST['music_gate_plan_month1_price']));
    update_option('music_gate_plan_month1_enabled', isset($_POST['music_gate_plan_month1_enabled']) ? '1' : '0');
    update_option('music_gate_plan_month1_image', esc_url_raw($_POST['music_gate_plan_month1_image']));
    
    update_option('music_gate_plan_month3_price', intval($_POST['music_gate_plan_month3_price']));
    update_option('music_gate_plan_month3_enabled', isset($_POST['music_gate_plan_month3_enabled']) ? '1' : '0');
    update_option('music_gate_plan_month3_image', esc_url_raw($_POST['music_gate_plan_month3_image']));
    
    update_option('music_gate_plan_year1_price', intval($_POST['music_gate_plan_year1_price']));
    update_option('music_gate_plan_year1_enabled', isset($_POST['music_gate_plan_year1_enabled']) ? '1' : '0');
    update_option('music_gate_plan_year1_image', esc_url_raw($_POST['music_gate_plan_year1_image']));
    
    update_option('music_gate_zarinpal_merchant', sanitize_text_field($_POST['music_gate_zarinpal_merchant']));
    
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', MUSIC_GATE_TEXT_DOMAIN) . '</p></div>';
}

// Get current values
$enabled = get_option('music_gate_enabled', '1');
$restriction_percentage = get_option('music_gate_restriction_percentage', '60');
$overlay_text = get_option('music_gate_overlay_text', __('You need a subscription to access full content.', MUSIC_GATE_TEXT_DOMAIN));
$overlay_image = get_option('music_gate_overlay_image', '');
$overlay_link = get_option('music_gate_overlay_link', '');

$plan_month1_price = get_option('music_gate_plan_month1_price', '50000');
$plan_month1_enabled = get_option('music_gate_plan_month1_enabled', '1');
$plan_month1_image = get_option('music_gate_plan_month1_image', '');

$plan_month3_price = get_option('music_gate_plan_month3_price', '120000');
$plan_month3_enabled = get_option('music_gate_plan_month3_enabled', '1');
$plan_month3_image = get_option('music_gate_plan_month3_image', '');

$plan_year1_price = get_option('music_gate_plan_year1_price', '400000');
$plan_year1_enabled = get_option('music_gate_plan_year1_enabled', '1');
$plan_year1_image = get_option('music_gate_plan_year1_image', '');

$zarinpal_merchant = get_option('music_gate_zarinpal_merchant', '9a22baaf-b9ac-421a-b8ce-7d7e25779130');
?>

<div class="wrap">
    <h1><?php _e('Music Gate Settings', MUSIC_GATE_TEXT_DOMAIN); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('music_gate_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Plugin', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="music_gate_enabled" value="1" <?php checked($enabled, '1'); ?>>
                        <?php _e('Enable content restriction', MUSIC_GATE_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Restriction Percentage', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number" name="music_gate_restriction_percentage" value="<?php echo esc_attr($restriction_percentage); ?>" min="0" max="100" class="small-text">
                    <p class="description"><?php _e('Percentage of content to cover (0-100)', MUSIC_GATE_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Overlay Text', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                <td>
                    <textarea name="music_gate_overlay_text" rows="3" cols="50"><?php echo esc_textarea($overlay_text); ?></textarea>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Overlay Image URL', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="url" name="music_gate_overlay_image" value="<?php echo esc_attr($overlay_image); ?>" class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Overlay Link URL', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="url" name="music_gate_overlay_link" value="<?php echo esc_attr($overlay_link); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Subscription Plans', MUSIC_GATE_TEXT_DOMAIN); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('1 Month Plan', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="music_gate_plan_month1_enabled" value="1" <?php checked($plan_month1_enabled, '1'); ?>>
                        <?php _e('Enable', MUSIC_GATE_TEXT_DOMAIN); ?>
                    </label>
                    <br>
                    <input type="number" name="music_gate_plan_month1_price" value="<?php echo esc_attr($plan_month1_price); ?>" class="regular-text" placeholder="قیمت">
                    <span><?php _e('Toman', MUSIC_GATE_TEXT_DOMAIN); ?></span>
                    <br>
                    <input type="url" name="music_gate_plan_month1_image" value="<?php echo esc_attr($plan_month1_image); ?>" class="regular-text" placeholder="آدرس تصویر پلان">
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('3 Months Plan', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="music_gate_plan_month3_enabled" value="1" <?php checked($plan_month3_enabled, '1'); ?>>
                        <?php _e('Enable', MUSIC_GATE_TEXT_DOMAIN); ?>
                    </label>
                    <br>
                    <input type="number" name="music_gate_plan_month3_price" value="<?php echo esc_attr($plan_month3_price); ?>" class="regular-text" placeholder="قیمت">
                    <span><?php _e('Toman', MUSIC_GATE_TEXT_DOMAIN); ?></span>
                    <br>
                    <input type="url" name="music_gate_plan_month3_image" value="<?php echo esc_attr($plan_month3_image); ?>" class="regular-text" placeholder="آدرس تصویر پلان">
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('1 Year Plan', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="music_gate_plan_year1_enabled" value="1" <?php checked($plan_year1_enabled, '1'); ?>>
                        <?php _e('Enable', MUSIC_GATE_TEXT_DOMAIN); ?>
                    </label>
                    <br>
                    <input type="number" name="music_gate_plan_year1_price" value="<?php echo esc_attr($plan_year1_price); ?>" class="regular-text" placeholder="قیمت">
                    <span><?php _e('Toman', MUSIC_GATE_TEXT_DOMAIN); ?></span>
                    <br>
                    <input type="url" name="music_gate_plan_year1_image" value="<?php echo esc_attr($plan_year1_image); ?>" class="regular-text" placeholder="آدرس تصویر پلان">
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Payment Settings', MUSIC_GATE_TEXT_DOMAIN); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Zarinpal Merchant ID', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="text" name="music_gate_zarinpal_merchant" value="<?php echo esc_attr($zarinpal_merchant); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        
        <?php submit_button(__('Save Settings', MUSIC_GATE_TEXT_DOMAIN)); ?>
    </form>
</div>
