<?php
require_once 'config.php';

$message = '';
$message_type = '';

// معالجة حفظ الإعدادات
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $settings_to_save = [
        'store_name',
        'store_name_ar',
        'store_phone',
        'store_email',
        'store_address',
        'store_website',
        'tax_rate',
        'currency',
        'invoice_footer'
    ];
    
    $success = true;
    foreach ($settings_to_save as $key) {
        if (isset($_POST[$key])) {
            if (!save_setting($key, $_POST[$key])) {
                $success = false;
                break;
            }
        }
    }
    
    if ($success) {
        $message = 'تم حفظ الإعدادات بنجاح';
        $message_type = 'success';
    } else {
        $message = 'حدث خطأ أثناء حفظ الإعدادات';
        $message_type = 'danger';
    }
}

// جلب الإعدادات الحالية
$settings = get_all_settings();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات المتجر - <?php echo $settings['store_name'] ?? 'Amazon Store'; ?></title>
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
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-shop"></i> <?php echo $settings['store_name_ar'] ?? 'نظام إدارة متجر Amazon'; ?>
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
                        <a class="nav-link" href="reports.php"><i class="bi bi-graph-up"></i> التقارير</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php"><i class="bi bi-gear"></i> الإعدادات</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <h2 class="text-white mb-4"><i class="bi bi-gear"></i> إعدادات المتجر</h2>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> معلومات المتجر</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">اسم المتجر (بالإنجليزية) *</label>
                            <input type="text" class="form-control" name="store_name" 
                                   value="<?php echo htmlspecialchars($settings['store_name'] ?? 'Amazon Store'); ?>" required>
                            <small class="text-muted">سيظهر في الفواتير والمراسلات الرسمية</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">اسم المتجر (بالعربية) *</label>
                            <input type="text" class="form-control" name="store_name_ar" 
                                   value="<?php echo htmlspecialchars($settings['store_name_ar'] ?? 'متجر أمازون'); ?>" required>
                            <small class="text-muted">سيظهر في واجهة النظام</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">رقم الهاتف</label>
                            <input type="text" class="form-control" name="store_phone" 
                                   value="<?php echo htmlspecialchars($settings['store_phone'] ?? '+1234567890'); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" class="form-control" name="store_email" 
                                   value="<?php echo htmlspecialchars($settings['store_email'] ?? 'info@amazon-store.com'); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">العنوان</label>
                        <input type="text" class="form-control" name="store_address" 
                               value="<?php echo htmlspecialchars($settings['store_address'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الموقع الإلكتروني</label>
                        <input type="url" class="form-control" name="store_website" 
                               value="<?php echo htmlspecialchars($settings['store_website'] ?? ''); ?>"
                               placeholder="https://www.example.com">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-calculator"></i> إعدادات الفواتير</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">العملة</label>
                            <select class="form-select" name="currency">
                                <option value="$" <?php echo ($settings['currency'] ?? 'ر.س') == '$' ? 'selected' : ''; ?>>دولار أمريكي ($)</option>
                                <option value="€" <?php echo ($settings['currency'] ?? 'ر.س') == '€' ? 'selected' : ''; ?>>يورو (€)</option>
                                <option value="£" <?php echo ($settings['currency'] ?? 'ر.س') == '£' ? 'selected' : ''; ?>>جنيه إسترليني (£)</option>
                                <option value="ر.س" <?php echo ($settings['currency'] ?? 'ر.س') == 'ر.س' ? 'selected' : ''; ?>>ريال سعودي (ر.س)</option>
                                <option value="د.إ" <?php echo ($settings['currency'] ?? 'ر.س') == 'د.إ' ? 'selected' : ''; ?>>درهم إماراتي (د.إ)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">نسبة الضريبة الافتراضية (%)</label>
                            <input type="number" step="0.01" class="form-control" name="tax_rate" 
                                   value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '0'); ?>">
                            <small class="text-muted">اتركها 0 إذا لم تكن هناك ضريبة</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">نص ختام الفاتورة</label>
                        <textarea class="form-control" name="invoice_footer" rows="3"><?php echo htmlspecialchars($settings['invoice_footer'] ?? 'شكراً لتعاملكم معنا'); ?></textarea>
                        <small class="text-muted">سيظهر في أسفل كل فاتورة</small>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-success btn-lg px-5">
                    <i class="bi bi-check-circle"></i> حفظ الإعدادات
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
