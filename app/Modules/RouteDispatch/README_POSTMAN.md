# 📖 دليل اختبار موديول إدارة المسارات (RouteDispatch) على Postman

هذا الدليل يشرح كيفية اختبار جميع الـ Endpoints الخاصة بموديول تخطيط وتوجيه المسارات (`RouteDispatch`).
جميع الروابط تبدأ بـ: `http://localhost:8000/api/v1/dispatch`

**هام جداً:** جميع المسارات تتطلب إضافة `Bearer Token` في الـ Headers.

---

## 1️⃣ إدارة المسارات (Routes)

### 📋 عرض مسارات
- **كل المسارات:** `GET /api/v1/dispatch/routes`
- **مسارات سائق معين:** `GET /api/v1/dispatch/routes/driver/{driverId}`
- **مسار محدد:** `GET /api/v1/dispatch/routes/{id}`

### ➕ إنشاء وإدارة مسار
- **إنشاء مسار:** `POST /api/v1/dispatch/routes`
  ```json
  {
      "name": "مسار شمال الرياض",
      "date": "2026-04-30"
  }
  ```
- **تحديث مسار:** `PUT /api/v1/dispatch/routes/{id}`
- **حذف مسار:** `DELETE /api/v1/dispatch/routes/{id}`

### 🚦 دورة حياة المسار (Lifecycle)
- **بدء المسار (Start):** `POST /api/v1/dispatch/routes/1/start`
- **إنهاء المسار (Complete):** `POST /api/v1/dispatch/routes/1/complete`

### ⚡ تحسين وتعديل المسار ديناميكياً
- **تحسين مسار (Optimize):** `POST /api/v1/dispatch/routes/1/optimize` (لترتيب النقاط آلياً)
- **إدراج طلب عاجل:** `POST /api/v1/dispatch/routes/1/insert-urgent`
- **نقل الطلبات لوردية أخرى:** `POST /api/v1/dispatch/routes/1/shift-transition`

---

## 2️⃣ نقاط التوقف في المسار (Route Stops)

- **عرض نقاط توقف مسار:** `GET /api/v1/dispatch/routes/1/stops`
- **إضافة نقطة لمسار:** `POST /api/v1/dispatch/routes/1/stops`
- **إعادة ترتيب النقاط:** `PUT /api/v1/dispatch/routes/1/stops/reorder`
  ```json
  {
      "stops_order": [3, 1, 2]
  }
  ```
- **تحديث حالة نقطة توقف:** `PATCH /api/v1/dispatch/stops/{stopId}/status`
- **حذف نقطة توقف:** `DELETE /api/v1/dispatch/stops/{stopId}`

---

## 3️⃣ إدارة المركبات للرحلات (Vehicles)

- **عرض المركبات:** `GET /api/v1/dispatch/vehicles`
- **المركبات المتاحة حالياً:** `GET /api/v1/dispatch/vehicles/available`
- **إنشاء مركبة:** `POST /api/v1/dispatch/vehicles`
  ```json
  {
      "plate_number": "ABC 1234",
      "capacity_kg": 5000,
      "type": "truck"
  }
  ```
- **إقفال مركبة (لسبب كالصيانة):** `POST /api/v1/dispatch/vehicles/1/lock`
- **فتح مركبة:** `POST /api/v1/dispatch/vehicles/1/unlock`

---

## 4️⃣ عمليات التوجيه وتخصيص السائقين (Dispatch Operations)

### 👨‍✈️ تخصيص سائق ومركبة لمسار (Assign)
- **المسار:** `POST /api/v1/dispatch/assign`
- **الـ Body (JSON):**
```json
{
    "route_id": 1,
    "driver_id": 5,
    "vehicle_id": 2
}
```

### 🔄 إعادة التوزيع (Redistribute)
- **المسار:** `POST /api/v1/dispatch/redistribute` (توزيع الطلبات آلياً)

### 📦 تجميع الطلبات في نطاقات جغرافية (Cluster Orders)
- **المسار:** `POST /api/v1/dispatch/cluster-orders`

### ⚖️ فحص السعة المتاحة للمركبة (Capacity Check)
- **المسار:** `POST /api/v1/dispatch/capacity-check`

### 🛂 فحص رخص القيادة (License Check)
- **المسار:** `GET /api/v1/dispatch/license-check` (للتأكد من سريان رخص السائقين المتاحين)

### ✅ فحص مدى توفر السائق (Driver Availability)
- **المسار:** `GET /api/v1/dispatch/drivers/5/availability`
