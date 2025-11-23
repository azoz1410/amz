<?php
require_once 'config.php';


// جلب إعدادات المتجر
$store_settings = get_all_settings();
$currency = $store_settings['currency'] ?? 'ر.س';

$message = '';
$message_type = '';

// معالجة الإضافة أو التعديل أو الحذف
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $name = clean_input($_POST['name']);
            $description = clean_input($_POST['description']);
            $sku = clean_input($_POST['sku']);
            $purchase_price = floatval($_POST['purchase_price']);
            $selling_price = floatval($_POST['selling_price']);
            $stock_quantity = intval($_POST['stock_quantity']);
            $min_stock_level = intval($_POST['min_stock_level']);
            
            if ($_POST['action'] == 'add') {
                $sql = "INSERT INTO products (name, description, sku, purchase_price, selling_price, stock_quantity, min_stock_level) 
                        VALUES ('$name', '$description', '$sku', $purchase_price, $selling_price, $stock_quantity, $min_stock_level)";
                
                if ($conn->query($sql)) {
                    $product_id = $conn->insert_id;
                    log_stock_movement($product_id, $stock_quantity, 'in', 'purchase', null, 'إضافة مخزون أولي');
                    $message = 'تم إضافة المنتج بنجاح';
                    $message_type = 'success';
                } else {
                    $message = 'خطأ في إضافة المنتج: ' . $conn->error;
                    $message_type = 'danger';
                }
            } else {
                $id = intval($_POST['id']);
                $old_product = get_product($id);
                
                $sql = "UPDATE products SET 
                        name = '$name', 
                        description = '$description', 
                        sku = '$sku', 
                        purchase_price = $purchase_price, 
                        selling_price = $selling_price, 
                        stock_quantity = $stock_quantity,
                        min_stock_level = $min_stock_level 
                        WHERE id = $id";
                
                if ($conn->query($sql)) {
                    // تسجيل التغيير في المخزون
                    $quantity_diff = $stock_quantity - $old_product['stock_quantity'];
                    if ($quantity_diff != 0) {
                        log_stock_movement($id, abs($quantity_diff), 
                            $quantity_diff > 0 ? 'in' : 'out', 
                            'adjustment', null, 'تعديل المخزون يدوياً');
                    }
                    $message = 'تم تحديث المنتج بنجاح';
                    $message_type = 'success';
                } else {
                    $message = 'خطأ في تحديث المنتج: ' . $conn->error;
                    $message_type = 'danger';
                }
            }
        } elseif ($_POST['action'] == 'delete') {
            $id = intval($_POST['id']);
            
            // التحقق من عدم وجود فواتير مرتبطة
            $check = $conn->query("SELECT COUNT(*) as count FROM invoice_items WHERE product_id = $id");
            $row = $check->fetch_assoc();
            
            if ($row['count'] > 0) {
                $message = 'لا يمكن حذف المنتج لوجود فواتير مرتبطة به';
                $message_type = 'danger';
            } else {
                if ($conn->query("DELETE FROM products WHERE id = $id")) {
                    $message = 'تم حذف المنتج بنجاح';
                    $message_type = 'success';
                } else {
                    $message = 'خطأ في حذف المنتج';
                    $message_type = 'danger';
                }
            }
        }
    }
}

// جلب جميع المنتجات
$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المخزون - Amazon Store</title>
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
        .low-stock {
            background-color: #fff3cd;
        }
        .out-of-stock {
            background-color: #f8d7da;
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
                        <a class="nav-link active" href="products.php"><i class="bi bi-box-seam"></i> المخزون</a>
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
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-white"><i class="bi bi-box-seam"></i> إدارة المخزون والمنتجات</h2>
            <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetForm()">
                <i class="bi bi-plus-circle"></i> إضافة منتج جديد
            </button>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>الرقم</th>
                            <th>اسم المنتج</th>
                            <th>SKU</th>
                            <th>سعر الشراء</th>
                            <th>سعر البيع</th>
                            <th>المخزون</th>
                            <th>الربح/القطعة</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = $products->fetch_assoc()): ?>
                        <?php 
                            $row_class = '';
                            $status = '<span class="badge bg-success">متوفر</span>';
                            if ($product['stock_quantity'] == 0) {
                                $row_class = 'out-of-stock';
                                $status = '<span class="badge bg-danger">نفد المخزون</span>';
                            } elseif ($product['stock_quantity'] <= $product['min_stock_level']) {
                                $row_class = 'low-stock';
                                $status = '<span class="badge bg-warning">قليل</span>';
                            }
                            $profit = $product['selling_price'] - $product['purchase_price'];
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <strong><?php echo $product['name']; ?></strong><br>
                                <small class="text-muted"><?php echo $product['description']; ?></small>
                            </td>
                            <td><?php echo $product['sku']; ?></td>
                            <td><?php echo number_format($product['purchase_price'], 2); ?> <?php echo $currency; ?></td>
                            <td><?php echo number_format($product['selling_price'], 2); ?> <?php echo $currency; ?></td>
                            <td><span class="badge bg-info"><?php echo $product['stock_quantity']; ?></span></td>
                            <td class="<?php echo $profit > 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo number_format($profit, 2); ?> <?php echo $currency; ?>
                            </td>
                            <td><?php echo $status; ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo $product['name']; ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- نموذج إضافة/تعديل المنتج -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">إضافة منتج جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="id" id="product_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">اسم المنتج *</label>
                                <input type="text" class="form-control" name="name" id="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">رمز المنتج (SKU) *</label>
                                <input type="text" class="form-control" name="sku" id="sku" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea class="form-control" name="description" id="description" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">سعر الشراء (<?php echo $currency; ?>) *</label>
                                <input type="number" step="0.01" class="form-control" name="purchase_price" id="purchase_price" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">سعر البيع (<?php echo $currency; ?>) *</label>
                                <input type="number" step="0.01" class="form-control" name="selling_price" id="selling_price" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الكمية في المخزون *</label>
                                <input type="number" class="form-control" name="stock_quantity" id="stock_quantity" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الحد الأدنى للمخزون *</label>
                                <input type="number" class="form-control" name="min_stock_level" id="min_stock_level" value="5" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- نموذج الحذف -->
    <form method="POST" id="deleteForm">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'إضافة منتج جديد';
            document.getElementById('action').value = 'add';
            document.getElementById('product_id').value = '';
            document.querySelector('form').reset();
        }

        function editProduct(product) {
            document.getElementById('modalTitle').textContent = 'تعديل المنتج';
            document.getElementById('action').value = 'edit';
            document.getElementById('product_id').value = product.id;
            document.getElementById('name').value = product.name;
            document.getElementById('description').value = product.description;
            document.getElementById('sku').value = product.sku;
            document.getElementById('purchase_price').value = product.purchase_price;
            document.getElementById('selling_price').value = product.selling_price;
            document.getElementById('stock_quantity').value = product.stock_quantity;
            document.getElementById('min_stock_level').value = product.min_stock_level;
            
            new bootstrap.Modal(document.getElementById('productModal')).show();
        }

        function deleteProduct(id, name) {
            if (confirm('هل أنت متأكد من حذف المنتج "' + name + '"؟')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
