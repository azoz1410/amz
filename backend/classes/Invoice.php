<?php
/**
 * Invoice Class
 * إدارة الفواتير
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Product.php';

class Invoice {
    private $db;
    private $conn;
    private $productClass;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
        $this->productClass = new Product();
    }
    
    // توليد رقم فاتورة فريد
    public function generateInvoiceNumber() {
        $prefix = 'INV-' . date('Y') . date('m');
        
        $sql = "SELECT invoice_number FROM invoices WHERE invoice_number LIKE '$prefix%' ORDER BY id DESC LIMIT 1";
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $last_number = intval(substr($row['invoice_number'], -4));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }
        
        return $prefix . '-' . str_pad($new_number, 4, '0', STR_PAD_LEFT);
    }
    
    // جلب جميع الفواتير
    public function getAllInvoices($search = '') {
        $where = '';
        if (!empty($search)) {
            $search = $this->db->cleanInput($search);
            $where = "WHERE invoice_number LIKE '%$search%' OR customer_name LIKE '%$search%' OR customer_email LIKE '%$search%'";
        }
        
        $sql = "SELECT * FROM invoices $where ORDER BY id DESC";
        $result = $this->conn->query($sql);
        
        if ($result) {
            $invoices = [];
            while ($row = $result->fetch_assoc()) {
                $invoices[] = $row;
            }
            return $invoices;
        }
        return [];
    }
    
    // جلب فاتورة واحدة
    public function getInvoice($id) {
        $id = intval($id);
        $sql = "SELECT * FROM invoices WHERE id = $id";
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $invoice = $result->fetch_assoc();
            
            // جلب تفاصيل الفاتورة
            $items_sql = "SELECT * FROM invoice_items WHERE invoice_id = $id";
            $items_result = $this->conn->query($items_sql);
            
            $items = [];
            if ($items_result) {
                while ($item = $items_result->fetch_assoc()) {
                    $items[] = $item;
                }
            }
            
            $invoice['items'] = $items;
            return $invoice;
        }
        return null;
    }
    
    // إنشاء فاتورة جديدة
    public function createInvoice($data) {
        $customer_name = $this->db->cleanInput($data['customer_name']);
        $customer_email = $this->db->cleanInput($data['customer_email'] ?? '');
        $customer_phone = $this->db->cleanInput($data['customer_phone'] ?? '');
        $customer_address = $this->db->cleanInput($data['customer_address'] ?? '');
        $discount = floatval($data['discount'] ?? 0);
        $tax = floatval($data['tax'] ?? 0);
        $notes = $this->db->cleanInput($data['notes'] ?? '');
        $items = $data['items'] ?? [];
        
        if (empty($items)) {
            return ['error' => 'يجب إضافة منتج واحد على الأقل للفاتورة'];
        }
        
        // التحقق من توفر المخزون
        foreach ($items as $item) {
            $product = $this->productClass->getProduct($item['product_id']);
            if (!$product || $product['stock_quantity'] < $item['quantity']) {
                return ['error' => 'المخزون غير كافي للمنتج: ' . ($product ? $product['name'] : 'غير موجود')];
            }
        }
        
        // حساب الإجماليات
        $subtotal = 0;
        $total_profit = 0;
        $total_fees = 0;
        $net_profit = 0;
        
        foreach ($items as &$item) {
            // إذا لم يتم تمرير الرسوم، استخدم القيم الافتراضية من المنتج
            $product = $this->productClass->getProduct($item['product_id']);
            
            if (!isset($item['amazon_fee'])) {
                $item['amazon_fee'] = ($item['unit_price'] * $product['amazon_fee_percent']) / 100;
            }
            if (!isset($item['shipping_fee'])) {
                $item['shipping_fee'] = floatval($product['default_shipping_fee']) * $item['quantity'];
            }
            if (!isset($item['storage_fee'])) {
                $item['storage_fee'] = floatval($product['default_storage_fee']) * $item['quantity'];
            }
            
            $item['total_fees'] = $item['amazon_fee'] + $item['shipping_fee'] + $item['storage_fee'];
            $item_profit = ($item['unit_price'] - $item['purchase_price']) * $item['quantity'];
            $item['net_profit'] = $item_profit - $item['total_fees'];
            
            $subtotal += $item['subtotal'];
            $total_profit += $item_profit;
            $total_fees += $item['total_fees'];
            $net_profit += $item['net_profit'];
        }
        
        $total = $subtotal - $discount + $tax;
        
        // إنشاء الفاتورة
        $invoice_number = $this->generateInvoiceNumber();
        
        $sql = "INSERT INTO invoices (invoice_number, customer_name, customer_email, customer_phone, customer_address, 
                subtotal, tax, discount, total, profit, total_fees, net_profit, notes, status) 
                VALUES ('$invoice_number', '$customer_name', '$customer_email', '$customer_phone', '$customer_address', 
                $subtotal, $tax, $discount, $total, $total_profit, $total_fees, $net_profit, '$notes', 'paid')";
        
        if ($this->conn->query($sql)) {
            $invoice_id = $this->conn->insert_id;
            
            // إضافة تفاصيل الفاتورة وتحديث المخزون
            foreach ($items as $item) {
                $product_id = intval($item['product_id']);
                $quantity = intval($item['quantity']);
                $unit_price = floatval($item['unit_price']);
                $purchase_price = floatval($item['purchase_price']);
                $item_subtotal = floatval($item['subtotal']);
                $item_profit = ($unit_price - $purchase_price) * $quantity;
                $product_name = $this->db->cleanInput($item['product_name']);
                $amazon_fee = floatval($item['amazon_fee']);
                $shipping_fee = floatval($item['shipping_fee']);
                $storage_fee = floatval($item['storage_fee']);
                $item_total_fees = floatval($item['total_fees']);
                $item_net_profit = floatval($item['net_profit']);
                
                $sql_item = "INSERT INTO invoice_items (invoice_id, product_id, product_name, quantity, 
                            purchase_price, unit_price, subtotal, profit, amazon_fee, shipping_fee, storage_fee, 
                            total_fees, net_profit) 
                            VALUES ($invoice_id, $product_id, '$product_name', $quantity, 
                            $purchase_price, $unit_price, $item_subtotal, $item_profit, $amazon_fee, $shipping_fee, 
                            $storage_fee, $item_total_fees, $item_net_profit)";
                
                if ($this->conn->query($sql_item)) {
                    $this->productClass->updateStock($product_id, $quantity, 'out');
                    $this->logStockMovement($product_id, $quantity, 'out', 'sale', $invoice_id, 'بيع - فاتورة ' . $invoice_number);
                }
            }
            
            return ['id' => $invoice_id, 'invoice_number' => $invoice_number];
        }
        
        return ['error' => 'حدث خطأ أثناء إنشاء الفاتورة'];
    }
    
    // تحديث فاتورة
    public function updateInvoice($id, $data) {
        $id = intval($id);
        $invoice = $this->getInvoice($id);
        
        if (!$invoice) {
            return ['error' => 'الفاتورة غير موجودة'];
        }
        
        // إرجاع المنتجات القديمة للمخزون
        foreach ($invoice['items'] as $old_item) {
            $this->productClass->updateStock($old_item['product_id'], $old_item['quantity'], 'in');
        }
        
        $customer_name = $this->db->cleanInput($data['customer_name']);
        $customer_email = $this->db->cleanInput($data['customer_email'] ?? '');
        $customer_phone = $this->db->cleanInput($data['customer_phone'] ?? '');
        $customer_address = $this->db->cleanInput($data['customer_address'] ?? '');
        $discount = floatval($data['discount'] ?? 0);
        $tax = floatval($data['tax'] ?? 0);
        $notes = $this->db->cleanInput($data['notes'] ?? '');
        $status = $this->db->cleanInput($data['status'] ?? 'paid');
        $items = $data['items'] ?? [];
        
        // التحقق من توفر المخزون
        foreach ($items as $item) {
            $product = $this->productClass->getProduct($item['product_id']);
            if (!$product || $product['stock_quantity'] < $item['quantity']) {
                // إعادة المنتجات القديمة
                foreach ($invoice['items'] as $old_item) {
                    $this->productClass->updateStock($old_item['product_id'], $old_item['quantity'], 'out');
                }
                return ['error' => 'المخزون غير كافي للمنتج: ' . ($product ? $product['name'] : 'غير موجود')];
            }
        }
        
        // حساب الإجماليات
        $subtotal = 0;
        $total_profit = 0;
        $total_fees = 0;
        $net_profit = 0;
        
        foreach ($items as &$item) {
            // إذا لم يتم تمرير الرسوم، استخدم القيم الافتراضية من المنتج
            $product = $this->productClass->getProduct($item['product_id']);
            
            if (!isset($item['amazon_fee'])) {
                $item['amazon_fee'] = ($item['unit_price'] * $product['amazon_fee_percent']) / 100;
            }
            if (!isset($item['shipping_fee'])) {
                $item['shipping_fee'] = floatval($product['default_shipping_fee']) * $item['quantity'];
            }
            if (!isset($item['storage_fee'])) {
                $item['storage_fee'] = floatval($product['default_storage_fee']) * $item['quantity'];
            }
            
            $item['total_fees'] = $item['amazon_fee'] + $item['shipping_fee'] + $item['storage_fee'];
            $item_profit = ($item['unit_price'] - $item['purchase_price']) * $item['quantity'];
            $item['net_profit'] = $item_profit - $item['total_fees'];
            
            $subtotal += $item['subtotal'];
            $total_profit += $item_profit;
            $total_fees += $item['total_fees'];
            $net_profit += $item['net_profit'];
        }
        
        $total = $subtotal - $discount + $tax;
        
        // تحديث الفاتورة
        $sql = "UPDATE invoices SET 
                customer_name = '$customer_name',
                customer_email = '$customer_email',
                customer_phone = '$customer_phone',
                customer_address = '$customer_address',
                subtotal = $subtotal,
                tax = $tax,
                discount = $discount,
                total = $total,
                profit = $total_profit,
                total_fees = $total_fees,
                net_profit = $net_profit,
                notes = '$notes',
                status = '$status'
                WHERE id = $id";
        
        if ($this->conn->query($sql)) {
            // حذف التفاصيل القديمة
            $this->conn->query("DELETE FROM invoice_items WHERE invoice_id = $id");
            
            // إضافة التفاصيل الجديدة
            foreach ($items as $item) {
                $product_id = intval($item['product_id']);
                $quantity = intval($item['quantity']);
                $unit_price = floatval($item['unit_price']);
                $purchase_price = floatval($item['purchase_price']);
                $item_subtotal = floatval($item['subtotal']);
                $item_profit = ($unit_price - $purchase_price) * $quantity;
                $product_name = $this->db->cleanInput($item['product_name']);
                $amazon_fee = floatval($item['amazon_fee']);
                $shipping_fee = floatval($item['shipping_fee']);
                $storage_fee = floatval($item['storage_fee']);
                $item_total_fees = floatval($item['total_fees']);
                $item_net_profit = floatval($item['net_profit']);
                
                $sql_item = "INSERT INTO invoice_items (invoice_id, product_id, product_name, quantity, 
                            purchase_price, unit_price, subtotal, profit, amazon_fee, shipping_fee, storage_fee, 
                            total_fees, net_profit) 
                            VALUES ($id, $product_id, '$product_name', $quantity, 
                            $purchase_price, $unit_price, $item_subtotal, $item_profit, $amazon_fee, $shipping_fee, 
                            $storage_fee, $item_total_fees, $item_net_profit)";
                
                if ($this->conn->query($sql_item)) {
                    $this->productClass->updateStock($product_id, $quantity, 'out');
                    $this->logStockMovement($product_id, $quantity, 'out', 'sale', $id, 'تحديث فاتورة ' . $invoice['invoice_number']);
                }
            }
            
            return true;
        }
        
        return ['error' => 'حدث خطأ أثناء تحديث الفاتورة'];
    }
    
    // حذف فاتورة
    public function deleteInvoice($id) {
        $id = intval($id);
        $invoice = $this->getInvoice($id);
        
        if (!$invoice) {
            return ['error' => 'الفاتورة غير موجودة'];
        }
        
        // إرجاع المنتجات للمخزون
        foreach ($invoice['items'] as $item) {
            $this->productClass->updateStock($item['product_id'], $item['quantity'], 'in');
            $this->logStockMovement($item['product_id'], $item['quantity'], 'in', 'return', $id, 
                'إرجاع - حذف فاتورة ' . $invoice['invoice_number']);
        }
        
        // حذف الفاتورة
        if ($this->conn->query("DELETE FROM invoices WHERE id = $id")) {
            return true;
        }
        
        return ['error' => 'حدث خطأ أثناء حذف الفاتورة'];
    }
    
    // تسجيل حركة المخزون
    private function logStockMovement($product_id, $quantity, $type, $reference_type, $reference_id = null, $notes = '') {
        $notes = $this->db->cleanInput($notes);
        $sql = "INSERT INTO stock_movements (product_id, quantity, type, reference_type, reference_id, notes) 
                VALUES ($product_id, $quantity, '$type', '$reference_type', " . 
                ($reference_id ? $reference_id : 'NULL') . ", '$notes')";
        
        return $this->conn->query($sql);
    }
}
?>
