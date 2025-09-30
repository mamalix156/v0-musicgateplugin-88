<?php
/**
 * Subscriptions admin page
 */

if (!defined('ABSPATH')) {
    exit;
}

$subscriptions_manager = new MG_Subscriptions();
$subscriptions = $subscriptions_manager->get_all_subscriptions();
?>

<div class="wrap">
    <h1><?php _e('Subscriptions', MUSIC_GATE_TEXT_DOMAIN); ?></h1>
    
    <div class="mg-admin-content">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                    <th><?php _e('User', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Plan', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Start Date', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                    <th><?php _e('End Date', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Status', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscriptions)): ?>
                    <tr>
                        <td colspan="6"><?php _e('No subscriptions found.', MUSIC_GATE_TEXT_DOMAIN); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($subscriptions as $subscription): ?>
                        <tr>
                            <td><?php echo esc_html($subscription->id); ?></td>
                            <td>
                                <?php echo esc_html($subscription->display_name); ?>
                                <br>
                                <small><?php echo esc_html($subscription->user_email); ?></small>
                            </td>
                            <td><?php echo esc_html(ucfirst($subscription->plan)); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription->start_date))); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription->end_date))); ?></td>
                            <td>
                                <span class="mg-status mg-status-<?php echo esc_attr($subscription->status); ?>">
                                    <?php echo esc_html(ucfirst($subscription->status)); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
