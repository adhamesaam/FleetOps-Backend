# 📖 دليل اختبار موديول AuthIdentity على Postman

هذا الدليل يشرح كيفية اختبار جميع الـ Endpoints الخاصة بموديول المصادقة والهوية (`AuthIdentity`).
جميع الروابط تبدأ بـ: `http://localhost:8000/api/v1`

---

## 1️⃣ مسارات عامة (لا تتطلب تسجيل دخول)

### 🔑 تسجيل الدخول (Login)
- **المسار:** `POST /api/v1/auth/login`
- **الوظيفة:** تسجيل الدخول والحصول على توكن `Sanctum`.
- **الـ Body (JSON):**
```json
{
    "email": "admin@example.com",
    "password": "password123"
}
```

### ❓ نسيت كلمة المرور (Forgot Password)
- **المسار:** `POST /api/v1/auth/forgot-password`
- **الوظيفة:** إرسال رابط إعادة تعيين كلمة المرور إلى البريد الإلكتروني.
- **الـ Body (JSON):**
```json
{
    "email": "admin@example.com"
}
```

### 🔄 إعادة تعيين كلمة المرور (Reset Password)
- **المسار:** `POST /api/v1/auth/reset-password`
- **الوظيفة:** إعادة تعيين كلمة المرور باستخدام التوكن المرسل للبريد.
- **الـ Body (JSON):**
```json
{
    "email": "admin@example.com",
    "token": "TOKEN_STRING_HERE",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

---

## 2️⃣ مسارات محمية (تتطلب 토كن)
**هام جداً:** جميع المسارات التالية تتطلب إضافة التوكن في الـ Headers الخاص بـ Postman كالتالي:
- اذهب إلى تبويب **Authorization** في Postman.
- اختر النوع **Bearer Token**.
- الصق التوكن الذي حصلت عليه من خطوة الـ Login.

### 👤 بياناتي (Me)
- **المسار:** `GET /api/v1/auth/me`
- **الوظيفة:** جلب بيانات المستخدم المسجل دخوله حالياً.

### 🚪 تسجيل الخروج (Logout)
- **المسار:** `POST /api/v1/auth/logout`
- **الوظيفة:** تسجيل الخروج من الجهاز الحالي وإبطال التوكن.

### 🚪 الخروج من جميع الأجهزة (Logout All)
- **المسار:** `POST /api/v1/auth/logout-all`
- **الوظيفة:** تسجيل الخروج من جميع الأجهزة المتصلة.

### 🔄 تجديد التوكن (Refresh Token)
- **المسار:** `POST /api/v1/auth/refresh`
- **الوظيفة:** إصدار توكن جديد وإلغاء القديم.

### 🔑 تغيير كلمة المرور (Change Password)
- **المسار:** `POST /api/v1/auth/change-password`
- **الوظيفة:** تغيير كلمة المرور أثناء تسجيل الدخول.
- **الـ Body (JSON):**
```json
{
    "current_password": "password123",
    "new_password": "newpassword456",
    "new_password_confirmation": "newpassword456"
}
```

---

## 3️⃣ إدارة المستخدمين (User Management)
يتطلب توكن + صلاحيات.

- **عرض جميع المستخدمين:** `GET /api/v1/users`
- **عرض المستخدمين النشطين فقط:** `GET /api/v1/users/active`
- **عرض السائقين فقط:** `GET /api/v1/users/role/drivers`
- **عرض موظفي الإرسال:** `GET /api/v1/users/role/dispatchers`
- **عرض مديري الأسطول:** `GET /api/v1/users/role/fleet-managers`
- **عرض الميكانيكيين:** `GET /api/v1/users/role/mechanics`

- **عرض مستخدم محدد:** `GET /api/v1/users/{id}`
- **إنشاء مستخدم:** `POST /api/v1/users`
  ```json
  {
      "name": "Ahmed",
      "email": "ahmed@example.com",
      "password": "password123",
      "role": "driver"
  }
  ```
- **تحديث مستخدم:** `PUT /api/v1/users/{id}`
- **حذف مستخدم:** `DELETE /api/v1/users/{id}`

---

## 4️⃣ إدارة الأدوار (Role Management)
يتطلب توكن + صلاحيات.

- **عرض الأدوار غير النظامية:** `GET /api/v1/roles/type/non-system`
- **عرض جميع الأدوار:** `GET /api/v1/roles`
- **إنشاء دور:** `POST /api/v1/roles`
  ```json
  {
      "name": "Super Admin",
      "permissions": ["create_user", "delete_user"]
  }
  ```
- **عرض دور محدد:** `GET /api/v1/roles/{id}`
- **تحديث دور:** `PUT /api/v1/roles/{id}`
- **حذف دور:** `DELETE /api/v1/roles/{id}`



///password for all users is :Pass_Hash_123