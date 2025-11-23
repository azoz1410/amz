<?php
require_once 'config.php';

// جلب إعدادات المتجر
$store_settings = get_all_settings();
$currency = $store_settings['currency'] ?? 'ر.س';

$message = '';
$message_type = '';

// معالجة إنشاء الفاتورة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_invoice'])) {
    $customer_name = clean_input($_POST['customer_name']);
    $customer_email = clean_input($_POST['customer_email']);
    $customer_phone = clean_input($_POST['customer_phone']);
    $customer_address = clean_input($_POST['customer_address']);
    $discount = floatval($_POST['discount']);
    $tax = floatval($_POST['tax']);
    $notes = clean_input($_POST['notes']);
    
    $items = json_decode($_POST['items'], true);
    
    if (empty($items)) {
        $message = 'يجب إضافة منتج واحد على الأقل للفاتورة';
        $message_type = 'danger';
    } else {
        // التحقق من توفر المخزون
        $stock_error = false;
        foreach ($items as $item) {
            $product = get_product($item['product_id']);
            if (!$product || $product['stock_quantity'] < $item['quantity']) {
                $message = 'المخزون غير كافي للمنتج: ' . ($product ? $product['name'] : 'غير موجود');
                $message_type = 'danger';
                $stock_error = true;
                break;
            }
        }
        
        if (!$stock_error) {
            // حساب الإجماليات
            $subtotal = 0;
            $total_profit = 0;
            foreach ($items as $item) {
                $subtotal += $item['subtotal'];
                $total_profit += ($item['unit_price'] - $item['purchase_price']) * $item['quantity'];
            }
            
            $total = $subtotal - $discount + $tax;
            
            // إنشاء الفاتورة
            $invoice_number = generate_invoice_number();
            
            $sql = "INSERT INTO invoices (invoice_number, customer_name, customer_email, customer_phone, customer_address, 
                    subtotal, tax, discount, total, profit, notes, status) 
                    VALUES ('$invoice_number', '$customer_name', '$customer_email', '$customer_phone', '$customer_address', 
                    $subtotal, $tax, $discount, $total, $total_profit, '$notes', 'paid')";
            
            if ($conn->query($sql)) {
                $invoice_id = $conn->insert_id;
                
                // إضافة تفاصيل الفاتورة وتحديث المخزون
                $success = true;
                foreach ($items as $item) {
                    $product_id = intval($item['product_id']);
                    $quantity = intval($item['quantity']);
                    $unit_price = floatval($item['unit_price']);
                    $purchase_price = floatval($item['purchase_price']);
                    $item_subtotal = floatval($item['subtotal']);
                    $item_profit = ($unit_price - $purchase_price) * $quantity;
                    $product_name = clean_input($item['product_name']);
                    
                    $sql_item = "INSERT INTO invoice_items (invoice_id, product_id, product_name, quantity, 
                                purchase_price, unit_price, subtotal, profit) 
                                VALUES ($invoice_id, $product_id, '$product_name', $quantity, 
                                $purchase_price, $unit_price, $item_subtotal, $item_profit)";
                    
                    if ($conn->query($sql_item)) {
                        // تحديث المخزون
                        update_stock($product_id, $quantity, 'out');
                        log_stock_movement($product_id, $quantity, 'out', 'sale', $invoice_id, 'بيع - فاتورة ' . $invoice_number);
                    } else {
                        $success = false;
                        break;
                    }
                }
                
                if ($success) {
                    header("Location: invoice_view.php?id=$invoice_id");
                    exit;
                } else {
                    $message = 'حدث خطأ أثناء حفظ تفاصيل الفاتورة';
                    $message_type = 'danger';
                }
            } else {
                $message = 'حدث خطأ أثناء إنشاء الفاتورة';
                $message_type = 'danger';
            }
        }
    }
}

