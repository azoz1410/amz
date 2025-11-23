<?php
/**
 * Settings API Endpoint
 * نقطة النهاية لإدارة الإعدادات
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Settings.php';

$settings = new Settings();
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if (isset($_GET['key'])) {
            // جلب إعداد واحد
            $value = $settings->getSetting($_GET['key']);
            sendResponse(['key' => $_GET['key'], 'value' => $value]);
        } else {
            // جلب جميع الإعدادات
            $result = $settings->getAllSettings();
            sendResponse($result);
        }
        break;
        
    case 'POST':
    case 'PUT':
        // حفظ الإعدادات
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data)) {
            sendResponse(['error' => 'لا توجد بيانات للحفظ'], 400);
            break;
        }
        
        // إذا كان هناك مفتاح واحد
        if (isset($data['key']) && isset($data['value'])) {
            $result = $settings->saveSetting($data['key'], $data['value']);
            if ($result) {
                sendResponse(['message' => 'تم حفظ الإعداد بنجاح']);
            } else {
                sendResponse(['error' => 'فشل حفظ الإعداد'], 500);
            }
        } 
        // إذا كان هناك عدة إعدادات
        else {
            $result = $settings->saveSettings($data);
            if ($result) {
                sendResponse(['message' => 'تم حفظ الإعدادات بنجاح']);
            } else {
                sendResponse(['error' => 'فشل حفظ الإعدادات'], 500);
            }
        }
        break;
        
    default:
        sendResponse(['error' => 'طريقة غير مدعومة'], 405);
        break;
}
?>
