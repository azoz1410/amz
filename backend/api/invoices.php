<?php
/**
 * Invoices API Endpoint
 * نقطة النهاية لإدارة الفواتير
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Invoice.php';

$invoice = new Invoice();
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // جلب فاتورة واحدة
            $result = $invoice->getInvoice($_GET['id']);
            if ($result) {
                sendResponse($result);
            } else {
                sendResponse(['error' => 'الفاتورة غير موجودة'], 404);
            }
        } else {
            // جلب جميع الفواتير
            $search = $_GET['search'] ?? '';
            $result = $invoice->getAllInvoices($search);
            sendResponse($result);
        }
        break;
        
    case 'POST':
        // إنشاء فاتورة جديدة
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['customer_name'])) {
            sendResponse(['error' => 'اسم العميل مطلوب'], 400);
            break;
        }
        
        if (empty($data['items']) || !is_array($data['items'])) {
            sendResponse(['error' => 'يجب إضافة منتج واحد على الأقل'], 400);
            break;
        }
        
        $result = $invoice->createInvoice($data);
        
        if (isset($result['error'])) {
            sendResponse($result, 400);
        } else {
            sendResponse($result, 201);
        }
        break;
        
    case 'PUT':
        // تحديث فاتورة
        if (!isset($_GET['id'])) {
            sendResponse(['error' => 'معرف الفاتورة مطلوب'], 400);
            break;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $invoice->updateInvoice($_GET['id'], $data);
        
        if ($result === true) {
            sendResponse(['message' => 'تم تحديث الفاتورة بنجاح']);
        } elseif (isset($result['error'])) {
            sendResponse($result, 400);
        } else {
            sendResponse(['error' => 'فشل تحديث الفاتورة'], 500);
        }
        break;
        
    case 'DELETE':
        // حذف فاتورة
        if (!isset($_GET['id'])) {
            sendResponse(['error' => 'معرف الفاتورة مطلوب'], 400);
            break;
        }
        
        $result = $invoice->deleteInvoice($_GET['id']);
        
        if ($result === true) {
            sendResponse(['message' => 'تم حذف الفاتورة بنجاح']);
        } elseif (isset($result['error'])) {
            sendResponse($result, 400);
        } else {
            sendResponse(['error' => 'فشل حذف الفاتورة'], 500);
        }
        break;
        
    default:
        sendResponse(['error' => 'طريقة غير مدعومة'], 405);
        break;
}
?>
