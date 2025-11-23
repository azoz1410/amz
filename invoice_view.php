<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    header('Location: invoices.php');
    exit;
}

$invoice_id = intval($_GET['id']);

// جلب إعدادات المتجر
$store_settings = get_all_settings();
$currency = $store_settings['currency'] ?? 'ر.س';

// جلب بيانات الفاتورة
$invoice_query = $conn->query("SELECT * FROM invoices WHERE id = $invoice_id");
if (!$invoice_query || $invoice_query->num_rows == 0) {
    header('Location: invoices.php');
    exit;
}
$invoice = $invoice_query->fetch_assoc();

// جلب تفاصيل الفاتورة
$items = $conn->query("SELECT * FROM invoice_items WHERE invoice_id = $invoice_id");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة رقم <?php echo $invoice['invoice_number']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        .invoice-container {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .invoice-header {
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .invoice-title {
            color: #667eea;
            font-size: 32px;
            font-weight: bold;
        }
        .invoice-number {
            font-size: 20px;
            color: #666;
        }
        .company-info {
            text-align: left;
        }
        .customer-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .table th {
            background: #667eea;
            color: white;
        }
        .invoice-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
        .total-row {
            font-size: 20px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 15px;
            margin-top: 15px;
        }
        .btn-print {
            position: fixed;
            bottom: 30px;
            left: 30px;
            z-index: 1000;
        }
        @media print {
            .btn-print, .no-print {
                display: none !important;
            }
            .invoice-container {
                box-shadow: none;
                margin: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="no-print text-center pt-3">
        <a href="invoices.php" class="btn btn-secondary">
            <i class="bi bi-arrow-right"></i> العودة للفواتير
        </a>
        <a href="create_invoice.php" class="btn btn-primary">
            <i class="bi bi-file-earmark-plus"></i> فاتورة جديدة
        </a>
    </div>

    <div class="invoice-container">
        <div class="invoice-header">
            <div class="row">
                <div class="col-md-6">
                    <div class="invoice-title">فاتورة</div>
                    <div class="invoice-number">رقم: <?php echo $invoice['invoice_number']; ?></div>
                    <div class="text-muted">
                        التاريخ: <?php echo date('Y/m/d', strtotime($invoice['created_at'])); ?>
                    </div>
                </div>
                <div class="col-md-6 company-info">
                    <h4><?php echo $store_settings['store_name'] ?? 'Amazon Store'; ?></h4>
                    <p class="mb-1"><?php echo $store_settings['store_name_ar'] ?? 'متجر أمازون'; ?></p>
                    <?php if (!empty($store_settings['store_phone'])): ?>
                    <p class="mb-1"><i class="bi bi-telephone"></i> <?php echo $store_settings['store_phone']; ?></p>
                    <?php endif; ?>
                    <?php if (!empty($store_settings['store_email'])): ?>
                    <p class="mb-1"><i class="bi bi-envelope"></i> <?php echo $store_settings['store_email']; ?></p>
                    <?php endif; ?>
                    <?php if (!empty($store_settings['store_address'])): ?>
                    <p class="mb-1"><i class="bi bi-geo-alt"></i> <?php echo $store_settings['store_address']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="customer-box">
            <h5 class="mb-3"><i class="bi bi-person"></i> معلومات العميل</h5>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-2"><strong>الاسم:</strong> <?php echo $invoice['customer_name']; ?></p>
                    <?php if ($invoice['customer_phone']): ?>
                    <p class="mb-2"><strong>الهاتف:</strong> <?php echo $invoice['customer_phone']; ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <?php if ($invoice['customer_email']): ?>
                    <p class="mb-2"><strong>البريد:</strong> <?php echo $invoice['customer_email']; ?></p>
                    <?php endif; ?>
                    <?php if ($invoice['customer_address']): ?>
                    <p class="mb-2"><strong>العنوان:</strong> <?php echo $invoice['customer_address']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <h5 class="mb-3"><i class="bi bi-cart"></i> تفاصيل المنتجات</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>المنتج</th>
                    <th>السعر</th>
                    <th>الكمية</th>
                    <th>المجموع</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $counter = 1;
                while ($item = $items->fetch_assoc()): 
                ?>
                <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo $item['product_name']; ?></td>
                    <td><?php echo number_format($item['unit_price'], 2); ?> <?php echo $currency; ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo number_format($item['subtotal'], 2); ?> <?php echo $currency; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="invoice-summary">
            <div class="row">
                <div class="col-md-8"></div>
                <div class="col-md-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span>المجموع الفرعي:</span>
                        <span><?php echo number_format($invoice['subtotal'], 2); ?> <?php echo $currency; ?></span>
                    </div>
                    <?php if ($invoice['discount'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>الخصم:</span>
                        <span>- <?php echo number_format($invoice['discount'], 2); ?> <?php echo $currency; ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($invoice['tax'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>الضريبة:</span>
                        <span><?php echo number_format($invoice['tax'], 2); ?> <?php echo $currency; ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between total-row text-success">
                        <span>الإجمالي:</span>
                        <span><?php echo number_format($invoice['total'], 2); ?> <?php echo $currency; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($invoice['notes']): ?>
        <div class="mt-4">
            <h6>ملاحظات:</h6>
            <p class="text-muted"><?php echo nl2br($invoice['notes']); ?></p>
        </div>
        <?php endif; ?>

        <div class="text-center mt-5 pt-4" style="border-top: 1px solid #dee2e6;">
            <p class="text-muted mb-0"><?php echo $store_settings['invoice_footer'] ?? 'شكراً لتعاملكم معنا'; ?></p>
            <p class="text-muted"><?php echo $store_settings['store_name'] ?? 'Amazon Store'; ?> - جميع الحقوق محفوظة © <?php echo date('Y'); ?></p>
        </div>
    </div>

    <button class="btn btn-lg btn-primary btn-print" onclick="window.print()">
        <i class="bi bi-printer"></i> طباعة الفاتورة
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
