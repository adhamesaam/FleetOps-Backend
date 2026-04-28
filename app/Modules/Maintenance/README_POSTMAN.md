# 📖 دليل اختبار موديول الصيانة (Maintenance) على Postman

هذا الدليل يشرح كيفية اختبار جميع الـ Endpoints الخاصة بموديول الصيانة وقطع الغيار (`Maintenance`).
جميع الروابط تبدأ بـ: `http://localhost:8000/api/v1/maintenance`

**هام جداً:** جميع المسارات تتطلب إضافة `Bearer Token` في الـ Headers الخاص بـ Postman.

---

## 1️⃣ أوامر الصيانة (Work Orders)
لإدارة عمليات الصيانة الدورية والطارئة.

### 📋 عرض أوامر الصيانة
- **كل الأوامر:** `GET /api/v1/maintenance/work-orders`
- **الأوامر المفتوحة فقط:** `GET /api/v1/maintenance/work-orders/open`
- **أوامر مركبة معينة:** `GET /api/v1/maintenance/work-orders/vehicle/{vehicleId}`
- **أوامر ميكانيكي معين:** `GET /api/v1/maintenance/work-orders/mechanic/{mechanicId}`

### ➕ إنشاء أمر صيانة جديد
- **المسار:** `POST /api/v1/maintenance/work-orders`
- **الـ Body (JSON):**
```json
{
    "vehicle_id": 1,
    "issue_description": "تغيير زيت وفلاتر",
    "priority": "normal"
}
```

### 👨‍🔧 تعيين ميكانيكي لأمر صيانة
- **المسار:** `POST /api/v1/maintenance/work-orders/1/assign`
- **الـ Body (JSON):**
```json
{
    "mechanic_id": 5
}
```

### 🔄 تحديث حالة أمر الصيانة
- **المسار:** `PATCH /api/v1/maintenance/work-orders/1/status`
- **الـ Body (JSON):**
```json
{
    "status": "in_progress" 
}
```
*(الحالات المتاحة عادة: open, in_progress, completed)*

### ⚙️ تسجيل قطع الغيار المستخدمة
- **المسار:** `POST /api/v1/maintenance/work-orders/1/parts`
- **الـ Body (JSON):**
```json
{
    "part_id": 2,
    "quantity": 4
}
```

---

## 2️⃣ مخزون قطع الغيار (Spare Parts)

### 📋 إدارة المخزون
- **عرض كل القطع:** `GET /api/v1/maintenance/parts`
- **عرض القطع التي أوشكت على النفاد:** `GET /api/v1/maintenance/parts/low-stock`
- **إنشاء قطعة جديدة:** `POST /api/v1/maintenance/parts`
```json
{
    "name": "فلتر زيت",
    "part_number": "FIL-123",
    "quantity_in_stock": 50,
    "minimum_threshold": 10
}
```

### ⚖️ تعديل كمية المخزون (Adjustment)
- **المسار:** `POST /api/v1/maintenance/parts/1/adjust-stock`
- **الـ Body (JSON):**
```json
{
    "quantity": -2,
    "reason": "تالف"
}
```

---

## 3️⃣ الفحوصات (Vehicle Inspections)
لإدارة الفحوصات الدورية أو السنوية.

### 📋 عرض الفحوصات
- **كل الفحوصات:** `GET /api/v1/maintenance/inspections`
- **الفحوصات المتأخرة:** `GET /api/v1/maintenance/inspections/overdue`
- **الفحوصات القادمة:** `GET /api/v1/maintenance/inspections/upcoming`
- **فحوصات مركبة معينة:** `GET /api/v1/maintenance/inspections/vehicle/1`

### ➕ تسجيل فحص جديد
- **المسار:** `POST /api/v1/maintenance/inspections`
- **الـ Body (JSON):**
```json
{
    "vehicle_id": 1,
    "inspection_date": "2026-05-01",
    "type": "annual",
    "status": "passed",
    "notes": "كل شيء سليم"
}
```
