<?php
/**
 * Reports Class
 * إدارة التقارير والإحصائيات
 */

require_once __DIR__ . '/../config.php';

class Reports {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    // إحصائيات لوحة التحكم
    public function getDashboardStats() {
        $stats = [];
        
        // إجمالي المبيعات والأرباح والرسوم
        $result = $this->conn->query("SELECT SUM(total) as total_sales, SUM(profit) as total_profit, 
                                       SUM(total_fees) as total_fees, SUM(net_profit) as net_profit, 
                                       COUNT(*) as total_invoices FROM invoices");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stats['total_sales'] = floatval($row['total_sales']);
            $stats['total_profit'] = floatval($row['total_profit']);
            $stats['total_fees'] = floatval($row['total_fees']);
            $stats['net_profit'] = floatval($row['net_profit']);
            $stats['total_invoices'] = intval($row['total_invoices']);
        } else {
            $stats['total_sales'] = 0;
            $stats['total_profit'] = 0;
            $stats['total_fees'] = 0;
            $stats['net_profit'] = 0;
            $stats['total_invoices'] = 0;
        }
        
        // إجمالي المنتجات
        $result = $this->conn->query("SELECT COUNT(*) as total_products, SUM(stock_quantity) as total_stock FROM products");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stats['total_products'] = intval($row['total_products']);
            $stats['total_stock'] = intval($row['total_stock']);
        } else {
            $stats['total_products'] = 0;
            $stats['total_stock'] = 0;
        }
        
        // منتجات منخفضة المخزون
        $result = $this->conn->query("SELECT COUNT(*) as low_stock_count FROM products WHERE stock_quantity <= low_stock_alert");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stats['low_stock_count'] = intval($row['low_stock_count']);
        } else {
            $stats['low_stock_count'] = 0;
        }
        
        return $stats;
    }
    
    // مبيعات اليوم
    public function getTodaySales() {
        $result = $this->conn->query("SELECT SUM(total) as today_sales, SUM(profit) as today_profit, 
                                       SUM(total_fees) as today_fees, SUM(net_profit) as today_net_profit 
                                       FROM invoices 
                                       WHERE DATE(created_at) = CURDATE()");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return [
                'sales' => floatval($row['today_sales']),
                'profit' => floatval($row['today_profit']),
                'fees' => floatval($row['today_fees']),
                'net_profit' => floatval($row['today_net_profit'])
            ];
        }
        return ['sales' => 0, 'profit' => 0, 'fees' => 0, 'net_profit' => 0];
    }
    
    // مبيعات الشهر
    public function getMonthSales() {
        $result = $this->conn->query("SELECT SUM(total) as month_sales, SUM(profit) as month_profit, 
                                       SUM(total_fees) as month_fees, SUM(net_profit) as month_net_profit 
                                       FROM invoices 
                                       WHERE YEAR(created_at) = YEAR(CURDATE()) 
                                       AND MONTH(created_at) = MONTH(CURDATE())");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return [
                'sales' => floatval($row['month_sales']),
                'profit' => floatval($row['month_profit']),
                'fees' => floatval($row['month_fees']),
                'net_profit' => floatval($row['month_net_profit'])
            ];
        }
        return ['sales' => 0, 'profit' => 0, 'fees' => 0, 'net_profit' => 0];
    }
    
    // أفضل المنتجات مبيعاً
    public function getTopProducts($limit = 5) {
        $limit = intval($limit);
        $sql = "SELECT p.id, p.name, p.sku, SUM(ii.quantity) as total_sold, SUM(ii.profit) as total_profit
                FROM products p
                INNER JOIN invoice_items ii ON p.id = ii.product_id
                GROUP BY p.id
                ORDER BY total_sold DESC
                LIMIT $limit";
        
        $result = $this->conn->query($sql);
        $products = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        
        return $products;
    }
    
    // أحدث الفواتير
    public function getRecentInvoices($limit = 5) {
        $limit = intval($limit);
        $sql = "SELECT * FROM invoices ORDER BY created_at DESC LIMIT $limit";
        $result = $this->conn->query($sql);
        $invoices = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $invoices[] = $row;
            }
        }
        
        return $invoices;
    }
    
    // تقرير المبيعات حسب الفترة
    public function getSalesReport($start_date, $end_date) {
        $start_date = $this->db->cleanInput($start_date);
        $end_date = $this->db->cleanInput($end_date);
        
        $sql = "SELECT DATE(created_at) as date, 
                       COUNT(*) as invoice_count,
                       SUM(subtotal) as subtotal,
                       SUM(tax) as tax,
                       SUM(discount) as discount,
                       SUM(total) as total,
                       SUM(profit) as profit,
                       SUM(total_fees) as total_fees,
                       SUM(net_profit) as net_profit
                FROM invoices
                WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
        
        $result = $this->conn->query($sql);
        $report = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $report[] = $row;
            }
        }
        
        return $report;
    }
    
    // تقرير الأرباح حسب الفترة
    public function getProfitReport($start_date, $end_date) {
        $start_date = $this->db->cleanInput($start_date);
        $end_date = $this->db->cleanInput($end_date);
        
        $sql = "SELECT 
                    SUM(total) as total_sales,
                    SUM(profit) as total_profit,
                    SUM(total_fees) as total_fees,
                    SUM(net_profit) as net_profit,
                    COUNT(*) as total_invoices,
                    AVG(profit) as avg_profit,
                    AVG(net_profit) as avg_net_profit
                FROM invoices
                WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
        
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return [
            'total_sales' => 0,
            'total_profit' => 0,
            'total_fees' => 0,
            'net_profit' => 0,
            'total_invoices' => 0,
            'avg_profit' => 0,
            'avg_net_profit' => 0
        ];
    }
    
    // تقرير حركة المخزون
    public function getStockMovements($product_id = null, $limit = 50) {
        $limit = intval($limit);
        $where = $product_id ? "WHERE sm.product_id = " . intval($product_id) : "";
        
        $sql = "SELECT sm.*, p.name as product_name, p.sku
                FROM stock_movements sm
                INNER JOIN products p ON sm.product_id = p.id
                $where
                ORDER BY sm.created_at DESC
                LIMIT $limit";
        
        $result = $this->conn->query($sql);
        $movements = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $movements[] = $row;
            }
        }
        
        return $movements;
    }
    
    // تقرير المنتجات منخفضة المخزون
    public function getLowStockReport() {
        $sql = "SELECT * FROM products 
                WHERE stock_quantity <= low_stock_alert 
                ORDER BY stock_quantity ASC";
        
        $result = $this->conn->query($sql);
        $products = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        
        return $products;
    }
}
?>
