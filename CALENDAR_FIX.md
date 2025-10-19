# 🔧 راه حل تقویم (اگر هنوز یکشنبه اول هست)

## 🎯 **مشکل:**
تقویم هنوز از یکشنبه شروع میشه به جای شنبه

## ✅ **راه‌حل‌ها:**

### 1️⃣ **Hard Refresh (اولین کار):**

#### Windows/Linux:
```
Ctrl + Shift + R
یا
Ctrl + F5
```

#### Mac:
```
Cmd + Shift + R
```

---

### 2️⃣ **پاک کردن Cache مرورگر:**

#### Chrome/Edge:
```
1. F12 (Developer Tools)
2. راست کلیک روی دکمه Refresh
3. انتخاب "Empty Cache and Hard Reload"
```

#### Firefox:
```
1. Ctrl + Shift + Delete
2. انتخاب "Cached Web Content"
3. کلیک "Clear Now"
```

---

### 3️⃣ **تغییر Version Number:**

اگر بالا کار نکرد، باید version number فایل JS رو عوض کنی:

**فایل:** `puzzlingcrm.php`

```php
// پیدا کن:
define('PUZZLINGCRM_VERSION', '0.1.06');

// عوضش کن به:
define('PUZZLINGCRM_VERSION', '0.1.07');
```

این کار cache رو force break می‌کنه.

---

### 4️⃣ **چک کن که تغییرات Apply شده:**

#### راه اول: مشاهده Source:
```
1. F12 (Developer Tools)
2. تب "Sources" یا "Debugger"
3. پیدا کن: tasks-management.js
4. جستجو کن: "firstDay"
5. باید ببینی: firstDay: 6
```

#### راه دوم: Console:
```
1. F12
2. تب "Console"
3. تایپ کن و Enter بزن:
   console.log(taskCalendar.getOption('firstDay'));
4. باید جواب بده: 6
```

---

### 5️⃣ **تنظیمات جدید اعمال شده:**

```javascript
firstDay: 6,              // ✅ شنبه اول هفته
locale: 'fa',             // ✅ فارسی
direction: 'rtl',         // ✅ راست به چپ
weekends: [5, 6],         // ✅ جمعه و شنبه آخر هفته
dayHeaderFormat: { weekday: 'long' }  // ✅ نام کامل روز
```

---

### 6️⃣ **اگر هنوز کار نکرد:**

#### Disable All Cache (موقتی):

**Chrome DevTools:**
```
1. F12
2. تب "Network"
3. تیک بزن روی "Disable cache"
4. Developer Tools رو باز نگه دار
5. صفحه رو Reload کن
```

---

## 🔍 **دیباگ:**

### بررسی شماره نسخه:
```
1. F12 → Console
2. تایپ کن:
   document.querySelector('script[src*="tasks-management"]').src
3. باید ببینی:
   ...tasks-management.js?ver=0.1.07
```

اگر ver قدیمی بود (مثلاً 0.1.06)، یعنی cache شده.

---

## 📝 **Checklist:**

```
☐ Hard Refresh زدم (Ctrl + Shift + R)
☐ Cache مرورگر رو پاک کردم
☐ Version number رو عوض کردم
☐ Developer Tools رو چک کردم
☐ firstDay: 6 رو تو source دیدم
☐ تقویم رو Reload کردم
```

---

## ✅ **نتیجه باید این باشه:**

```
شنبه | یکشنبه | دوشنبه | سه‌شنبه | چهارشنبه | پنجشنبه | جمعه
  1       2       3         4          5          6        7
```

**نه این:**
```
یکشنبه | دوشنبه | سه‌شنبه | چهارشنبه | پنجشنبه | جمعه | شنبه
   1       2         3          4          5        6       7
```

---

**99% مشکل از cache هست!** 

**Hard Refresh کافیه!** 🔄

