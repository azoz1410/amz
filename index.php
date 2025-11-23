<?php
require_once 'config.php';

// جلب إعدادات المتجر
$store_settings = get_all_settings();
$store_name = $store_settings['store_name_ar'] ?? 'نظام إدارة متجر Amazon';
$currency = $store_settings['currency'] ?? 'ر.س';

// جلب إحصائيات عامة
$stats = [
    'total_products' => 0,
    'low_stock_products' => 0,
    'total_invoices' => 0,
    'total_revenue' => 0,
    'total_profit' => 0
];

$result = $conn->query("SELECT COUNT(*) as count FROM products");
if ($result) $stats['total_products'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= min_stock_level");
if ($result) $stats['low_stock_products'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM invoices WHERE status = 'paid'");
if ($result) $stats['total_invoices'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT SUM(total) as total, SUM(profit) as profit FROM invoices WHERE status = 'paid'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['total_revenue'] = $row['total'] ?? 0;
    $stats['total_profit'] = $row['profit'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة المخزون والفواتير - Amazon Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .container-main {
            margin-top: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
        .quick-action {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            color: inherit;
        }
        .quick-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            color: inherit;
        }
        .quick-action i {
            font-size: 48px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-shop"></i> <?php echo $store_name; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php"><i class="bi bi-house-door"></i> الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php"><i class="bi bi-box-seam"></i> المخزون</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_invoice.php"><i class="bi bi-file-earmark-plus"></i> فاتورة جديدة</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="invoices.php"><i class="bi bi-receipt"></i> الفواتير</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php"><i class="bi bi-graph-up"></i> التقارير</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> الإعدادات</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <h2 class="text-white mb-4"><i class="bi bi-speedometer2"></i> لوحة التحكم</h2>
        
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="stat-value text-primary"><?php echo $stats['total_products']; ?></div>
                    <div class="stat-label">إجمالي المنتجات</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="stat-value text-warning"><?php echo $stats['low_stock_products']; ?></div>
                    <div class="stat-label">منتجات قليلة المخزون</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="stat-value text-info"><?php echo $stats['total_invoices']; ?></div>
                    <div class="stat-label">عدد الفواتير</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div class="stat-value text-success"><?php echo number_format($stats['total_profit'], 2); ?> <?php echo $currency; ?></div>
                    <div class="stat-label">إجمالي الأرباح</div>
                </div>
            </div>
        </div>

        <h4 class="text-white mb-3 mt-4">الإجراءات السريعة</h4>
        <div class="row">
            <div class="col-md-3">
                <a href="products.php" class="quick-action">
                    <i class="bi bi-box-seam text-primary"></i>
                    <h5>إدارة المخزون</h5>
                    <p class="text-muted mb-0">إضافة وتعديل المنتجات</p>
                </a>
            </div>
            <div class="col-md-3">
                <a href="create_invoice.php" class="quick-action">
                    <i class="bi bi-file-earmark-plus text-success"></i>
                    <h5>فاتورة جديدة</h5>
                    <p class="text-muted mb-0">إنشاء فاتورة للعميل</p>
                </a>
            </div>
            <div class="col-md-3">
                <a href="invoices.php" class="quick-action">
                    <i class="bi bi-receipt text-info"></i>
                    <h5>عرض الفواتير</h5>
                    <p class="text-muted mb-0">مراجعة الفواتير السابقة</p>
                </a>
            </div>
            <div class="col-md-3">
                <a href="reports.php" class="quick-action">
                    <i class="bi bi-graph-up text-danger"></i>
                    <h5>التقارير والأرباح</h5>
                    <p class="text-muted mb-0">تحليل المبيعات والأرباح</p>
                </a>
            </div>
        </div>

        <?php if ($stats['low_stock_products'] > 0): ?>
        <div class="alert alert-warning mt-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>تنبيه!</strong> يوجد <?php echo $stats['low_stock_products']; ?> منتج قليل المخزون. يرجى التحقق من المخزون وإعادة التزويد.
            <a href="products.php" class="alert-link">عرض المنتجات</a>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
