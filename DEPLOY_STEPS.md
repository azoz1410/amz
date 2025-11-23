# خطوات النشر السريعة

## 1. رفع التحديثات إلى GitHub

افتح Terminal واكتب:

```bash
cd c:/xampp/htdocs/AMZ
git add .
git commit -m "تحديث: تحويل المشروع إلى Firebase"
git push origin main
```

## 2. تفعيل GitHub Pages

1. اذهب إلى: https://github.com/azoz1410/amz/settings/pages
2. في قسم **Source**:
   - Branch: اختر `main`
   - Folder: اختر `/ (root)`
3. اضغط **Save**
4. انتظر 2-3 دقائق
5. الموقع سيكون متاحاً على: **https://azoz1410.github.io/amz/**

## 3. إعداد Firebase (مهم جداً!)

قبل استخدام الموقع:

1. اذهب إلى https://console.firebase.google.com
2. أنشئ مشروع جديد
3. فعّل **Firestore Database**
4. انسخ إعدادات Firebase
5. حدّث ملف `firebase-config.js` في المشروع

## 4. تحديث ملف firebase-config.js

استبدل القيم في `firebase-config.js`:

```javascript
const firebaseConfig = {
  apiKey: "ضع قيمة API Key هنا",
  authDomain: "ضع قيمة authDomain هنا",
  projectId: "ضع قيمة projectId هنا",
  storageBucket: "ضع قيمة storageBucket هنا",
  messagingSenderId: "ضع قيمة messagingSenderId هنا",
  appId: "ضع قيمة appId هنا",
};
```

بعد التحديث، ارفع التغييرات:

```bash
git add firebase-config.js
git commit -m "إضافة إعدادات Firebase"
git push origin main
```

## 5. إعداد قواعد Firestore

في Firebase Console > Firestore Database > Rules:

```
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    match /{document=**} {
      allow read, write: if true;
    }
  }
}
```

⚠️ **ملاحظة:** هذه القواعد للاختبار فقط!

## ✅ تم!

موقعك الآن جاهز على: **https://azoz1410.github.io/amz/**
