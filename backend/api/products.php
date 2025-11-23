<?php
/**
 * Products API Endpoint
 * نقطة النهاية لإدارة المنتجات
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Product.php';

$product = new Product();
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // جلب منتج واحد
            $result = $product->getProduct($_GET['id']);
            if ($result) {
                sendResponse($result);
            } else {
                sendResponse(['error' => 'المنتج غير موجود'], 404);
            }
        } elseif (isset($_GET['available'])) {
            // جلب المنتجات المتاحة فقط
            $result = $product->getAvailableProducts();
            sendResponse($result);
        } elseif (isset($_GET['low_stock'])) {
            // جلب المنتجات منخفضة المخزون
            $result = $product->getLowStockProducts();
            sendResponse($result);
        } else {
            // جلب جميع المنتجات
            $search = $_GET['search'] ?? '';
            $result = $product->getAllProducts($search);
            sendResponse($result);
        }
        break;
        
    case 'POST':
        // إنشاء منتج جديد
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['name']) || empty($data['sku'])) {
            sendResponse(['error' => 'الاسم ورمز SKU مطلوبان'], 400);
            break;
        }
        
        $result = $product->createProduct($data);
        
        if (isset($result['error'])) {
            sendResponse($result, 400);
        } else {
            sendResponse(['id' => $result, 'message' => 'تم إضافة المنتج بنجاح'], 201);
        }
        break;
        
    case 'PUT':
        // تحديث منتج
        if (!isset($_GET['id'])) {
            sendResponse(['error' => 'معرف المنتج مطلوب'], 400);
            break;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $product->updateProduct($_GET['id'], $data);
        
        if ($result === true) {
            sendResponse(['message' => 'تم تحديث المنتج بنجاح']);
        } elseif (isset($result['error'])) {
            sendResponse($result, 400);
        } else {
            sendResponse(['error' => 'فشل تحديث المنتج'], 500);
        }
        break;
        
    case 'DELETE':
        // حذف منتج
        if (!isset($_GET['id'])) {
            sendResponse(['error' => 'معرف المنتج مطلوب'], 400);
            break;
        }
        
        $result = $product->deleteProduct($_GET['id']);
        
        if ($result === true) {
            sendResponse(['message' => 'تم حذف المنتج بنجاح']);
        } elseif (isset($result['error'])) {
            sendResponse($result, 400);
        } else {
            sendResponse(['error' => 'فشل حذف المنتج'], 500);
        }
        break;
        
    default:
        sendResponse(['error' => 'طريقة غير مدعومة'], 405);
        break;
}
?>
