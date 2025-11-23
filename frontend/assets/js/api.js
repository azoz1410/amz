/**
 * API Module
 * وحدة التواصل مع الـ API
 */

const API_BASE_URL = "http://localhost/AMZ/backend/api";

class API {
  // معالج الأخطاء
  static handleError(error) {
    console.error("API Error:", error);
    if (error.message) {
      showAlert(error.message, "danger");
    } else {
      showAlert("حدث خطأ في الاتصال بالخادم", "danger");
    }
  }

  // طلب عام
  static async request(endpoint, options = {}) {
    try {
      const response = await fetch(`${API_BASE_URL}/${endpoint}`, {
        ...options,
        headers: {
          "Content-Type": "application/json",
          ...options.headers,
        },
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || "حدث خطأ");
      }

      return data;
    } catch (error) {
      this.handleError(error);
      throw error;
    }
  }

  // المنتجات
  static async getProducts(search = "") {
    const query = search ? `?search=${encodeURIComponent(search)}` : "";
    return this.request(`products.php${query}`);
  }

  static async getProduct(id) {
    return this.request(`products.php?id=${id}`);
  }

  static async getAvailableProducts() {
    return this.request("products.php?available=1");
  }

  static async getLowStockProducts() {
    return this.request("products.php?low_stock=1");
  }

  static async createProduct(data) {
    return this.request("products.php", {
      method: "POST",
      body: JSON.stringify(data),
    });
  }

  static async updateProduct(id, data) {
    return this.request(`products.php?id=${id}`, {
      method: "PUT",
      body: JSON.stringify(data),
    });
  }

  static async deleteProduct(id) {
    return this.request(`products.php?id=${id}`, {
      method: "DELETE",
    });
  }

  // الفواتير
  static async getInvoices(search = "") {
    const query = search ? `?search=${encodeURIComponent(search)}` : "";
    return this.request(`invoices.php${query}`);
  }

  static async getInvoice(id) {
    return this.request(`invoices.php?id=${id}`);
  }

  static async createInvoice(data) {
    return this.request("invoices.php", {
      method: "POST",
      body: JSON.stringify(data),
    });
  }

  static async updateInvoice(id, data) {
    return this.request(`invoices.php?id=${id}`, {
      method: "PUT",
      body: JSON.stringify(data),
    });
  }

  static async deleteInvoice(id) {
    return this.request(`invoices.php?id=${id}`, {
      method: "DELETE",
    });
  }

  // الإعدادات
  static async getSettings() {
    return this.request("settings.php");
  }

  static async getSetting(key) {
    return this.request(`settings.php?key=${key}`);
  }

  static async saveSettings(data) {
    return this.request("settings.php", {
      method: "POST",
      body: JSON.stringify(data),
    });
  }

  // التقارير
  static async getDashboardStats() {
    return this.request("reports.php?type=dashboard");
  }

  static async getTodaySales() {
    return this.request("reports.php?type=today");
  }

  static async getMonthSales() {
    return this.request("reports.php?type=month");
  }

  static async getTopProducts(limit = 5) {
    return this.request(`reports.php?type=top_products&limit=${limit}`);
  }

  static async getRecentInvoices(limit = 5) {
    return this.request(`reports.php?type=recent_invoices&limit=${limit}`);
  }

  static async getSalesReport(startDate, endDate) {
    return this.request(
      `reports.php?type=sales&start_date=${startDate}&end_date=${endDate}`
    );
  }

  static async getProfitReport(startDate, endDate) {
    return this.request(
      `reports.php?type=profit&start_date=${startDate}&end_date=${endDate}`
    );
  }

  static async getStockMovements(productId = null, limit = 50) {
    const query = productId ? `&product_id=${productId}` : "";
    return this.request(
      `reports.php?type=stock_movements${query}&limit=${limit}`
    );
  }

  static async getLowStockReport() {
    return this.request("reports.php?type=low_stock");
  }
}
