<?php
require_once 'config.php';

// جلب إعدادات المتجر
$store_settings = get_all_settings();
$currency = $store_settings['currency'] ?? 'ر.س';

// تحديد الفترة الزمنية
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// إحصائيات عامة
$total_sales = 0;
$total_profit = 0;
$total_invoices = 0;
$total_items_sold = 0;

$result = $conn->query("SELECT 
    COUNT(*) as invoice_count,
    SUM(total) as total_sales,
    SUM(profit) as total_profit
    FROM invoices 
    WHERE status = 'paid' 
    AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'");

if ($result) {
    $stats = $result->fetch_assoc();
    $total_invoices = $stats['invoice_count'];
    $total_sales = $stats['total_sales'] ?? 0;
    $total_profit = $stats['total_profit'] ?? 0;
}

$result = $conn->query("SELECT SUM(ii.quantity) as total_items 
    FROM invoice_items ii
    JOIN invoices i ON i.id = ii.invoice_id
    WHERE i.status = 'paid'
    AND DATE(i.created_at) BETWEEN '$start_date' AND '$end_date'");

if ($result) {
    $row = $result->fetch_assoc();
    $total_items_sold = $row['total_items'] ?? 0;
}

// أفضل المنتجات مبيعاً
$top_products = $conn->query("SELECT 
    p.name,
    p.sku,
    SUM(ii.quantity) as total_sold,
    SUM(ii.subtotal) as revenue,
    SUM(ii.profit) as profit
    FROM invoice_items ii
    JOIN products p ON p.id = ii.product_id
    JOIN invoices i ON i.id = ii.invoice_id
    WHERE i.status = 'paid'
    AND DATE(i.created_at) BETWEEN '$start_date' AND '$end_date'
    GROUP BY ii.product_id
    ORDER BY total_sold DESC
    LIMIT 10");

// المبيعات اليومية
$daily_sales = $conn->query("SELECT 
    DATE(created_at) as sale_date,
    COUNT(*) as invoice_count,
    SUM(total) as daily_total,
    SUM(profit) as daily_profit
    FROM invoices
    WHERE status = 'paid'
    AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'
    GROUP BY DATE(created_at)
    ORDER BY sale_date DESC");

// المنتجات منخفضة المخزون
$low_stock = $conn->query("SELECT * FROM products 
    WHERE stock_quantity <= min_stock_level 
    ORDER BY stock_quantity ASC 
    LIMIT 10");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير والأرباح - Amazon Store</title>
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
            margin-bottom: 30px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 15px;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-shop"></i> نظام إدارة متجر Amazon
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-house-door"></i> الرئيسية</a>
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
                        <a class="nav-link active" href="reports.php"><i class="bi bi-graph-up"></i> التقارير</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <h2 class="text-white mb-4"><i class="bi bi-graph-up"></i> التقارير المالية والأرباح</h2>

        <!-- فلتر الفترة الزمنية -->
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">من تاريخ</label>
                        <input type="date" class="form-control" name="start_date" 
                               value="<?php echo $start_date; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">إلى تاريخ</label>
                        <input type="date" class="form-control" name="end_date" 
                               value="<?php echo $end_date; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i> عرض التقرير
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- الإحصائيات الرئيسية -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div class="stat-value text-primary"><?php echo number_format($total_sales, 2); ?> <?php echo $currency; ?></div>
                    <div class="stat-label">إجمالي المبيعات</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div class="stat-value text-success"><?php echo number_format($total_profit, 2); ?> <?php echo $currency; ?></div>
                    <div class="stat-label">صافي الأرباح</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="stat-value text-info"><?php echo $total_invoices; ?></div>
                    <div class="stat-label">عدد الفواتير</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="stat-value text-warning"><?php echo $total_items_sold; ?></div>
                    <div class="stat-label">القطع المباعة</div>
                </div>
            </div>
        </div>

        <?php if ($total_sales > 0): ?>
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">متوسط قيمة الفاتورة</div>
                    <div class="stat-value text-secondary"><?php echo number_format($total_sales / $total_invoices, 2); ?> <?php echo $currency; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">نسبة الربح</div>
                    <div class="stat-value text-success"><?php echo number_format(($total_profit / $total_sales) * 100, 1); ?>%</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">متوسط الربح للفاتورة</div>
                    <div class="stat-value text-success"><?php echo number_format($total_profit / $total_invoices, 2); ?> <?php echo $currency; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">متوسط السعر للقطعة</div>
                    <div class="stat-value text-primary"><?php echo number_format($total_sales / $total_items_sold, 2); ?> <?php echo $currency; ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- أفضل المنتجات مبيعاً -->
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="bi bi-star-fill text-warning"></i> أفضل المنتجات مبيعاً</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>المنتج</th>
                                    <th>الكمية</th>
                                    <th>الإيراد</th>
                                    <th>الربح</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($top_products && $top_products->num_rows > 0): ?>
                                    <?php while ($product = $top_products->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $product['name']; ?></strong><br>
                                            <small class="text-muted"><?php echo $product['sku']; ?></small>
                                        </td>
                                        <td><span class="badge bg-info"><?php echo $product['total_sold']; ?></span></td>
                                        <td class="text-primary"><?php echo number_format($product['revenue'], 2); ?> <?php echo $currency; ?></td>
                                        <td class="text-success"><?php echo number_format($product['profit'], 2); ?> <?php echo $currency; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">لا توجد بيانات</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- المبيعات اليومية -->
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="bi bi-calendar-check text-primary"></i> المبيعات اليومية</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الفواتير</th>
                                    <th>المبيعات</th>
                                    <th>الربح</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($daily_sales && $daily_sales->num_rows > 0): ?>
                                    <?php while ($day = $daily_sales->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('Y/m/d', strtotime($day['sale_date'])); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo $day['invoice_count']; ?></span></td>
                                        <td class="text-primary"><?php echo number_format($day['daily_total'], 2); ?> <?php echo $currency; ?></td>
                                        <td class="text-success"><?php echo number_format($day['daily_profit'], 2); ?> <?php echo $currency; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">لا توجد بيانات</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- تنبيه المخزون المنخفض -->
        <?php if ($low_stock && $low_stock->num_rows > 0): ?>
        <div class="chart-container">
            <h5 class="mb-3"><i class="bi bi-exclamation-triangle-fill text-warning"></i> تنبيه: منتجات منخفضة المخزون</h5>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-warning">
                        <tr>
                            <th>المنتج</th>
                            <th>SKU</th>
                            <th>المخزون الحالي</th>
                            <th>الحد الأدنى</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = $low_stock->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $product['name']; ?></td>
                            <td><?php echo $product['sku']; ?></td>
                            <td>
                                <span class="badge <?php echo $product['stock_quantity'] == 0 ? 'bg-danger' : 'bg-warning'; ?>">
                                    <?php echo $product['stock_quantity']; ?>
                                </span>
                            </td>
                            <td><?php echo $product['min_stock_level']; ?></td>
                            <td>
                                <?php if ($product['stock_quantity'] == 0): ?>
                                    <span class="badge bg-danger">نفد المخزون</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">يحتاج إعادة تزويد</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center mt-3">
                <a href="products.php" class="btn btn-warning">
                    <i class="bi bi-box-seam"></i> إدارة المخزون
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
