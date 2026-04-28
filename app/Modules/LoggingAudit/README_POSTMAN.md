# 📖 دليل اختبار موديول LoggingAudit على Postman

هذا الدليل يشرح كيفية اختبار جميع الـ Endpoints الخاصة بموديول المراجعة والسجلات (`LoggingAudit`).
جميع الروابط تبدأ بـ: `http://localhost:8000/api/v1/audit`

**هام جداً:** جميع المسارات التالية تتطلب إضافة `Bearer Token` في الـ Headers الخاص بـ Postman.

---

## 1️⃣ سجلات المراجعة (Audit Logs)
هذه السجلات تتتبع التغييرات (إنشاء، تعديل، حذف) على البيانات.

### 📋 عرض السجلات (List Logs)
- **المسار:** `GET /api/v1/audit/logs`
- **الوظيفة:** عرض السجلات مع إمكانية التصفية (Filtering).
- **الـ Parameters المتاحة (Query):**
  - `action=create` أو `update` أو `delete`
  - `entity_type=user` أو `vehicle`

### 📥 تصدير السجلات (Export Logs)
- **المسار:** `GET /api/v1/audit/logs/export`
- **الوظيفة:** تصدير السجلات إلى ملف (مثلاً: CSV).

### 🔍 سجل تتبع كيان محدد (Entity Trail)
- **المسار:** `GET /api/v1/audit/entity/{entityType}/{entityId}`
- **الوظيفة:** جلب جميع التغييرات التي حدثت على كيان معين.
- **مثال:** `GET /api/v1/audit/entity/user/5` يجلب كل التغييرات التي طرأت على المستخدم رقم 5.

---

## 2️⃣ سجلات النظام (System Logs)
هذه السجلات تقنية ومخصصة لتسجيل الأخطاء ومراقبة أداء النظام.

### 📋 عرض السجلات التقنية (List System Logs)
- **المسار:** `GET /api/v1/audit/system-logs`
- **الوظيفة:** عرض جميع السجلات التقنية.

### ⚠️ عرض الأخطاء فقط (System Errors)
- **المسار:** `GET /api/v1/audit/system-logs/errors`
- **الوظيفة:** جلب السجلات التي من مستوى `error` أو `critical` فقط.

### 📊 عرض الإحصائيات (System Stats)
- **المسار:** `GET /api/v1/audit/system-logs/stats`
- **الوظيفة:** جلب إحصائيات عن أنواع السجلات والـ API calls وأوقات الاستجابة.

### 📺 سجلات قناة محددة (By Channel)
- **المسار:** `GET /api/v1/audit/system-logs/channel/{channel}`
- **الوظيفة:** جلب السجلات التابعة لقناة معينة.
- **مثال:** `GET /api/v1/audit/system-logs/channel/security` (لجلب سجلات الأمان).
