<?php
/**
 * Product Class
 * إدارة المنتجات والمخزون
 */

require_once __DIR__ . '/../config.php';

class Product {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    // جلب جميع المنتجات
    public function getAllProducts() {
        $sql = "SELECT * FROM products ORDER BY id DESC";
        $result = $this->conn->query($sql);
        
        if ($result) {
            $products = [];
            while ($row = $result->fetch_assoc()) {
                $row['profit_per_unit'] = $row['selling_price'] - $row['purchase_price'];
                // حساب صافي الربح بعد الرسوم
                $amazon_fee = ($row['selling_price'] * $row['amazon_fee_percent']) / 100;
                $total_fees = $amazon_fee + $row['default_shipping_fee'] + $row['default_storage_fee'];
                $row['net_profit_per_unit'] = $row['profit_per_unit'] - $total_fees;
                $products[] = $row;
            }
            return $products;
        }
        return [];
    }
    
    // جلب منتج واحد
    public function getProduct($id) {
        $id = intval($id);
        $sql = "SELECT * FROM products WHERE id = $id";
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $product = $result->fetch_assoc();
            $product['profit_per_unit'] = $product['selling_price'] - $product['purchase_price'];
            // حساب صافي الربح بعد الرسوم
            $amazon_fee = ($product['selling_price'] * $product['amazon_fee_percent']) / 100;
            $total_fees = $amazon_fee + $product['default_shipping_fee'] + $product['default_storage_fee'];
            $product['net_profit_per_unit'] = $product['profit_per_unit'] - $total_fees;
            return $product;
        }
        return null;
    }
    
    // جلب المنتجات المتوفرة فقط
    public function getAvailableProducts() {
        $sql = "SELECT * FROM products WHERE stock_quantity > 0 ORDER BY name";
        $result = $this->conn->query($sql);
        
        if ($result) {
            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            return $products;
        }
        return [];
    }
    
    // إضافة منتج جديد
    public function createProduct($data) {
        $name = $this->db->cleanInput($data['name']);
        $description = $this->db->cleanInput($data['description'] ?? '');
        $sku = $this->db->cleanInput($data['sku']);
        $purchase_price = floatval($data['purchase_price']);
        $selling_price = floatval($data['selling_price']);
        $stock_quantity = intval($data['stock_quantity']);
        $min_stock_level = intval($data['min_stock_level'] ?? 5);
        $amazon_fee_percent = floatval($data['amazon_fee_percent'] ?? 0);
        $default_shipping_fee = floatval($data['default_shipping_fee'] ?? 0);
        $default_storage_fee = floatval($data['default_storage_fee'] ?? 0);
        
        $sql = "INSERT INTO products (name, description, sku, purchase_price, selling_price, 
                amazon_fee_percent, default_shipping_fee, default_storage_fee, stock_quantity, min_stock_level) 
                VALUES ('$name', '$description', '$sku', $purchase_price, $selling_price, 
                $amazon_fee_percent, $default_shipping_fee, $default_storage_fee, $stock_quantity, $min_stock_level)";
        
        if ($this->conn->query($sql)) {
            $product_id = $this->conn->insert_id;
            
            // تسجيل حركة المخزون
            $this->logStockMovement($product_id, $stock_quantity, 'in', 'purchase', null, 'إضافة مخزون أولي');
            
            return ['id' => $product_id];
        }
        
        return null;
    }
    
    // تحديث منتج
    public function updateProduct($id, $data) {
        $id = intval($id);
        $old_product = $this->getProduct($id);
        
        if (!$old_product) {
            return false;
        }
        
        $name = $this->db->cleanInput($data['name']);
        $description = $this->db->cleanInput($data['description'] ?? '');
        $sku = $this->db->cleanInput($data['sku']);
        $purchase_price = floatval($data['purchase_price']);
        $selling_price = floatval($data['selling_price']);
        $stock_quantity = intval($data['stock_quantity']);
        $min_stock_level = intval($data['min_stock_level'] ?? 5);
        $amazon_fee_percent = floatval($data['amazon_fee_percent'] ?? 0);
        $default_shipping_fee = floatval($data['default_shipping_fee'] ?? 0);
        $default_storage_fee = floatval($data['default_storage_fee'] ?? 0);
        
        $sql = "UPDATE products SET 
                name = '$name', 
                description = '$description', 
                sku = '$sku', 
                purchase_price = $purchase_price, 
                selling_price = $selling_price, 
                amazon_fee_percent = $amazon_fee_percent,
                default_shipping_fee = $default_shipping_fee,
                default_storage_fee = $default_storage_fee,
                stock_quantity = $stock_quantity,
                min_stock_level = $min_stock_level 
                WHERE id = $id";
        
        if ($this->conn->query($sql)) {
            // تسجيل التغيير في المخزون
            $quantity_diff = $stock_quantity - $old_product['stock_quantity'];
            if ($quantity_diff != 0) {
                $this->logStockMovement($id, abs($quantity_diff), 
                    $quantity_diff > 0 ? 'in' : 'out', 
                    'adjustment', null, 'تعديل المخزون يدوياً');
            }
            return true;
        }
        
        return false;
    }
    
    // حذف منتج
    public function deleteProduct($id) {
        $id = intval($id);
        
        // التحقق من عدم وجود فواتير مرتبطة
        $check = $this->conn->query("SELECT COUNT(*) as count FROM invoice_items WHERE product_id = $id");
        $row = $check->fetch_assoc();
        
        if ($row['count'] > 0) {
            return ['error' => 'لا يمكن حذف المنتج لوجود فواتير مرتبطة به'];
        }
        
        if ($this->conn->query("DELETE FROM products WHERE id = $id")) {
            return true;
        }
        
        return false;
    }
    
    // تحديث المخزون
    public function updateStock($product_id, $quantity, $type = 'out') {
        $product_id = intval($product_id);
        $quantity = intval($quantity);
        
        if ($type == 'out') {
            $sql = "UPDATE products SET stock_quantity = stock_quantity - $quantity WHERE id = $product_id";
        } else {
            $sql = "UPDATE products SET stock_quantity = stock_quantity + $quantity WHERE id = $product_id";
        }
        
        return $this->conn->query($sql);
    }
    
    // تسجيل حركة المخزون
    private function logStockMovement($product_id, $quantity, $type, $reference_type, $reference_id = null, $notes = '') {
        $notes = $this->db->cleanInput($notes);
        $sql = "INSERT INTO stock_movements (product_id, quantity, type, reference_type, reference_id, notes) 
                VALUES ($product_id, $quantity, '$type', '$reference_type', " . 
                ($reference_id ? $reference_id : 'NULL') . ", '$notes')";
        
        return $this->conn->query($sql);
    }
    
    // جلب المنتجات قليلة المخزون
    public function getLowStockProducts() {
        $sql = "SELECT * FROM products WHERE stock_quantity <= min_stock_level ORDER BY stock_quantity ASC";
        $result = $this->conn->query($sql);
        
        if ($result) {
            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            return $products;
        }
        return [];
    }
}
?>
