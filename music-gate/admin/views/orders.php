<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$orders_table = $wpdb->prefix . 'musicgate_orders';

// Get orders with user info
$orders = $wpdb->get_results("
    SELECT o.*, u.display_name, u.user_email 
    FROM $orders_table o 
    LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID 
    ORDER BY o.created_at DESC
");

$plan_names = array(
    'month1' => 'یک ماهه',
    'month3' => 'سه ماهه',
    'year1' => 'یک ساله'
);

$status_names = array(
    'pending' => 'در انتظار',
    'completed' => 'تکمیل شده',
    'failed' => 'ناموفق',
    'cancelled' => 'لغو شده'
);
?>

<div class="wrap">
    <h1>مدیریت سفارشات</h1>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>شناسه</th>
                <th>کلید سفارش</th>
                <th>کاربر</th>
                <th>پلن</th>
                <th>مبلغ</th>
                <th>وضعیت</th>
                <th>شناسه زرین‌پال</th>
                <th>تاریخ ایجاد</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="8">هیچ سفارشی یافت نشد.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo esc_html($order->id); ?></td>
                        <td><?php echo esc_html($order->order_key); ?></td>
                        <td>
                            <?php echo esc_html($order->display_name); ?>
                            <br><small><?php echo esc_html($order->user_email); ?></small>
                        </td>
                        <td><?php echo esc_html($plan_names[$order->plan] ?? $order->plan); ?></td>
                        <td><?php echo mg_format_price($order->amount); ?></td>
                        <td>
                            <span class="mg-status mg-status-<?php echo esc_attr($order->status); ?>">
                                <?php echo esc_html($status_names[$order->status] ?? $order->status); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($order->zarinpal_ref_id ?: '-'); ?></td>
                        <td><?php echo esc_html(date_i18n('Y/m/d H:i', strtotime($order->created_at))); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
