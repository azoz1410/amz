# دليل نشر مشروع AMZ على Google Cloud Platform

## المتطلبات الأساسية

قبل البدء، تأكد من:

- ✅ حساب Google Cloud مفعّل (الخطة المجانية كافية)
- ✅ بطاقة ائتمانية مسجلة (مطلوبة للتحقق فقط، لن يتم الخصم)
- ✅ مساحة 300$ رصيد تجريبي مجاني

---

## الخطوة 1️⃣: تثبيت Google Cloud SDK

### على Windows:

1. قم بتحميل المثبت من:

   ```
   https://cloud.google.com/sdk/docs/install
   ```

2. شغل الملف المحمل: `GoogleCloudSDKInstaller.exe`

3. أثناء التثبيت، تأكد من تحديد:

   - ☑️ Install bundled Python
   - ☑️ Run gcloud init

4. بعد التثبيت، افتح **Command Prompt** جديد وتأكد من التثبيت:
   ```bash
   gcloud version
   ```

---

## الخطوة 2️⃣: إعداد مشروع Google Cloud

### 2.1 تسجيل الدخول

```bash
gcloud auth login
```

سيفتح متصفح للدخول بحسابك في Google.

### 2.2 إنشاء مشروع جديد

1. افتح **Google Cloud Console**:

   ```
   https://console.cloud.google.com
   ```

2. انقر على القائمة المنسدلة للمشاريع (أعلى اليسار)

3. اختر **New Project**

4. املأ التفاصيل:

   - **Project Name**: `AMZ Inventory System`
   - **Project ID**: سيتم توليده تلقائياً (مثل: `amz-inventory-123456`)
   - **Location**: اتركها Organization

5. انقر **Create**

### 2.3 تعيين المشروع الافتراضي

```bash
gcloud config set project YOUR_PROJECT_ID
```

استبدل `YOUR_PROJECT_ID` بمعرف المشروع الذي أنشأته (مثل: `amz-inventory-123456`)

---

## الخطوة 3️⃣: إنشاء قاعدة البيانات Cloud SQL

### 3.1 تفعيل Cloud SQL API

```bash
gcloud services enable sqladmin.googleapis.com
```

### 3.2 إنشاء MySQL Instance

```bash
gcloud sql instances create amz-db \
  --database-version=MYSQL_8_0 \
  --tier=db-f1-micro \
  --region=us-central1 \
  --root-password=YOUR_STRONG_PASSWORD
```

⚠️ **مهم**: استبدل `YOUR_STRONG_PASSWORD` بكلمة مرور قوية واحفظها!

**ملاحظة**: `db-f1-micro` هي أصغر حجم (مجانية جزئياً حتى 30GB/شهر)

### 3.3 إنشاء قاعدة البيانات

```bash
gcloud sql databases create amz_inventory --instance=amz-db
```

### 3.4 الحصول على Connection Name

```bash
gcloud sql instances describe amz-db --format="value(connectionName)"
```

احفظ الناتج، سيكون بصيغة: `PROJECT_ID:REGION:INSTANCE_NAME`

مثال: `amz-inventory-123456:us-central1:amz-db`

---

## الخطوة 4️⃣: استيراد قاعدة البيانات

### 4.1 رفع ملف SQL إلى Cloud Storage

```bash
# إنشاء bucket
gsutil mb gs://YOUR_PROJECT_ID-sql-backup

# رفع ملف قاعدة البيانات
gsutil cp database.sql gs://YOUR_PROJECT_ID-sql-backup/
```

### 4.2 استيراد البيانات

```bash
gcloud sql import sql amz-db \
  gs://YOUR_PROJECT_ID-sql-backup/database.sql \
  --database=amz_inventory
```

---

## الخطوة 5️⃣: تحديث ملف app.yaml

افتح ملف `app.yaml` وعدّل الأسطر التالية:

```yaml
env_variables:
  DB_HOST: "/cloudsql/YOUR_PROJECT_ID:REGION:INSTANCE_NAME"
  DB_USER: "root"
  DB_PASS: "YOUR_STRONG_PASSWORD"
  DB_NAME: "amz_inventory"
```

**استبدل**:

- `YOUR_PROJECT_ID:REGION:INSTANCE_NAME` → بالقيمة من الخطوة 3.4
- `YOUR_STRONG_PASSWORD` → بكلمة المرور من الخطوة 3.2

مثال:

```yaml
env_variables:
  DB_HOST: "/cloudsql/amz-inventory-123456:us-central1:amz-db"
  DB_USER: "root"
  DB_PASS: "MySecurePass123!"
  DB_NAME: "amz_inventory"
```

---

