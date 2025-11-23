# نظام إدارة المخزون والفواتير لمتجر أمازون

# Amazon Store Inventory and Invoice Management System

## البنية الجديدة للمشروع / New Project Structure

تم إعادة هيكلة المشروع بحيث يكون منفصلاً إلى:

- **Backend**: واجهة برمجة التطبيقات (API)
- **Frontend**: واجهة المستخدم (UI)

```
AMZ/
├── backend/                    # الواجهة الخلفية
│   ├── config.php             # إعدادات قاعدة البيانات
│   ├── classes/               # الكلاسات
│   │   ├── Product.php        # إدارة المنتجات
│   │   ├── Invoice.php        # إدارة الفواتير
│   │   ├── Settings.php       # إدارة الإعدادات
│   │   └── Reports.php        # إدارة التقارير
│   └── api/                   # نقاط النهاية API
│       ├── products.php       # API المنتجات
│       ├── invoices.php       # API الفواتير
│       ├── settings.php       # API الإعدادات
│       └── reports.php        # API التقارير
│
├── frontend/                   # الواجهة الأمامية
│   ├── assets/                # الملفات الثابتة
│   │   ├── css/
│   │   │   └── main.css       # الأنماط الرئيسية
│   │   └── js/
│   │       ├── api.js         # وحدة API
│   │       └── main.js        # الدوال المشتركة
│   └── pages/                 # صفحات HTML
│       ├── index.html         # لوحة التحكم
│       ├── products.html      # إدارة المنتجات
│       ├── invoices.html      # قائمة الفواتير
│       ├── create_invoice.html # إنشاء فاتورة
│       ├── edit_invoice.html  # تعديل فاتورة
│       ├── invoice_view.html  # عرض الفاتورة
│       ├── reports.html       # التقارير
│       └── settings.html      # الإعدادات
│
├── database.sql               # قاعدة البيانات
├── update_database.sql        # تحديث قاعدة البيانات
└── README.md                  # هذا الملف
```

## المتطلبات / Requirements

- PHP 7.4 أو أحدث
- MySQL 5.7 أو أحدث
- خادم Apache (XAMPP, WAMP, أو مشابه)
- متصفح حديث يدعم ES6+

## التثبيت / Installation

### 1. نسخ الملفات

```bash
# نسخ المشروع إلى مجلد htdocs
cp -r AMZ /xampp/htdocs/
```

### 2. إعداد قاعدة البيانات

1. افتح phpMyAdmin: `http://localhost/phpmyadmin`
2. أنشئ قاعدة بيانات جديدة باسم: `amz_inventory`
3. استورد ملف `database.sql`
4. استورد ملف `update_database.sql`

### 3. تهيئة Backend

افتح ملف `backend/config.php` وتأكد من إعدادات الاتصال:

```php
private $host = 'localhost';
private $username = 'root';
private $password = '';
private $database = 'amz_inventory';
```

### 4. تشغيل التطبيق

افتح المتصفح وانتقل إلى:

```
http://localhost/AMZ/frontend/pages/index.html
```

## API Endpoints

### المنتجات / Products

- **GET** `backend/api/products.php` - جلب جميع المنتجات
- **GET** `backend/api/products.php?id=1` - جلب منتج واحد
- **GET** `backend/api/products.php?available=1` - المنتجات المتاحة
- **GET** `backend/api/products.php?low_stock=1` - المنتجات منخفضة المخزون
- **POST** `backend/api/products.php` - إنشاء منتج جديد
- **PUT** `backend/api/products.php?id=1` - تحديث منتج
- **DELETE** `backend/api/products.php?id=1` - حذف منتج

### الفواتير / Invoices

- **GET** `backend/api/invoices.php` - جلب جميع الفواتير
- **GET** `backend/api/invoices.php?id=1` - جلب فاتورة واحدة
- **POST** `backend/api/invoices.php` - إنشاء فاتورة جديدة
- **PUT** `backend/api/invoices.php?id=1` - تحديث فاتورة
- **DELETE** `backend/api/invoices.php?id=1` - حذف فاتورة