// جلب المنتجات المتوفرة
$products = $conn->query("SELECT * FROM products WHERE stock_quantity > 0 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء فاتورة جديدة - Amazon Store</title>
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
        .invoice-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            position: sticky;
            top: 20px;
        }
        .invoice-summary h4 {
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .summary-row.total {
            font-size: 1.2em;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
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
                        <a class="nav-link active" href="create_invoice.php"><i class="bi bi-file-earmark-plus"></i> فاتورة جديدة</a>
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
        <h2 class="text-white mb-4"><i class="bi bi-file-earmark-plus"></i> إنشاء فاتورة جديدة</h2>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-person"></i> معلومات العميل</h5>
                    </div>
                    <div class="card-body">
                        <form id="invoiceForm" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">اسم العميل *</label>
                                    <input type="text" class="form-control" name="customer_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" name="customer_email">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">رقم الهاتف</label>
                                    <input type="text" class="form-control" name="customer_phone">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">العنوان</label>
                                    <input type="text" class="form-control" name="customer_address">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-cart"></i> المنتجات</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">اختر المنتج</label>
                                <select class="form-select" id="productSelect">
                                    <option value="">-- اختر منتج --</option>
                                    <?php while ($product = $products->fetch_assoc()): ?>
                                    <option value="<?php echo $product['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                            data-price="<?php echo $product['selling_price']; ?>"
                                            data-purchase="<?php echo $product['purchase_price']; ?>"
                                            data-stock="<?php echo $product['stock_quantity']; ?>">
                                        <?php echo $product['name']; ?> - <?php echo $product['selling_price']; ?> <?php echo $currency; ?> (متوفر: <?php echo $product['stock_quantity']; ?>)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">الكمية</label>
                                <input type="number" class="form-control" id="quantityInput" min="1" value="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-primary w-100" onclick="addItem()">
                                    <i class="bi bi-plus-circle"></i> إضافة
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>المنتج</th>
                                        <th>السعر</th>
                                        <th>الكمية</th>
                                        <th>المجموع</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <tr id="emptyRow">
                                        <td colspan="5" class="text-center text-muted">لم يتم إضافة أي منتجات بعد</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">ملاحظات</label>
                            <textarea class="form-control" name="notes" form="invoiceForm" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="invoice-summary">
                    <h4>ملخص الفاتورة</h4>
                    
                    <div class="summary-row">
                        <span>المجموع الفرعي:</span>
                        <span id="displaySubtotal">0.00 <?php echo $currency; ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الخصم (<?php echo $currency; ?>)</label>
                        <input type="number" class="form-control" name="discount" form="invoiceForm" 
                               id="discountInput" value="0" step="0.01" min="0" onchange="calculateTotal()">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الضريبة (<?php echo $currency; ?>)</label>
                        <input type="number" class="form-control" name="tax" form="invoiceForm" 
                               id="taxInput" value="0" step="0.01" min="0" onchange="calculateTotal()">
                    </div>
                    
                    <div class="summary-row total">
                        <span>الإجمالي:</span>
                        <span class="text-success" id="displayTotal">0.00 <?php echo $currency; ?></span>
                    </div>
                    
                    <div class="summary-row text-muted">
                        <span>الربح المتوقع:</span>
                        <span id="displayProfit">0.00 <?php echo $currency; ?></span>
                    </div>
                    
                    <input type="hidden" name="items" form="invoiceForm" id="itemsInput">
                    <input type="hidden" name="create_invoice" form="invoiceForm" value="1">
                    
                    <button type="submit" form="invoiceForm" class="btn btn-success w-100 mt-4" id="submitBtn" disabled>
                        <i class="bi bi-check-circle"></i> إصدار الفاتورة
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let items = [];

        function addItem() {
            const select = document.getElementById('productSelect');
            const quantity = parseInt(document.getElementById('quantityInput').value);
            
            if (!select.value) {
                alert('يرجى اختيار منتج');
                return;
            }
            
            if (quantity < 1) {
                alert('يرجى إدخال كمية صحيحة');
                return;
            }
            
            const option = select.options[select.selectedIndex];
            const productId = parseInt(select.value);
            const productName = option.dataset.name;
            const price = parseFloat(option.dataset.price);
            const purchasePrice = parseFloat(option.dataset.purchase);
            const stock = parseInt(option.dataset.stock);
            
            if (quantity > stock) {
                alert('الكمية المطلوبة أكبر من المخزون المتاح');
                return;
            }
            
            // التحقق من عدم تكرار المنتج
            const existing = items.find(item => item.product_id === productId);
            if (existing) {
                existing.quantity += quantity;
                existing.subtotal = existing.quantity * existing.unit_price;
            } else {
                items.push({
                    product_id: productId,
                    product_name: productName,
                    unit_price: price,
                    purchase_price: purchasePrice,
                    quantity: quantity,
                    subtotal: price * quantity
                });
            }
            
            renderItems();
            select.value = '';
            document.getElementById('quantityInput').value = 1;
        }

        function removeItem(index) {
            items.splice(index, 1);
            renderItems();
        }

        function renderItems() {
            const tbody = document.getElementById('itemsBody');
            const emptyRow = document.getElementById('emptyRow');
            
            if (items.length === 0) {
                emptyRow.style.display = '';
                document.getElementById('submitBtn').disabled = true;
            } else {
                emptyRow.style.display = 'none';
                document.getElementById('submitBtn').disabled = false;
            }
            
            // مسح الصفوف الحالية
            Array.from(tbody.children).forEach(row => {
                if (row.id !== 'emptyRow') row.remove();
            });
            
            // إضافة الصفوف الجديدة
            items.forEach((item, index) => {
                const row = tbody.insertRow();
                row.innerHTML = `
                    <td>${item.product_name}</td>
                    <td>${item.unit_price.toFixed(2)} <?php echo $currency; ?></td>
                    <td>${item.quantity}</td>
                    <td>${item.subtotal.toFixed(2)} <?php echo $currency; ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${index})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
            });
            
            calculateTotal();
        }

        function calculateTotal() {
            const subtotal = items.reduce((sum, item) => sum + item.subtotal, 0);
            const discount = parseFloat(document.getElementById('discountInput').value) || 0;
            const tax = parseFloat(document.getElementById('taxInput').value) || 0;
            const total = subtotal - discount + tax;
            
            const profit = items.reduce((sum, item) => {
                return sum + ((item.unit_price - item.purchase_price) * item.quantity);
            }, 0);
            
            document.getElementById('displaySubtotal').textContent = subtotal.toFixed(2) + ' <?php echo $currency; ?>';
            document.getElementById('displayTotal').textContent = total.toFixed(2) + ' <?php echo $currency; ?>';
            document.getElementById('displayProfit').textContent = profit.toFixed(2) + ' <?php echo $currency; ?>';
            document.getElementById('itemsInput').value = JSON.stringify(items);
        }
    </script>
</body>
</html>
