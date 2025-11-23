/**
 * Main JavaScript
 * الدوال المشتركة
 */

// عرض رسالة تنبيه
function showAlert(message, type = "success", duration = 3000) {
  const alertDiv = document.createElement("div");
  alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
  alertDiv.style.zIndex = "9999";
  alertDiv.style.minWidth = "300px";
  alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

  document.body.appendChild(alertDiv);

  setTimeout(() => {
    alertDiv.remove();
  }, duration);
}

// تنسيق الأرقام
function formatNumber(number, decimals = 2) {
  return parseFloat(number).toFixed(decimals);
}

// تنسيق العملة
function formatCurrency(amount, currency = "ر.س") {
  return `${formatNumber(amount)} ${currency}`;
}

// تنسيق التاريخ
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("ar-SA");
}

// تنسيق التاريخ والوقت
function formatDateTime(dateString) {
  const date = new Date(dateString);
  return date.toLocaleString("ar-SA");
}

// تأكيد الحذف
function confirmDelete(message = "هل أنت متأكد من الحذف؟") {
  return confirm(message);
}

// تحميل الإعدادات
let appSettings = {};
async function loadSettings() {
  try {
    appSettings = await API.getSettings();
    return appSettings;
  } catch (error) {
    console.error("Error loading settings:", error);
  }
}

// الحصول على قيمة إعداد
function getSetting(key, defaultValue = "") {
  return appSettings[key] || defaultValue;
}

// إظهار/إخفاء مؤشر التحميل
function showLoading(element) {
  const spinner = document.createElement("div");
  spinner.className = "spinner-border spinner-border-sm me-2";
  spinner.setAttribute("role", "status");
  element.prepend(spinner);
  element.disabled = true;
}

function hideLoading(element) {
  const spinner = element.querySelector(".spinner-border");
  if (spinner) {
    spinner.remove();
  }
  element.disabled = false;
}

// التحقق من صحة البريد الإلكتروني
function isValidEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

// التحقق من صحة رقم الهاتف
function isValidPhone(phone) {
  const re = /^[0-9]{10}$/;
  return re.test(phone.replace(/[\s\-\(\)]/g, ""));
}

// طباعة الصفحة
function printPage() {
  window.print();
}

// تصدير إلى CSV
function exportToCSV(data, filename = "export.csv") {
  const csv = convertToCSV(data);
  const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
  const link = document.createElement("a");
  const url = URL.createObjectURL(blob);

  link.setAttribute("href", url);
  link.setAttribute("download", filename);
  link.style.visibility = "hidden";

  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function convertToCSV(data) {
  if (data.length === 0) return "";

  const headers = Object.keys(data[0]);
  const csvRows = [];

  csvRows.push(headers.join(","));

  for (const row of data) {
    const values = headers.map((header) => {
      const val = row[header];
      return `"${val}"`;
    });
    csvRows.push(values.join(","));
  }

  return csvRows.join("\n");
}

// البحث في الجدول
function searchTable(searchInput, tableBody) {
  const filter = searchInput.value.toLowerCase();
  const rows = tableBody.getElementsByTagName("tr");

  for (let i = 0; i < rows.length; i++) {
    const row = rows[i];
    const text = row.textContent.toLowerCase();

    if (text.indexOf(filter) > -1) {
      row.style.display = "";
    } else {
      row.style.display = "none";
    }
  }
}

// إضافة معالج للبحث المباشر
function setupLiveSearch(searchInputId, tableBodyId) {
  const searchInput = document.getElementById(searchInputId);
  const tableBody = document.getElementById(tableBodyId);

  if (searchInput && tableBody) {
    searchInput.addEventListener("keyup", () => {
      searchTable(searchInput, tableBody);
    });
  }
}

// تحميل الإعدادات عند تحميل الصفحة
document.addEventListener("DOMContentLoaded", async () => {
  await loadSettings();
});
