# MRN Price Engine
# نبض قیمت

<p align="center">
  <img src="https://raw.githubusercontent.com/mehran-mrn/wp-price-engine/main/assets/images/logo.svg" alt="MRN Price Engine Logo" width="128" height="128">
</p>

<p align="center">
  <strong>افزونه مستقل وردپرس قیمت‌گذاری پویا بر پایه طلا، ارز، سکه و رمزارز برای WordPress/WooCommerce</strong>
</p>

<p align="center">
  <a href="#-ویژگی‌ها">ویژگی‌ها</a> •
  <a href="#-نیازمندی‌ها">نیازمندی‌ها</a> •
  <a href="#-نصب">نصب</a> •
  <a href="#-معماری">معماری</a> •
  <a href="#-منابع-داده">منابع داده</a> •
  <a href="#-فرمول-محصول">فرمول محصول</a> •
  <a href="#-نمایش-در-سایت">نمایش در سایت</a> •
  <a href="#-امنیت-و-پایداری">امنیت و پایداری</a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/version-1.2.0-blue?style=flat-square" alt="Version">
  <img src="https://img.shields.io/badge/WooCommerce-Compatible-96588A?style=flat-square&logo=woocommerce" alt="WooCommerce">
  <img src="https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat-square&logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/WordPress-6.6+-21759B?style=flat-square&logo=wordpress" alt="WordPress">
  <img src="https://img.shields.io/badge/License-GPL--2.0--or--later-blue?style=flat-square" alt="License">
</p>

---

## ✨ ویژگی‌ها

- **قیمت‌گذاری پویا** بر اساس نرخ‌های لحظه‌ای طلا، ارز، سکه و رمزارز
- **۳۹+ منبع داده** معتبر داخلی و بین‌المللی
- **تبدیل ارز هوشمند** با استفاده از الگوریتم جستجوی گراف
- **فرمول‌نویسی پیشرفته** با قابلیت تنظیم درصد سود، هزینه ثابت و گرد کردن
- **تاریخچه نرخ‌ها** برای تحلیل و گزارش‌گیری
- **بلوک اختصاصی** در ویرایشگر گوتنبرگ
- **شورت‌کدهای قدرتمند** برای نمایش در هر جای سایت
- **ادغام کامل با ووکامرس** (قفل قیمت در سبد خرید، ذخیره مبنای قیمت در سفارشات)
- **بدون درخواست خارجی** در زمان بازدید کاربر
- **API REST عمومی** برای تازه‌سازی جدول‌های قیمت
- **امنیت بالا** با استفاده از Nonce، Capability و Sanitization
- **پشتیبانی از چندین ارز** و تبدیل خودکار به ارز فروشگاه

---

## 📋 نیازمندی‌ها

- **WordPress** 6.6 یا بالاتر
- **PHP** 8.1 یا بالاتر
- **WooCommerce** (اختیاری - برای قیمت‌گذاری محصولات؛ تابلوی نرخ‌ها بدون ووکامرس نیز کار می‌کنند)

---

## 🚀 نصب

### از طریق وردپرس
1. به **پیشخوان → افزونه‌ها → افزودن** بروید
2. فایل `mrn-price-engine.zip` را آپلود کنید
3. افزونه را فعال کنید

### از طریق گیت
```bash
git clone https://github.com/mehran-mrn/mrn-price-engine.git
cd mrn-price-engine
composer install
