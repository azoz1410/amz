<?php
/**
 * Reports API Endpoint
 * نقطة النهاية للتقارير والإحصائيات
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Reports.php';

$reports = new Reports();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    sendResponse(['error' => 'طريقة غير مدعومة'], 405);
    exit;
}

$type = $_GET['type'] ?? 'dashboard';

switch($type) {
    case 'dashboard':
        // إحصائيات لوحة التحكم
        $result = $reports->getDashboardStats();
        sendResponse($result);
        break;
        
    case 'today':
        // مبيعات اليوم
        $result = $reports->getTodaySales();
        sendResponse($result);
        break;
        
    case 'month':
        // مبيعات الشهر
        $result = $reports->getMonthSales();
        sendResponse($result);
        break;
        
    case 'top_products':
        // أفضل المنتجات
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
        $result = $reports->getTopProducts($limit);
        sendResponse($result);
        break;
        
    case 'recent_invoices':
        // أحدث الفواتير
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
        $result = $reports->getRecentInvoices($limit);
        sendResponse($result);
        break;
        
    case 'sales':
        // تقرير المبيعات
        if (!isset($_GET['start_date']) || !isset($_GET['end_date'])) {
            sendResponse(['error' => 'تاريخ البداية والنهاية مطلوبان'], 400);
            break;
        }
        $result = $reports->getSalesReport($_GET['start_date'], $_GET['end_date']);
        sendResponse($result);
        break;
        
    case 'profit':
        // تقرير الأرباح
        if (!isset($_GET['start_date']) || !isset($_GET['end_date'])) {
            sendResponse(['error' => 'تاريخ البداية والنهاية مطلوبان'], 400);
            break;
        }
        $result = $reports->getProfitReport($_GET['start_date'], $_GET['end_date']);
        sendResponse($result);
        break;
        
    case 'stock_movements':
        // حركة المخزون
        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        $result = $reports->getStockMovements($product_id, $limit);
        sendResponse($result);
        break;
        
    case 'low_stock':
        // منتجات منخفضة المخزون
        $result = $reports->getLowStockReport();
        sendResponse($result);
        break;
        
    default:
        sendResponse(['error' => 'نوع التقرير غير صحيح'], 400);
        break;
}
?>
