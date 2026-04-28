# دليل تجربة موديول StartFromHere باستخدام Postman

هذا الدليل مصمم للمطورين المبتدئين أو أي شخص يود تجربة سير العمل (Workflow) الخاص بالموديول الذي قمنا ببنائه للتو، من البداية وحتى النهاية باستخدام برنامج **Postman**.

---

## 🛠️ الخطوة 0: التجهيزات المسبقة
قبل البدء بالاختبار يجب التأكد من:
1. أن حاويات Docker تعمل بنجاح (Run `./docker.sh up`, `./docker.sh down` ,XOR,`.\docker.ps1 up`,`.\docker.ps1 down`).
2. أنك قمت بعمل تهيئة لقاعدة البيانات والجداول (Run `docker compose exec app php artisan migrate`).
3. بما أن هذا الموديول محمي بـ `auth:sanctum`، ستحتاج إلى إرسال Header التوثيق (أو إلغاء الـ middleware من `routes.php` مؤقتاً للتجربة السهلة). 
   - *ملاحظة:* إذا أردت التجربة بدون تعقيد تسجيل الدخول، افتح `routes.php` وقم بتغيير:
     `Route::prefix('api/v1')->middleware(['auth:sanctum', StartDemoMiddleware::class])`
     إلى:
     `Route::prefix('api/v1')->middleware([StartDemoMiddleware::class])`

---

## 🚀 رحلة التجربة (Endpoints)

رابط الخادم الأساسي (Base URL) هو: `http://localhost:8000/api/v1`

### 1️⃣ إنشاء سجل جديد (Create)
- **الطريقة (Method):** `POST`
- **الرابط (URL):** `http://localhost:8000/api/v1/demo`
- **شكل البيانات (Body):** اختر تبويب `Body` -> ثم `raw` -> واختر `JSON`.
```json
{
    "title": "المهمة الأولى للتجربة",
    "description": "هذا مجرد نص تجريبي لنرى كيف تحفظ البيانات",
    "status": "active"
}
```
- **النتيجة المتوقعة (Response):** 
```json
{
    "success": true,
    "message": "تم إنشاء السجل بنجاح",
    "data": {
        "title": "المهمة الأولى للتجربة",
        "description": "هذا مجرد نص تجريبي لنرى كيف تحفظ البيانات",
        "status": "active",
        "start_id": 1
    }
}
```
*💡 راقب الـ Console لترى رسالة الـ Middleware التي أضفناها!*

---

### 2️⃣ عرض جميع السجلات (Read All)
- **الطريقة (Method):** `GET`
- **الرابط (URL):** `http://localhost:8000/api/v1/demo`
- **النتيجة المتوقعة (Response):** سيرجع لك قائمة تحتوي على العنصر الذي قمت بإنشائه داخل كائن `data` ويدعم الـ Pagination (مثل `current_page`, `total`).

---

### 3️⃣ البحث عن سجل معين (Search)
- **الطريقة (Method):** `GET`
- **الرابط (URL):** `http://localhost:8000/api/v1/demo/search?keyword=المهمة`
- **النتيجة المتوقعة (Response):** سيرجع العناصر التي تحتوي كلمة "المهمة" في العنوان أو الوصف.

---

### 4️⃣ جلب سجل واحد (Read One)
- **الطريقة (Method):** `GET`
- **الرابط (URL):** `http://localhost:8000/api/v1/demo/1`
  *(استبدل 1 برقم `start_id` الخاص بالعنصر الذي أنشأته)*

---

### 5️⃣ تحديث السجل (Update)
- **الطريقة (Method):** `PUT`
- **الرابط (URL):** `http://localhost:8000/api/v1/demo/1`
- **شكل البيانات (Body - JSON):**
```json
{
    "title": "تم تعديل العنوان بنجاح!",
    "status": "inactive"
}
```

---

### 6️⃣ تبديل حالة السجل (Toggle Status) - Custom Action
- **الطريقة (Method):** `POST`
- **الرابط (URL):** `http://localhost:8000/api/v1/demo/1/toggle-status`
- **النتيجة المتوقعة (Response):** سيقوم الكود أوتوماتيكياً بتحويل الحالة من `active` إلى `inactive` أو العكس بدون الحاجة لإرسال Body.

---

### 7️⃣ حذف السجل (Soft Delete)
- **الطريقة (Method):** `DELETE`
- **الرابط (URL):** `http://localhost:8000/api/v1/demo/1`
- **النتيجة المتوقعة (Response):** سيتم إخفاء السجل (بوضع تاريخ في `deleted_at`) بدلاً من حذفه نهائياً. إذا جربت طلبه بـ GET بعد ذلك سيخبرك أنه غير موجود `404`.

---

## 🎯 نصائح للمطور
1. جرب إرسال `title` أقل من 3 حروف في طلب `POST`. سترى كيف يقوم **FormRequest** بإيقاف الطلب فوراً ويرجع `422 Unprocessable Entity` موضحاً لك رسائل الخطأ من التحقق.
2. ادخل إلى مجلد `storage/logs/laravel.log` (إذا كنت تستخدم Linux) أو استخدم `docker compose exec app tail -f storage/logs/laravel.log` لتشاهد كيف أن الـ **Middleware** الخاص بنا طبع رسالة مع كل Request!
