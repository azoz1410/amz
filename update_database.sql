-- إضافة جدول الإعدادات
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة إعدادات المتجر الافتراضية
INSERT INTO settings (setting_key, setting_value) VALUES
('store_name', 'Amazon Store'),
('store_name_ar', 'متجر أمازون'),
('store_phone', '+1234567890'),
('store_email', 'info@amazon-store.com'),
('store_address', ''),
('store_website', ''),
('store_logo', ''),
('tax_rate', '0'),
('currency', 'ر.س'),
('invoice_footer', 'شكراً لتعاملكم معنا')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