## الخطوة 6️⃣: نشر التطبيق

### 6.1 الانتقال لمجلد المشروع

```bash
cd C:\xampp\htdocs\AMZ
```

### 6.2 تفعيل App Engine API

```bash
gcloud services enable appengine.googleapis.com
```

### 6.3 إنشاء تطبيق App Engine

```bash
gcloud app create --region=us-central
```

### 6.4 نشر التطبيق

```bash
gcloud app deploy
```

سيطلب منك تأكيد النشر، اكتب `Y` واضغط Enter.

**⏱️ ملاحظة**: أول نشر يأخذ 5-10 دقائق

---

## الخطوة 7️⃣: اختبار التطبيق

### 7.1 فتح التطبيق

```bash
gcloud app browse
```

سيفتح رابط التطبيق في المتصفح، مثل:

```
https://YOUR_PROJECT_ID.uc.r.appspot.com
```

### 7.2 التحقق من عمل قاعدة البيانات

جرّب:

- إضافة منتج جديد
- إنشاء فاتورة
- عرض التقارير

---

## الخطوة 8️⃣: مراقبة التكاليف (مهم!)

### 8.1 تعيين ميزانية تنبيهية

1. افتح Console → **Billing** → **Budgets & alerts**

2. أنشئ Budget جديد:

   - Amount: `$5/month` (احتياطي)
   - Alert at: `50%, 90%, 100%`

3. أضف بريدك الإلكتروني لتلقي التنبيهات

### 8.2 مراقبة الاستخدام

```bash
# عرض تكاليف المشروع
gcloud billing projects describe YOUR_PROJECT_ID
```

---

## الأوامر المفيدة

### عرض Logs التطبيق:

```bash
gcloud app logs tail -s default
```

### إيقاف التطبيق مؤقتاً (لتوفير التكاليف):

```bash
gcloud app versions stop VERSION_ID
```

### إعادة النشر بعد التعديلات:

```bash
gcloud app deploy
```

### حذف نسخة قديمة:

```bash
gcloud app versions delete VERSION_ID
```

### الاتصال بـ Cloud SQL من جهازك المحلي:

```bash
gcloud sql connect amz-db --user=root
```

---

## استكشاف الأخطاء

### ❌ خطأ: "Database connection failed"

**الحل**:

1. تحقق من `app.yaml` → `DB_HOST`, `DB_PASS`
2. تأكد من إنشاء قاعدة البيانات بنجاح
3. راجع Logs: `gcloud app logs tail`

### ❌ خطأ: "Permission denied"

**الحل**:

```bash
gcloud sql instances patch amz-db \
  --authorized-networks=0.0.0.0/0
```

⚠️ **تحذير**: هذا يفتح قاعدة البيانات للجميع، استخدمه للتطوير فقط!

### ❌ الصفحات لا تظهر بشكل صحيح

**الحل**:
تحقق من مسارات الملفات في `app.yaml` → `handlers`

---

## إيقاف المشروع (لتجنب التكاليف)

إذا أردت إيقاف كل شيء:

### 1. إيقاف التطبيق:

```bash
gcloud app versions stop --service=default --all
```

### 2. حذف Cloud SQL Instance:

```bash
gcloud sql instances delete amz-db
```

⚠️ **تحذير**: هذا سيحذف جميع البيانات نهائياً!

### 3. حذف المشروع بالكامل:

```bash
gcloud projects delete YOUR_PROJECT_ID
```

---

## التكاليف المتوقعة (الخطة المجانية)

| الخدمة              | الاستخدام المجاني | التكلفة بعد الحد |
| ------------------- | ----------------- | ---------------- |
| **App Engine**      | 28 ساعة/يوم       | ~$0.05/ساعة      |
| **Cloud SQL**       | 30GB تخزين        | ~$0.017/GB       |
| **Egress (Upload)** | 1GB/شهر           | ~$0.12/GB        |

**💡 نصيحة**: مع استخدام خفيف، ستبقى ضمن الحد المجاني!

---

## الدعم والمساعدة

### وثائق Google Cloud:

- App Engine PHP: https://cloud.google.com/appengine/docs/standard/php
- Cloud SQL: https://cloud.google.com/sql/docs

### في حالة المشاكل:

راجع ملف `config-cloud.php` - يحتوي على تكوين ذكي يدعم البيئتين المحلية والسحابية.

---

## ✅ النجاح!

إذا وصلت لهنا، فمبروك! 🎉
مشروعك الآن يعمل على Google Cloud Platform.

**رابط تطبيقك**:

```
https://YOUR_PROJECT_ID.uc.r.appspot.com
```

---

**آخر تحديث**: نوفمبر 2025
