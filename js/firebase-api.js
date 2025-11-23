// Firebase API Layer - طبقة API للتعامل مع Firebase

class FirebaseAPI {
  // ============= المنتجات (Products) =============

  static async getAllProducts() {
    try {
      const snapshot = await collections.products
        .orderBy("created_at", "desc")
        .get();
      return snapshot.docs.map((doc) => ({
        id: doc.id,
        ...doc.data(),
      }));
    } catch (error) {
      console.error("خطأ في جلب المنتجات:", error);
      throw error;
    }
  }

  static async getProduct(id) {
    try {
      const doc = await collections.products.doc(id).get();
      if (doc.exists) {
        return { id: doc.id, ...doc.data() };
      }
      return null;
    } catch (error) {
      console.error("خطأ في جلب المنتج:", error);
      throw error;
    }
  }

  static async addProduct(productData) {
    try {
      const docRef = await collections.products.add({
        ...productData,
        created_at: firebase.firestore.FieldValue.serverTimestamp(),
        updated_at: firebase.firestore.FieldValue.serverTimestamp(),
      });
      return docRef.id;
    } catch (error) {
      console.error("خطأ في إضافة المنتج:", error);
      throw error;
    }
  }

  static async updateProduct(id, productData) {
    try {
      await collections.products.doc(id).update({
        ...productData,
        updated_at: firebase.firestore.FieldValue.serverTimestamp(),
      });
      return true;
    } catch (error) {
      console.error("خطأ في تحديث المنتج:", error);
      throw error;
    }
  }

  static async deleteProduct(id) {
    try {
      await collections.products.doc(id).delete();
      return true;
    } catch (error) {
      console.error("خطأ في حذف المنتج:", error);
      throw error;
    }
  }

  static async getLowStockProducts() {
    try {
      const snapshot = await collections.products
        .where(
          "stock_quantity",
          "<=",
          firebase.firestore.FieldPath.documentId()
        )
        .get();

      // فلترة المنتجات التي stock_quantity <= min_stock_level
      const products = snapshot.docs
        .map((doc) => ({
          id: doc.id,
          ...doc.data(),
        }))
        .filter((p) => p.stock_quantity <= p.min_stock_level);

      return products;
    } catch (error) {
      console.error("خطأ في جلب المنتجات منخفضة المخزون:", error);
      throw error;
    }
  }

  // ============= الفواتير (Invoices) =============

  static async getAllInvoices() {
    try {
      const snapshot = await collections.invoices
        .orderBy("created_at", "desc")
        .get();
      return snapshot.docs.map((doc) => ({
        id: doc.id,
        ...doc.data(),
      }));
    } catch (error) {
      console.error("خطأ في جلب الفواتير:", error);
      throw error;
    }
  }

  static async getInvoice(id) {
    try {
      const doc = await collections.invoices.doc(id).get();
      if (doc.exists) {
        // جلب عناصر الفاتورة
        const itemsSnapshot = await collections.invoiceItems
          .where("invoice_id", "==", id)
          .get();

        const items = itemsSnapshot.docs.map((doc) => ({
          id: doc.id,
          ...doc.data(),
        }));

        return {
          id: doc.id,
          ...doc.data(),
          items: items,
        };
      }
      return null;
    } catch (error) {
      console.error("خطأ في جلب الفاتورة:", error);
      throw error;
    }
  }

  static async addInvoice(invoiceData, items) {
    try {
      // إنشاء رقم الفاتورة
      const invoiceNumber = "INV-" + Date.now();

      // إضافة الفاتورة
      const invoiceRef = await collections.invoices.add({
        ...invoiceData,
        invoice_number: invoiceNumber,
        created_at: firebase.firestore.FieldValue.serverTimestamp(),
        updated_at: firebase.firestore.FieldValue.serverTimestamp(),
      });

      // إضافة عناصر الفاتورة
      const batch = db.batch();

      for (const item of items) {
        const itemRef = collections.invoiceItems.doc();
        batch.set(itemRef, {
          ...item,
          invoice_id: invoiceRef.id,
          created_at: firebase.firestore.FieldValue.serverTimestamp(),
        });

        // تحديث المخزون
        const productRef = collections.products.doc(item.product_id);
        batch.update(productRef, {
          stock_quantity: firebase.firestore.FieldValue.increment(
            -item.quantity
          ),
        });
      }

      await batch.commit();
      return invoiceRef.id;
    } catch (error) {
      console.error("خطأ في إضافة الفاتورة:", error);
      throw error;
    }
  }

