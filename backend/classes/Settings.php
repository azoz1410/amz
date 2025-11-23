<?php
/**
 * Settings Class
 * إدارة الإعدادات
 */

require_once __DIR__ . '/../config.php';

class Settings {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    // جلب جميع الإعدادات
    public function getAllSettings() {
        $settings = [];
        $result = $this->conn->query("SELECT setting_key, setting_value FROM settings");
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        
        return $settings;
    }
    
    // جلب إعداد واحد
    public function getSetting($key, $default = '') {
        $key = $this->db->cleanInput($key);
        $sql = "SELECT setting_value FROM settings WHERE setting_key = '$key'";
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['setting_value'];
        }
        return $default;
    }
    
    // حفظ إعداد
    public function saveSetting($key, $value) {
        $key = $this->db->cleanInput($key);
        $value = $this->db->cleanInput($value);
        
        $sql = "INSERT INTO settings (setting_key, setting_value) 
                VALUES ('$key', '$value') 
                ON DUPLICATE KEY UPDATE setting_value = '$value'";
        
        return $this->conn->query($sql);
    }
    
    // حفظ عدة إعدادات
    public function saveSettings($settings) {
        $success = true;
        foreach ($settings as $key => $value) {
            if (!$this->saveSetting($key, $value)) {
                $success = false;
            }
        }
        return $success;
    }
}
?>
