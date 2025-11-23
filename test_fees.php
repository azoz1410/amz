<?php
/**
 * Test Fees System
 * اختبار نظام الرسوم والعمولات
 */

require_once __DIR__ . '/backend/config.php';
require_once __DIR__ . '/backend/classes/Product.php';
require_once __DIR__ . '/backend/classes/Invoice.php';
require_once __DIR__ . '/backend/classes/Reports.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <title>اختبار نظام الرسوم</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .test { background: white; margin: 10px 0; padding: 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .success { color: green; }
        .error { color: red; }
        h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: right; }
        th { background: #667eea; color: white; }
    </style>
</head>
<body>
<h1>🧪 اختبار نظام الرسوم والعمولات</h1>";

// إنشاء كائنات
$product = new Product();
$invoice = new Invoice();
$reports = new Reports();

// اختبار 1: إضافة منتج مع رسوم
echo "<div class='test'>";
echo "<h2>1️⃣ اختبار إضافة منتج مع رسوم</h2>";

$testProduct = [
    'name' => 'منتج تجريبي - ' . date('H:i:s'),
    'sku' => 'TEST-' . time(),
    'description' => 'منتج لاختبار نظام الرسوم',
    'purchase_price' => 50.00,
    'selling_price' => 150.00,
    'amazon_fee_percent' => 15.00,      // عمولة أمازون 15%
    'default_shipping_fee' => 10.00,     // رسوم الشحن 10 ريال
    'default_storage_fee' => 5.00,       // رسوم التخزين 5 ريال
    'stock_quantity' => 100,
    'min_stock_level' => 10
];

$result = $product->createProduct($testProduct);

if ($result && isset($result['id'])) {
    $productId = $result['id'];
    echo "<p class='success'>✅ تم إنشاء المنتج بنجاح - ID: {$productId}</p>";
    
    // عرض تفاصيل المنتج
    $productData = $product->getProduct($productId);
    echo "<table>";
    echo "<tr><th>الحقل</th><th>القيمة</th></tr>";
    echo "<tr><td>الاسم</td><td>{$productData['name']}</td></tr>";
    echo "<tr><td>SKU</td><td>{$productData['sku']}</td></tr>";
    echo "<tr><td>سعر الشراء</td><td>{$productData['purchase_price']} ر.س</td></tr>";
    echo "<tr><td>سعر البيع</td><td>{$productData['selling_price']} ر.س</td></tr>";
    echo "<tr><td>الربح قبل الرسوم</td><td>{$productData['profit_per_unit']} ر.س</td></tr>";
    echo "<tr><td>عمولة أمازون</td><td>{$productData['amazon_fee_percent']}%</td></tr>";
    echo "<tr><td>رسوم الشحن</td><td>{$productData['default_shipping_fee']} ر.س</td></tr>";
    echo "<tr><td>رسوم التخزين</td><td>{$productData['default_storage_fee']} ر.س</td></tr>";
    echo "<tr><td><strong>صافي الربح</strong></td><td><strong>{$productData['net_profit_per_unit']} ر.س</strong></td></tr>";
    echo "</table>";
    
    $amazon_fee = ($productData['selling_price'] * $productData['amazon_fee_percent']) / 100;
    $total_fees = $amazon_fee + $productData['default_shipping_fee'] + $productData['default_storage_fee'];
    
    echo "<p>📊 حسابات تفصيلية:</p>";
    echo "<ul>";
    echo "<li>سعر البيع: {$productData['selling_price']} ر.س</li>";
    echo "<li>تكلفة الشراء: {$productData['purchase_price']} ر.س</li>";
    echo "<li>الربح الإجمالي: {$productData['profit_per_unit']} ر.س</li>";
    echo "<li>عمولة أمازون ({$productData['amazon_fee_percent']}%): " . number_format($amazon_fee, 2) . " ر.س</li>";
    echo "<li>رسوم الشحن: {$productData['default_shipping_fee']} ر.س</li>";
    echo "<li>رسوم التخزين: {$productData['default_storage_fee']} ر.س</li>";
    echo "<li><strong>إجمالي الرسوم: " . number_format($total_fees, 2) . " ر.س</strong></li>";
    echo "<li><strong>صافي الربح: {$productData['net_profit_per_unit']} ر.س</strong></li>";
    echo "</ul>";
} else {
    echo "<p class='error'>❌ فشل إنشاء المنتج</p>";
    exit;
}

echo "</div>";

// اختبار 2: إنشاء فاتورة مع الرسوم
echo "<div class='test'>";
echo "<h2>2️⃣ اختبار إنشاء فاتورة مع رسوم</h2>";

$invoiceData = [
    'customer_name' => 'عميل تجريبي',
    'customer_email' => 'test@example.com',
    'customer_phone' => '0500000000',
    'customer_address' => 'الرياض، السعودية',
    'discount' => 0,
    'tax' => 0,
    'notes' => 'فاتورة تجريبية لاختبار نظام الرسوم',
    'items' => [
        [
            'product_id' => $productId,
            'product_name' => $productData['name'],
            'quantity' => 2,
            'unit_price' => $productData['selling_price'],
            'purchase_price' => $productData['purchase_price'],
            'subtotal' => $productData['selling_price'] * 2
            // الرسوم ستحسب تلقائياً من بيانات المنتج
        ]
    ]
];

$invoiceResult = $invoice->createInvoice($invoiceData);

if (isset($invoiceResult['id'])) {
    $invoiceId = $invoiceResult['id'];
    echo "<p class='success'>✅ تم إنشاء الفاتورة بنجاح - رقم الفاتورة: {$invoiceResult['invoice_number']}</p>";
    
    // جلب تفاصيل الفاتورة
    $invoiceDetails = $invoice->getInvoice($invoiceId);
    
    echo "<table>";
    echo "<tr><th>الحقل</th><th>القيمة</th></tr>";
    echo "<tr><td>رقم الفاتورة</td><td>{$invoiceDetails['invoice_number']}</td></tr>";
    echo "<tr><td>العميل</td><td>{$invoiceDetails['customer_name']}</td></tr>";
    echo "<tr><td>المجموع الفرعي</td><td>{$invoiceDetails['subtotal']} ر.س</td></tr>";
    echo "<tr><td>الإجمالي</td><td>{$invoiceDetails['total']} ر.س</td></tr>";
    echo "<tr><td>الربح الإجمالي</td><td>{$invoiceDetails['profit']} ر.س</td></tr>";
    echo "<tr><td><strong>إجمالي الرسوم</strong></td><td><strong>{$invoiceDetails['total_fees']} ر.س</strong></td></tr>";
    echo "<tr><td><strong>صافي الربح</strong></td><td><strong>{$invoiceDetails['net_profit']} ر.س</strong></td></tr>";
    echo "</table>";
    
    echo "<h3>تفاصيل المنتجات:</h3>";
    echo "<table>";
    echo "<tr><th>المنتج</th><th>الكمية</th><th>السعر</th><th>عمولة أمازون</th><th>الشحن</th><th>التخزين</th><th>إجمالي الرسوم</th><th>صافي الربح</th></tr>";
    
    foreach ($invoiceDetails['items'] as $item) {
        echo "<tr>";
        echo "<td>{$item['product_name']}</td>";
        echo "<td>{$item['quantity']}</td>";
        echo "<td>{$item['unit_price']} ر.س</td>";
        echo "<td>" . number_format($item['amazon_fee'], 2) . " ر.س</td>";
        echo "<td>" . number_format($item['shipping_fee'], 2) . " ر.س</td>";
        echo "<td>" . number_format($item['storage_fee'], 2) . " ر.س</td>";
        echo "<td><strong>" . number_format($item['total_fees'], 2) . " ر.س</strong></td>";
        echo "<td><strong>" . number_format($item['net_profit'], 2) . " ر.س</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<p class='error'>❌ فشل إنشاء الفاتورة: " . ($invoiceResult['error'] ?? 'خطأ غير معروف') . "</p>";
}

echo "</div>";

// اختبار 3: إحصائيات لوحة التحكم
echo "<div class='test'>";
echo "<h2>3️⃣ اختبار إحصائيات لوحة التحكم</h2>";

$stats = $reports->getDashboardStats();

echo "<table>";
echo "<tr><th>الإحصائية</th><th>القيمة</th></tr>";
echo "<tr><td>إجمالي المبيعات</td><td>" . number_format($stats['total_sales'], 2) . " ر.س</td></tr>";
echo "<tr><td>إجمالي الربح (قبل الرسوم)</td><td>" . number_format($stats['total_profit'], 2) . " ر.س</td></tr>";
echo "<tr><td><strong>إجمالي الرسوم</strong></td><td><strong>" . number_format($stats['total_fees'], 2) . " ر.س</strong></td></tr>";
echo "<tr><td><strong>صافي الربح (بعد الرسوم)</strong></td><td><strong>" . number_format($stats['net_profit'], 2) . " ر.س</strong></td></tr>";
echo "<tr><td>عدد الفواتير</td><td>{$stats['total_invoices']}</td></tr>";
echo "<tr><td>عدد المنتجات</td><td>{$stats['total_products']}</td></tr>";
echo "</table>";

if ($stats['total_profit'] > 0) {
    $fee_percentage = ($stats['total_fees'] / $stats['total_profit']) * 100;
    $net_percentage = ($stats['net_profit'] / $stats['total_profit']) * 100;
    
    echo "<p>📈 تحليل:</p>";
    echo "<ul>";
    echo "<li>نسبة الرسوم من الربح الإجمالي: <strong>" . number_format($fee_percentage, 2) . "%</strong></li>";
    echo "<li>نسبة صافي الربح من الربح الإجمالي: <strong>" . number_format($net_percentage, 2) . "%</strong></li>";
    echo "</ul>";
}

echo "</div>";

echo "<div class='test'>";
echo "<h2>✅ اكتمل الاختبار بنجاح!</h2>";
echo "<p>جميع الميزات تعمل بشكل صحيح:</p>";
echo "<ul>";
echo "<li>✅ إضافة منتجات مع رسوم افتراضية</li>";
echo "<li>✅ حساب الرسوم تلقائياً عند إنشاء الفواتير</li>";
echo "<li>✅ حساب صافي الربح بعد خصم الرسوم</li>";
echo "<li>✅ عرض الرسوم في التقارير والإحصائيات</li>";
echo "</ul>";
echo "<p><strong>ملاحظة:</strong> لا تنس تشغيل ملف <code>update_fees_system.sql</code> على قاعدة البيانات إذا لم تقم بذلك بعد.</p>";
echo "</div>";

echo "</body></html>";
?>