  static async updateInvoice(id, invoiceData) {
    try {
      await collections.invoices.doc(id).update({
        ...invoiceData,
        updated_at: firebase.firestore.FieldValue.serverTimestamp(),
      });
      return true;
    } catch (error) {
      console.error("خطأ في تحديث الفاتورة:", error);
      throw error;
    }
  }

  static async deleteInvoice(id) {
    try {
      // حذف عناصر الفاتورة أولاً
      const itemsSnapshot = await collections.invoiceItems
        .where("invoice_id", "==", id)
        .get();

      const batch = db.batch();

      itemsSnapshot.docs.forEach((doc) => {
        batch.delete(doc.ref);
      });

      // حذف الفاتورة
      batch.delete(collections.invoices.doc(id));

      await batch.commit();
      return true;
    } catch (error) {
      console.error("خطأ في حذف الفاتورة:", error);
      throw error;
    }
  }

  // ============= الإعدادات (Settings) =============

  static async getAllSettings() {
    try {
      const snapshot = await collections.settings.get();
      const settings = {};
      snapshot.docs.forEach((doc) => {
        settings[doc.data().setting_key] = doc.data().setting_value;
      });
      return settings;
    } catch (error) {
      console.error("خطأ في جلب الإعدادات:", error);
      throw error;
    }
  }

  static async updateSettings(settingsData) {
    try {
      const batch = db.batch();

      for (const [key, value] of Object.entries(settingsData)) {
        // البحث عن الإعداد الموجود
        const snapshot = await collections.settings
          .where("setting_key", "==", key)
          .get();

        if (snapshot.empty) {
          // إضافة إعداد جديد
          const ref = collections.settings.doc();
          batch.set(ref, {
            setting_key: key,
            setting_value: value,
            updated_at: firebase.firestore.FieldValue.serverTimestamp(),
          });
        } else {
          // تحديث الإعداد الموجود
          const doc = snapshot.docs[0];
          batch.update(doc.ref, {
            setting_value: value,
            updated_at: firebase.firestore.FieldValue.serverTimestamp(),
          });
        }
      }

      await batch.commit();
      return true;
    } catch (error) {
      console.error("خطأ في تحديث الإعدادات:", error);
      throw error;
    }
  }

  // ============= التقارير (Reports) =============

  static async getStatistics() {
    try {
      const stats = {
        total_products: 0,
        low_stock_products: 0,
        total_invoices: 0,
        total_revenue: 0,
        total_profit: 0,
      };

      // عدد المنتجات
      const productsSnapshot = await collections.products.get();
      stats.total_products = productsSnapshot.size;

      // المنتجات منخفضة المخزون
      const lowStockProducts = await this.getLowStockProducts();
      stats.low_stock_products = lowStockProducts.length;

      // الفواتير المدفوعة
      const invoicesSnapshot = await collections.invoices
        .where("status", "==", "paid")
        .get();
      stats.total_invoices = invoicesSnapshot.size;

      // الإيرادات والأرباح
      invoicesSnapshot.docs.forEach((doc) => {
        const data = doc.data();
        stats.total_revenue += parseFloat(data.total || 0);
        stats.total_profit += parseFloat(data.profit || 0);
      });

      return stats;
    } catch (error) {
      console.error("خطأ في جلب الإحصائيات:", error);
      throw error;
    }
  }

  static async getReports(dateFrom, dateTo) {
    try {
      let query = collections.invoices;

      if (dateFrom) {
        query = query.where("created_at", ">=", new Date(dateFrom));
      }
      if (dateTo) {
        query = query.where("created_at", "<=", new Date(dateTo));
      }

      const snapshot = await query.orderBy("created_at", "desc").get();
      return snapshot.docs.map((doc) => ({
        id: doc.id,
        ...doc.data(),
      }));
    } catch (error) {
      console.error("خطأ في جلب التقارير:", error);
      throw error;
    }
  }
}
