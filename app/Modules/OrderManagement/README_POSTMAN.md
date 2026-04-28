# 📖 دليل اختبار موديول إدارة الطلبات (OrderManagement) على Postman

هذا الدليل يشرح كيفية اختبار جميع الـ Endpoints الخاصة بموديول إدارة الطلبات وتأكيد التسليم (`OrderManagement`).
جميع الروابط تبدأ بـ: `http://localhost:8000/api/v1/orders`

**هام جداً:** جميع المسارات تتطلب إضافة `Bearer Token` في الـ Headers.

---

## 1️⃣ إدارة الطلبات (Order Management)

### 📋 عرض الطلبات
- **عرض كل الطلبات:** `GET /api/v1/orders`
- **طلبات مسار معين (Route):** `GET /api/v1/orders/route/{routeId}`
- **طلبات سائق معين:** `GET /api/v1/orders/driver/{driverId}`
- **تفاصيل طلب معين:** `GET /api/v1/orders/{id}`

### ➕ إنشاء وإدارة طلب
- **إنشاء طلب جديد:** `POST /api/v1/orders`
  ```json
  {
      "customer_id": 1,
      "route_id": 2,
      "delivery_address": "Riyadh, Olaya St.",
      "priority": "high",
      "items": [
          {"name": "Laptop", "quantity": 1}
      ]
  }
  ```
- **تحديث طلب:** `PUT /api/v1/orders/{id}`
- **حذف طلب:** `DELETE /api/v1/orders/{id}`
- **استيراد مجمع (Bulk Import):** `POST /api/v1/orders/import` (عادة لرفع ملفات Excel/CSV).

---

## 2️⃣ عمليات التسليم (Delivery Actions)

### 🔄 تحديث حالة الطلب
- **المسار:** `PATCH /api/v1/orders/1/status`
- **الـ Body (JSON):**
```json
{
    "status": "out_for_delivery"
}
```

### 📱 التحقق من رمز QR
- **المسار:** `POST /api/v1/orders/1/verify-qr`
- **الـ Body (JSON):**
```json
{
    "scanned_code": "QR_CODE_STRING"
}
```

### ↩️ تسجيل طلب مرتجع (Return)
- **المسار:** `POST /api/v1/orders/1/return`
- **الـ Body (JSON):**
```json
{
    "reason": "العميل رفض الاستلام"
}
```

---

## 3️⃣ تأكيد التسليم (Proof of Delivery - POD)

### 📸 إنشاء إثبات تسليم جديد
- **المسار:** `POST /api/v1/orders/1/pod`
- **ملاحظة هامة:** هذا الـ Endpoint قد يحتاج لإرسال صور، لذا استخدم `form-data` بدلاً من `raw JSON` إذا كنت ترفع صورة توقيع.
- **في حالة JSON:**
```json
{
    "signature_text": "Ahmed",
    "delivery_notes": "تم التسليم بنجاح",
    "cash_collected": 150.50
}
```

### 📋 عرض إثبات تسليم لطلب
- **المسار:** `GET /api/v1/orders/1/pod`

---

## 4️⃣ الفحص قبل الرحلة (Pre-Trip Inspections)

- **فحوصات مركبة معينة:** `GET /api/v1/orders/inspections/vehicle/{vehicleId}`
- **فحوصات مسار معين:** `GET /api/v1/orders/inspections/route/{routeId}`
- **تسجيل فحص جديد:** `POST /api/v1/orders/inspections`
  ```json
  {
      "vehicle_id": 1,
      "route_id": 5,
      "driver_id": 3,
      "fuel_level": "75%",
      "tires_condition": "good",
      "notes": "جاهز للرحلة"
  }
  ```
