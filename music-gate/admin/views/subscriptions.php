<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$subscriptions_table = $wpdb->prefix . 'musicgate_subscriptions';

// Get subscriptions with user info
$subscriptions = $wpdb->get_results("
    SELECT s.*, u.display_name, u.user_email 
    FROM $subscriptions_table s 
    LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID 
    ORDER BY s.created_at DESC
");

$plan_names = array(
    'month1' => 'یک ماهه',
    'month3' => 'سه ماهه',
    'year1' => 'یک ساله'
);

$status_names = array(
    'active' => 'فعال',
    'expired' => 'منقضی شده',
    'inactive' => 'غیرفعال'
);
?>

<div class="wrap">
    <h1>مدیریت اشتراک‌ها</h1>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>شناسه</th>
                <th>کاربر</th>
                <th>پلن</th>
                <th>تاریخ شروع</th>
                <th>تاریخ پایان</th>
                <th>وضعیت</th>
                <th>تاریخ ایجاد</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($subscriptions)): ?>
                <tr>
                    <td colspan="7">هیچ اشتراکی یافت نشد.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($subscriptions as $subscription): ?>
                    <tr>
                        <td><?php echo esc_html($subscription->id); ?></td>
                        <td>
                            <?php echo esc_html($subscription->display_name); ?>
                            <br><small><?php echo esc_html($subscription->user_email); ?></small>
                        </td>
                        <td><?php echo esc_html($plan_names[$subscription->plan] ?? $subscription->plan); ?></td>
                        <td><?php echo esc_html(date_i18n('Y/m/d H:i', strtotime($subscription->start_date))); ?></td>
                        <td><?php echo esc_html(date_i18n('Y/m/d H:i', strtotime($subscription->end_date))); ?></td>
                        <td>
                            <span class="mg-status mg-status-<?php echo esc_attr($subscription->status); ?>">
                                <?php echo esc_html($status_names[$subscription->status] ?? $subscription->status); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date_i18n('Y/m/d H:i', strtotime($subscription->created_at))); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
