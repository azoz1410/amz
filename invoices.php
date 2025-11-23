<?php
require_once 'config.php';

// جلب إعدادات المتجر
$store_settings = get_all_settings();
$currency = $store_settings['currency'] ?? 'ر.س';

$message = '';
$message_type = '';

// معالجة حذف الفاتورة
if (isset($_POST['delete_invoice'])) {
    $invoice_id = intval($_POST['invoice_id']);
    
    // جلب تفاصيل الفاتورة قبل الحذف
    $invoice = $conn->query("SELECT * FROM invoices WHERE id = $invoice_id")->fetch_assoc();
    
    if ($invoice) {
        // إرجاع المنتجات للمخزون
        $items = $conn->query("SELECT * FROM invoice_items WHERE invoice_id = $invoice_id");
        while ($item = $items->fetch_assoc()) {
            update_stock($item['product_id'], $item['quantity'], 'in');
            log_stock_movement($item['product_id'], $item['quantity'], 'in', 'return', $invoice_id, 
                'إرجاع - حذف فاتورة ' . $invoice['invoice_number']);
        }
        
        // حذف الفاتورة (سيتم حذف التفاصيل تلقائياً بسبب CASCADE)
        if ($conn->query("DELETE FROM invoices WHERE id = $invoice_id")) {
            $message = 'تم حذف الفاتورة وإرجاع المنتجات للمخزون بنجاح';
            $message_type = 'success';
        } else {
            $message = 'حدث خطأ أثناء حذف الفاتورة';
            $message_type = 'danger';
        }
    }
}

// جلب جميع الفواتير
$search = '';
$where = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = clean_input($_GET['search']);
    $where = "WHERE invoice_number LIKE '%$search%' OR customer_name LIKE '%$search%' OR customer_email LIKE '%$search%'";
}

$invoices = $conn->query("SELECT * FROM invoices $where ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الفواتير - Amazon Store</title>
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
        }
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
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
                        <a class="nav-link active" href="invoices.php"><i class="bi bi-receipt"></i> الفواتير</a>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-white"><i class="bi bi-receipt"></i> جميع الفواتير</h2>
            <a href="create_invoice.php" class="btn btn-light btn-lg">
                <i class="bi bi-plus-circle"></i> فاتورة جديدة
            </a>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-10">
                        <input type="text" class="form-control" name="search" 
                               placeholder="بحث برقم الفاتورة أو اسم العميل..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> بحث
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>رقم الفاتورة</th>
                            <th>العميل</th>
                            <th>التاريخ</th>
                            <th>المبلغ</th>
                            <th>الربح</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($invoices && $invoices->num_rows > 0): ?>
                            <?php while ($invoice = $invoices->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $invoice['invoice_number']; ?></strong>
                                </td>
                                <td>
                                    <?php echo $invoice['customer_name']; ?><br>
                                    <?php if ($invoice['customer_phone']): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-telephone"></i> <?php echo $invoice['customer_phone']; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y/m/d', strtotime($invoice['created_at'])); ?></td>
                                <td class="fw-bold text-primary"><?php echo number_format($invoice['total'], 2); ?> <?php echo $currency; ?></td>
                                <td class="fw-bold text-success"><?php echo number_format($invoice['profit'], 2); ?> <?php echo $currency; ?></td>
                                <td>
                                    <?php
                                    $status_class = 'bg-success';
                                    $status_text = 'مدفوعة';
                                    if ($invoice['status'] == 'pending') {
                                        $status_class = 'bg-warning';
                                        $status_text = 'معلقة';
                                    } elseif ($invoice['status'] == 'cancelled') {
                                        $status_class = 'bg-danger';
                                        $status_text = 'ملغاة';
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </td>
                                <td>
                                    <a href="invoice_view.php?id=<?php echo $invoice['id']; ?>" 
                                       class="btn btn-sm btn-primary" target="_blank" title="عرض الفاتورة">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit_invoice.php?id=<?php echo $invoice['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="تعديل الفاتورة">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="deleteInvoice(<?php echo $invoice['id']; ?>, '<?php echo $invoice['invoice_number']; ?>')"
                                            title="حذف الفاتورة">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">لا توجد فواتير</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- نموذج الحذف -->
    <form method="POST" id="deleteForm">
        <input type="hidden" name="delete_invoice" value="1">
        <input type="hidden" name="invoice_id" id="delete_invoice_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteInvoice(id, invoiceNumber) {
            if (confirm('هل أنت متأكد من حذف الفاتورة "' + invoiceNumber + '"?\n\nملاحظة: سيتم إرجاع جميع المنتجات للمخزون')) {
                document.getElementById('delete_invoice_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
