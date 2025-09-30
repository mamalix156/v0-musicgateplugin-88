<?php
/**
 * Orders admin page
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$orders_table = $wpdb->prefix . 'musicgate_orders';
$orders = $wpdb->get_results("
    SELECT o.*, u.display_name, u.user_email 
    FROM $orders_table o 
    LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID 
    ORDER BY o.created_at DESC
");
?>

<div class="wrap">
    <h1><?php _e('Orders', MUSIC_GATE_TEXT_DOMAIN); ?></h1>
    
    <div class="mg-admin-content">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Order ID', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                    <th><?php _e('User', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Plan', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Amount', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Status', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Date', MUSIC_GATE_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6"><?php _e('No orders found.', MUSIC_GATE_TEXT_DOMAIN); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo esc_html($order->order_key); ?></td>
                            <td>
                                <?php echo esc_html($order->display_name); ?>
                                <br>
                                <small><?php echo esc_html($order->user_email); ?></small>
                            </td>
                            <td><?php echo esc_html(ucfirst($order->plan)); ?></td>
                            <td><?php echo esc_html(number_format($order->amount)); ?> <?php _e('Toman', MUSIC_GATE_TEXT_DOMAIN); ?></td>
                            <td>
                                <span class="mg-status mg-status-<?php echo esc_attr($order->status); ?>">
                                    <?php echo esc_html(ucfirst($order->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($order->created_at))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
