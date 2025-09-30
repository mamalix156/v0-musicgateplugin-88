-- اضافه کردن ستون authority به جدول سفارشات
ALTER TABLE wp_musicgate_orders 
ADD COLUMN zarinpal_authority VARCHAR(100) AFTER zarinpal_ref_id;

-- اضافه کردن ایندکس برای بهبود عملکرد
CREATE INDEX idx_authority ON wp_musicgate_orders(zarinpal_authority);
