# 📖 دليل اختبار موديول الإشعارات (Notification) على Postman

هذا الدليل يشرح كيفية اختبار جميع الـ Endpoints الخاصة بموديول الإشعارات (`Notification`).
جميع الروابط تبدأ بـ: `http://localhost:8000/api/v1/notifications`

**هام جداً:** جميع المسارات تتطلب إضافة `Bearer Token` في الـ Headers.

---

## 1️⃣ إدارة الإشعارات (Notifications)

### 📋 عرض الإشعارات الخاصة بي
- **المسار:** `GET /api/v1/notifications`
- **الوظيفة:** عرض جميع الإشعارات المرسلة للمستخدم الحالي.

### 👁️ قراءة إشعار محدد
- **المسار:** `GET /api/v1/notifications/{id}`
- **الوظيفة:** جلب تفاصيل إشعار محدد وتحديث حالته ليصبح (مقروء).

---

## 2️⃣ إعدادات الإشعارات (Preferences)

### ⚙️ عرض الإعدادات المفضلة
- **المسار:** `GET /api/v1/notifications/preferences`
- **الوظيفة:** جلب الإعدادات الحالية لتفضيلات إشعارات المستخدم (مثلاً تفعيل إشعارات التطبيق، البريد، الـ SMS).

### 🛠️ تحديث الإعدادات المفضلة
- **المسار:** `PUT /api/v1/notifications/preferences`
- **الوظيفة:** تحديث تفضيلات المستخدم.
- **الـ Body (JSON):**
```json
{
    "email_notifications": true,
    "push_notifications": true,
    "sms_notifications": false
}
```

### 📱 تحديث توكن الجهاز (FCM Token)
- **المسار:** `POST /api/v1/notifications/fcm-token`
- **الوظيفة:** تحديث التوكن الخاص بـ Firebase Cloud Messaging لإرسال إشعارات الهاتف (Push Notifications).
- **الـ Body (JSON):**
```json
{
    "fcm_token": "APA91bE...YOUR_FCM_TOKEN_HERE",
    "device_type": "android"
}
```