### الإعدادات / Settings

- **GET** `backend/api/settings.php` - جلب جميع الإعدادات
- **GET** `backend/api/settings.php?key=store_name` - جلب إعداد واحد
- **POST** `backend/api/settings.php` - حفظ إعدادات

### التقارير / Reports

- **GET** `backend/api/reports.php?type=dashboard` - إحصائيات لوحة التحكم
- **GET** `backend/api/reports.php?type=today` - مبيعات اليوم
- **GET** `backend/api/reports.php?type=month` - مبيعات الشهر
- **GET** `backend/api/reports.php?type=top_products&limit=5` - أفضل المنتجات
- **GET** `backend/api/reports.php?type=recent_invoices&limit=5` - أحدث الفواتير
- **GET** `backend/api/reports.php?type=sales&start_date=2024-01-01&end_date=2024-12-31` - تقرير المبيعات
- **GET** `backend/api/reports.php?type=profit&start_date=2024-01-01&end_date=2024-12-31` - تقرير الأرباح
- **GET** `backend/api/reports.php?type=stock_movements&product_id=1&limit=50` - حركة المخزون
- **GET** `backend/api/reports.php?type=low_stock` - تقرير المخزون المنخفض

## المميزات / Features

✅ **إدارة المنتجات الكاملة**

- إضافة، تعديل، وحذف المنتجات
- تتبع المخزون تلقائياً
- تنبيهات للمخزون المنخفض

✅ **نظام الفواتير**

- إنشاء فواتير احترافية
- تعديل وحذف الفواتير
- خصم تلقائي من المخزون
- إرجاع المخزون عند الحذف

✅ **التقارير والإحصائيات**

- لوحة تحكم شاملة
- تقارير المبيعات والأرباح
- أفضل المنتجات مبيعاً
- حركة المخزون

✅ **الإعدادات**

- تغيير اسم المتجر
- تخصيص العملة
- إعدادات قابلة للتوسع

✅ **البنية المعمارية**

- فصل كامل بين Backend و Frontend
- RESTful API
- CORS جاهز للاستخدام
- كود نظيف وقابل للصيانة

## تنسيق البيانات / Data Format

### إنشاء منتج

```json
{
  "name": "اسم المنتج",
  "sku": "SKU123",
  "description": "وصف المنتج",
  "purchase_price": 50.0,
  "selling_price": 100.0,
  "stock_quantity": 100,
  "low_stock_alert": 10
}
```

### إنشاء فاتورة

```json
{
  "customer_name": "اسم العميل",
  "customer_email": "email@example.com",
  "customer_phone": "0500000000",
  "customer_address": "العنوان",
  "discount": 10.0,
  "tax": 15.0,
  "notes": "ملاحظات",
  "items": [
    {
      "product_id": 1,
      "product_name": "اسم المنتج",
      "quantity": 2,
      "unit_price": 100.0,
      "purchase_price": 50.0,
      "subtotal": 200.0
    }
  ]
}
```

## الأمان / Security

- تنظيف جميع المدخلات
- استخدام Prepared Statements لمنع SQL Injection
- CORS محدد للنطاقات المسموحة
- التحقق من صحة البيانات في Backend

## الملفات القديمة / Old Files

الملفات الموجودة في المجلد الرئيسي هي النسخة القديمة (Monolithic) وتعمل بشكل مستقل:

- `index.php` - لوحة التحكم القديمة
- `products.php` - إدارة المنتجات القديمة
- `invoices.php` - قائمة الفواتير القديمة
- إلخ...

يمكنك الاحتفاظ بها كنسخة احتياطية أو حذفها بعد التأكد من عمل النظام الجديد.

## الدعم / Support

للمشاكل أو الأسئلة، يرجى فتح issue أو التواصل مع المطور.

## الترخيص / License

هذا المشروع للاستخدام الشخصي والتجاري.

---

**ملاحظة**: تأكد من تغيير إعدادات قاعدة البيانات في `backend/config.php` قبل الاستخدام.
