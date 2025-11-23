// Firebase Configuration
// قم بإنشاء مشروع على Firebase Console: https://console.firebase.google.com
// ثم استبدل هذه القيم بقيم مشروعك

const firebaseConfig = {
  apiKey: "YOUR_API_KEY",
  authDomain: "YOUR_PROJECT_ID.firebaseapp.com",
  projectId: "YOUR_PROJECT_ID",
  storageBucket: "YOUR_PROJECT_ID.appspot.com",
  messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
  appId: "YOUR_APP_ID",
};

// تهيئة Firebase
firebase.initializeApp(firebaseConfig);

// تهيئة Firestore
const db = firebase.firestore();

// تفعيل الدعم للغة العربية
db.settings({
  cacheSizeBytes: firebase.firestore.CACHE_SIZE_UNLIMITED,
});

// المجموعات (Collections)
const collections = {
  products: db.collection("products"),
  invoices: db.collection("invoices"),
  invoiceItems: db.collection("invoice_items"),
  settings: db.collection("settings"),
  stockMovements: db.collection("stock_movements"),
};
