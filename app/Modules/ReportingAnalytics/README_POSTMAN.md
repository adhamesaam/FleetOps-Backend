# 📖 دليل اختبار موديول التقارير والتحليلات (ReportingAnalytics) على Postman

هذا الدليل يشرح كيفية اختبار جميع الـ Endpoints الخاصة بموديول التقارير والإحصائيات (`ReportingAnalytics`).
جميع الروابط تبدأ بـ: `http://localhost:8000/api/v1/analytics`

**هام جداً:** جميع المسارات تتطلب إضافة `Bearer Token` في الـ Headers (يفضل حساب إداري).

---

## 1️⃣ المؤشرات الرئيسية (KPIs)

### 📈 معدل التسليم في الوقت المحدد (On-Time Delivery Rate)
- **المسار:** `GET /api/v1/analytics/kpis/on-time-rate`
- **الوظيفة:** حساب نسبة الطلبات التي تم تسليمها ضمن الوقت المتوقع.

### 🌿 تقرير الانبعاثات الكربونية (CO2 Report)
- **المسار:** `GET /api/v1/analytics/kpis/co2-report`
- **الوظيفة:** تقدير إجمالي الانبعاثات الكربونية الناتجة عن أسطول المركبات (تعتمد على استهلاك الوقود والمسافات).

### ⚠️ رصد التشوهات أو المخالفات (Anomalies)
- **المسار:** `GET /api/v1/analytics/kpis/anomalies`
- **الوظيفة:** رصد الحالات غير الطبيعية (مثل سرعة زائدة، استهلاك وقود مبالغ فيه، تغيير مسار مفاجئ).

### ⭐ تقييم السائق (Driver Score)
- **المسار:** `GET /api/v1/analytics/kpis/driver-score/5`
- **الوظيفة:** تقييم السائق رقم 5 بناءً على جودة القيادة، الحوادث، والالتزام بالوقت.

### 📋 قائمة بجميع الـ KPIs (List)
- **المسار:** `GET /api/v1/analytics/kpis`
- **الوظيفة:** عرض جميع الإحصائيات المسجلة مسبقاً (Pagination).

---

## 2️⃣ التقارير الشاملة (Reports)

### 📊 لوحة البيانات اليومية (Daily Dashboard)
- **المسار:** `GET /api/v1/analytics/reports/daily-dashboard`
- **الوظيفة:** ملخص شامل ليوم العمل الحالي (الطلبات، المركبات النشطة، الإيرادات).

### 📦 ملخص عمليات التسليم (Delivery Summary)
- **المسار:** `GET /api/v1/analytics/reports/delivery-summary`
- **الوظيفة:** تقرير شامل ببيانات التوصيل موزعة حسب الوقت والمناطق.

### 💰 تقرير تكلفة الصيانة (Maintenance Cost)
- **المسار:** `GET /api/v1/analytics/reports/maintenance-cost`
- **الوظيفة:** تجميع وتحليل تكاليف الصيانة الدورية والطارئة.

### 🏆 لوحة شرف السائقين (Driver Leaderboard)
- **المسار:** `GET /api/v1/analytics/reports/driver-leaderboard`
- **الوظيفة:** عرض قائمة أفضل السائقين بناءً على التقييمات وسرعة التوصيل.

### 📤 تصدير التقارير (Export Reports)
- **المسار:** `POST /api/v1/analytics/reports/export`
- **الـ Body (JSON):**
```json
{
    "report_type": "delivery-summary",
    "format": "pdf",
    "start_date": "2026-04-01",
    "end_date": "2026-04-30"
}
```
