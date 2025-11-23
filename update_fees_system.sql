-- تحديث نظام الرسوم والعمولات
-- Update Fees System for Amazon Commissions, Shipping, and Storage

-- إضافة حقول الرسوم الافتراضية لجدول المنتجات
-- Add default fee fields to products table
ALTER TABLE `products` 
ADD COLUMN `amazon_fee_percent` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'نسبة عمولة أمازون (%)' AFTER `selling_price`,
ADD COLUMN `default_shipping_fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'رسوم الشحن الافتراضية' AFTER `amazon_fee_percent`,
ADD COLUMN `default_storage_fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'رسوم التخزين الافتراضية' AFTER `default_shipping_fee`;

-- إضافة حقول الرسوم الفعلية لجدول تفاصيل الفواتير
-- Add actual fee fields to invoice_items table
ALTER TABLE `invoice_items`
ADD COLUMN `amazon_fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'عمولة أمازون الفعلية' AFTER `profit`,
ADD COLUMN `shipping_fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'رسوم الشحن الفعلية' AFTER `amazon_fee`,
ADD COLUMN `storage_fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'رسوم التخزين الفعلية' AFTER `shipping_fee`,
ADD COLUMN `total_fees` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'إجمالي الرسوم' AFTER `storage_fee`,
ADD COLUMN `net_profit` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'صافي الربح بعد الرسوم' AFTER `total_fees`;

-- إضافة حقول الرسوم الإجمالية لجدول الفواتير
-- Add total fee fields to invoices table
ALTER TABLE `invoices`
ADD COLUMN `total_fees` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'إجمالي الرسوم' AFTER `profit`,
ADD COLUMN `net_profit` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'صافي الربح بعد الرسوم' AFTER `total_fees`;

-- تحديث الفواتير الموجودة (صافي الربح = الربح - الرسوم)
-- Update existing invoices (net profit = profit - fees since fees are 0)
UPDATE `invoices` SET `net_profit` = `profit` WHERE `net_profit` = 0;

-- تحديث تفاصيل الفواتير الموجودة
-- Update existing invoice items
UPDATE `invoice_items` SET `net_profit` = `profit` WHERE `net_profit` = 0;

-- إنشاء فهرس للبحث السريع عن المنتجات بعمولات عالية
-- Create index for quick lookup of high commission products
CREATE INDEX `idx_amazon_fee` ON `products` (`amazon_fee_percent`);

-- عرض البنية الجديدة
-- Show new structure
SELECT 'تم تحديث قاعدة البيانات بنجاح - Database updated successfully' AS status;
