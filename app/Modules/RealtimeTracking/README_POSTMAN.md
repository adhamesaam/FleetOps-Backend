# 📖 دليل اختبار موديول التتبع الجغرافي (RealtimeTracking) على Postman

هذا الدليل يشرح كيفية اختبار جميع الـ Endpoints الخاصة بموديول التتبع الفعلي والـ GPS (`RealtimeTracking`).

---

## 1️⃣ التتبع العام للعملاء (Public Tracking)
هذا المسار **لا يتطلب توكن** (لأن العميل يستخدمه لتتبع شحنته).

- **المسار:** `GET /api/v1/tracking/public/{token}`
- **الوظيفة:** عرض الموقع الفعلي للمركبة للعميل باستخدام توكن التتبع الآمن.
- **مثال:** `GET /api/v1/tracking/public/abc-123-xyz`

---

## 2️⃣ تحديثات الموقع الجغرافي (GPS Location Updates)
**هام جداً:** المسارات التالية تتطلب إضافة `Bearer Token` الخاص بالسائق أو النظام.

### 📍 إرسال إحداثيات السائق (GPS Ping)
- **المسار:** `POST /api/v1/tracking/location`
- **الوظيفة:** السائق يرسل هذا الطلب كل 5 ثوانٍ لتحديث موقعه.
- **الـ Body (JSON):**
```json
{
    "driver_id": 3,
    "latitude": 24.7136,
    "longitude": 46.6753,
    "speed": 65.5,
    "heading": 90
}
```

### 🗺️ عرض آخر موقع معروف لسائق (Last Known Location)
- **المسار:** `GET /api/v1/tracking/drivers/3/last-location`

### 📡 حالة السائق (Driver Heartbeat/Status)
- **المسار:** `GET /api/v1/tracking/drivers/3/status`
- **الوظيفة:** جلب حالة السائق (متصل، غير متصل، يتحرك).

### ⏪ إعادة تشغيل مسار تاريخي (Historical Playback)
- **المسار:** `GET /api/v1/tracking/routes/5/trail`
- **الوظيفة:** جلب جميع النقاط الجغرافية المسجلة لرحلة معينة لرسم مسارها على الخريطة.

---

## 3️⃣ النطاقات الجغرافية (Geofences)
يتطلب توكن + صلاحيات مدير.

- **عرض كل النطاقات:** `GET /api/v1/tracking/geofences`
- **إنشاء نطاق جديد:** `POST /api/v1/tracking/geofences`
  ```json
  {
      "name": "مستودع الرياض الرئيسي",
      "latitude": 24.7136,
      "longitude": 46.6753,
      "radius_meters": 500
  }
  ```
- **تفاصيل نطاق:** `GET /api/v1/tracking/geofences/{id}`
- **تحديث نطاق:** `PUT /api/v1/tracking/geofences/{id}`
- **حذف نطاق:** `DELETE /api/v1/tracking/geofences/{id}`

---

## 4️⃣ روابط التتبع للعملاء (Tracking Links)
يتطلب توكن.

- **إنشاء رابط تتبع لطلب:** `POST /api/v1/tracking/links`
  ```json
  {
      "order_id": 100,
      "expires_at": "2026-05-01 12:00:00"
  }
  ```
- **إلغاء رابط تتبع:** `DELETE /api/v1/tracking/links/{id}`
