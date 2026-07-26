# MRN Price Engine
# نبض قیمت

<p align="center">
  <img src="https://raw.githubusercontent.com/mehran-mrn/wp-price-engine/main/assets/images/logo.svg" alt="MRN Price Engine Logo" width="128" height="128">
</p>

<p align="center">
  <strong>افزونه مستقل وردپرس قیمت‌گذاری پویا بر پایه طلا، ارز، سکه و رمزارز برای WordPress/WooCommerce</strong>
</p>

<p align="center">
  <a href="#ویژگی‌ها">ویژگی‌ها</a> •
  <a href="#نیازمندی‌ها">نیازمندی‌ها</a> •
  <a href="#نصب">نصب</a> •
  <a href="#معماری">معماری</a> •
  <a href="#منابع-داده">منابع داده</a> •
  <a href="#فرمول-محصول">فرمول محصول</a> •
  <a href="#نمایش-در-سایت">نمایش در سایت</a> •
  <a href="#امنیت-و-پایداری">امنیت و پایداری</a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/version-1.2.0-blue?style=flat-square" alt="Version">
  <img src="https://img.shields.io/badge/WooCommerce-Compatible-96588A?style=flat-square&logo=woocommerce" alt="WooCommerce">
  <img src="https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat-square&logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/WordPress-6.6+-21759B?style=flat-square&logo=wordpress" alt="WordPress">
  <img src="https://img.shields.io/badge/License-GPL--2.0--or--later-blue?style=flat-square" alt="License">
</p>

---

## ویژگی‌ها

- قیمت‌گذاری پویا بر اساس نرخ‌های لحظه‌ای طلا، ارز، سکه و رمزارز
- ۳۹+ منبع داده معتبر داخلی و بین‌المللی
- تبدیل ارز هوشمند با استفاده از الگوریتم جستجوی گراف
- فرمول‌نویسی پیشرفته با قابلیت تنظیم درصد سود، هزینه ثابت و گرد کردن
- تاریخچه نرخ‌ها برای تحلیل و گزارش‌گیری
- بلوک اختصاصی در ویرایشگر گوتنبرگ
- شورت‌کدهای قدرتمند برای نمایش در هر جای سایت
- ادغام کامل با ووکامرس (قفل قیمت در سبد خرید، ذخیره مبنای قیمت در سفارشات)
- بدون درخواست خارجی در زمان بازدید کاربر
- API REST عمومی برای تازه‌سازی جدول‌های قیمت
- امنیت بالا با استفاده از Nonce، Capability و Sanitization
- پشتیبانی از چندین ارز و تبدیل خودکار به ارز فروشگاه

---

## نیازمندی‌ها

- **WordPress** 6.6 یا بالاتر
- **PHP** 8.1 یا بالاتر
- **WooCommerce** (اختیاری - برای قیمت‌گذاری محصولات؛ تابلوی نرخ‌ها بدون ووکامرس نیز کار می‌کنند)

---

## نصب

### از طریق وردپرس
1. به **پیشخوان → افزونه‌ها → افزودن** بروید
2. فایل `mrn-price-engine.zip` را آپلود کنید
3. افزونه را فعال کنید

### از طریق گیت
```
git clone https://github.com/mehran-mrn/wp-price-engine.git
cd wp-price-engine
composer install
```

---

## معماری

درخواست خارجی هرگز در رندر صفحه یا سبد خرید اجرا نمی‌شود:

WP-Cron / refresh دستی
→ یک درخواست گروهی برای هر provider
→ قفل ضد اجرای هم‌زمان
→ ذخیره نرخ و تاریخچه در جدول محلی
→ بازسازی batch قیمت محصولات
→ نمایش/سبد/REST فقط از دیتای محلی

### جداول افزونه

| نام جدول | توضیح |
|----------|--------|
| {prefix}_mrnpe_assets | تعریف دارایی و آخرین نرخ معتبر |
| {prefix}_mrnpe_rate_history | تاریخچه تغییرات نرخ‌ها |
| {prefix}_mrnpe_logs | رخدادها و خطاهای عملیاتی |

### تبدیل ارز مبنا

اگر ارز نرخ انتخابی با ارز فروشگاه ووکامرس متفاوت باشد، افزونه قبل از اجرای فرمول از نرخ‌های جفت‌ارز ذخیره‌شده در دیتابیس استفاده می‌کند. برای نمونه، قیمت BTC_USD در فروشگاه تومانی می‌تواند از مسیر زیر محاسبه شود:

BTC_USD × USD_USDT × USDT_IRT

این تبدیل نیز فقط با داده محلی انجام می‌شود و هنگام مشاهده محصول هیچ درخواست خارجی تازه‌ای ارسال نمی‌کند.

---

## منابع داده

### منابع داخلی (بازار ایران)

- Navasan - نرخ‌های بازار ایران (نیازمند API key)
- Wallex - بازار عمومی رمزارز به تومان
- Nobitex - بازار عمومی رمزارز به ریال
- Bitpin - بازار عمومی رمزارز به تومان
- TetherLand - نرخ عمومی تتر و دارایی‌های منتخب به تومان
- Exir - بازار عمومی رمزارز
- Ramzinex - بازار عمومی رمزارز به ریال
- ArzPaya - بازار عمومی رمزارز به تومان
- AbanTether - بازار رمزارز ایران (نیازمند API key)
- Apimaster - نرخ ارز، طلا و سکه ایران (نیازمند API key)
- Bonbast - نرخ ارز بازار ایران (اتصال حساب‌محور)
- Hamyar Android - رابط قدیمی نرخ ایران (برای سازگاری با داده‌های مهاجرتی)
- Nerkh-API - نرخ ارز و طلای ایران (نیازمند کلید)
- Parsijoo - رابط قدیمی نرخ ایران
- Persian API / TGJU - نرخ‌های بازار ایران (نیازمند توکن سرویس)
- RatesBox - طلا، سکه و ارز ایران (نیازمند توکن)
- Sourcearena - نرخ ارز و طلا (نیازمند توکن)
- SunnyWeb Old - نسخه قدیمی وب‌سرویس نرخ ایران (نیازمند توکن)
- SunnyWeb New - ارز، طلا، سکه و کالا (نیازمند توکن)
- SunnyWeb Shabakeh - نرخ شبکه صرافی‌ها (نیازمند توکن)
- Taban Gohar - نرخ طلای طلا‌فروشی (اتصال حساب‌محور)
- Tala Clinic Fars - نرخ طلای ایران (نیازمند توکن)
- Tala.ir Scraper - استخراج HTML قدیمی (پیش‌فرض خاموش)
- TGJU Local - اتصال محلی به خروجی TGJU Studio
- TGJU HTML - کشف بیش از ۱۰۰ نرخ طلا، سکه، ارز و بازار جهانی (بدون کلید)
- MelliGold HTML - کشف قیمت طلا و سکه از صفحه‌های عمومی (بدون کلید)
- Milli Gold HTML - کشف قیمت یک گرم طلای ۱۸ عیار (بدون کلید)

### منابع بین‌المللی

- CoinGecko - قیمت تجمیعی رمزارزها (بدون کلید یا با کلید آزمایشی)
- Frankfurter - نرخ مرجع بانک‌های مرکزی (بدون کلید)
- Metals.Dev - فلزات گران‌بها (نیازمند API key)
- CoinMarketCap - قیمت جهانی رمزارز (پلن رایگان با کلید)
- CryptoCompare - قیمت گروهی رمزارز
- ExchangeRate-API - نرخ ارز بین‌المللی (پلن رایگان با کلید)
- Fixer - نرخ ارز بین‌المللی (پلن رایگان با کلید)
- GoldAPI - طلا، نقره، پلاتین و پالادیوم (پلن رایگان با کلید)
- MetalpriceAPI - فلزات و ارز (پلن رایگان با کلید)
- Metals-API - فلزات گران‌بها (پلن رایگان با کلید)
- Open Exchange Rates - نرخ ارز بین‌المللی (پلن رایگان با App ID)
- Commodities-API - کالا، فلز و ارز (پلن رایگان با کلید)
- Custom JSON - اتصال به API اختصاصی یا منطقه‌ای

**تعداد کل اتصالات:** ۳۹ منبع داده در کاتالوگ افزونه

> سرویس‌های عمومی پایدار بدون کلید به‌صورت پیش‌فرض فعال‌اند. اتصال‌های نیازمند کلید و endpointهای قدیمی یا مبتنی بر scraping تا زمان پیکربندی مدیر خاموش می‌مانند.

> همه providerها با فیلتر `mrnpe_rate_providers` قابل توسعه‌اند.

---

## فرمول محصول

```
material = local_rate × quantity × product_multiplier
regular  = material + (material × markup_percent / 100) + fixed_amount
sale     = regular - (regular × discount_percent / 100) - fixed_discount
```

### مثال: ۱.۴۳ گرم طلا

- **نرخ مبنا:** GOLD_18K_IRT
- **مقدار:** 1.43
- **ضریب:** 1
- **درصد:** اجرت/سود دلخواه
- **مبلغ ثابت:** بسته‌بندی، سنگ یا هزینه ثابت
- **گرد کردن:** نزدیک‌ترین/بالا/پایین با گام قابل انتخاب

> قیمت محاسبه‌شده داخل فیلدهای استاندارد WooCommerce هم ذخیره می‌شود تا sorting، price filter، Store API و lookup table درست بمانند.

---

## نمایش در سایت

### بلوک گوتنبرگ
بلوک «تابلوی قیمت MRN» در ویرایشگر گوتنبرگ ثبت می‌شود. تمام تنظیمات در نوار کناری بلوک در دسترس است.

### شورت‌کدها

**نمایش تابلوی قیمت:**
```
[mrn_price_board assets="GOLD_18K_IRT,USD_IRT,BTC_USD" layout="cards" theme="auto" columns="3" title="قیمت لحظه‌ای بازار"]
```

چیدمان‌ها: `cards`، `table` و `ticker`

**نمایش یک مقدار:**
```
[mrn_rate symbol="GOLD_18K_IRT" decimals="0"]
```

> تازه‌سازی فرانت‌اند فقط REST محلی افزونه را می‌خواند و درخواست خارجی ایجاد نمی‌کند.

---

## امنیت و پایداری

### امنیت
- Nonce و Capability روی همه mutationهای مدیریت
- wp_safe_remote_get و URL فقط HTTPS برای provider سفارشی
- API keyها فقط سمت سرور و option با autoload خاموش
- Sanitization کامل ورودی‌ها
- SQL Injection با استفاده از $wpdb->prepare

### پایداری
- آخرین نرخ معتبر در خطای provider حذف نمی‌شود
- امکان غیرفعال‌کردن خرید هنگام stale شدن نرخ
- snapshot نرخ و قیمت روی cart/order
- حذف داده‌ها در uninstall فقط با انتخاب صریح مدیر
- قفل ضد اجرای هم‌زمان برای جلوگیری از تداخل درخواست‌ها

---

## توسعه و تست

### تست
```
.\tools\verify.ps1
```

### ساخت فایل نصب
```
.\tools\package.ps1
```

---

## مجوز

این پروژه تحت مجوز **GPL-2.0-or-later** منتشر شده است.

---

## مشارکت

ما از مشارکت شما استقبال می‌کنیم! لطفاً:

1. این مخزن را Fork کنید
2. یک Branch جدید برای ویژگی یا رفع باگ خود ایجاد کنید
3. تغییرات خود را Commit و Push کنید
4. یک Pull Request با توضیحات کامل باز کنید

برای تغییرات بزرگ، لطفاً ابتدا یک Issue باز کنید تا در مورد آن گفتگو کنیم.

---

## ارتباط با توسعه‌دهنده

<p align="center">
  <a href="https://www.linkedin.com/in/mehran-marandi-644aa616b/" target="_blank">
    <img src="https://img.shields.io/badge/LinkedIn-0A66C2?style=for-the-badge&logo=linkedin&logoColor=white" alt="LinkedIn">
  </a>
  <a href="mailto:mehranmarandi90@gmail.com">
    <img src="https://img.shields.io/badge/Email-EA4335?style=for-the-badge&logo=gmail&logoColor=white" alt="Email">
  </a>
  <a href="https://t.me/Mehran_MRN" target="_blank">
    <img src="https://img.shields.io/badge/Telegram-26A5E4?style=for-the-badge&logo=telegram&logoColor=white" alt="Telegram">
  </a>
  <a href="https://github.com/mehran-mrn" target="_blank">
    <img src="https://img.shields.io/badge/GitHub-181717?style=for-the-badge&logo=github&logoColor=white" alt="GitHub">
  </a>
</p>

---

## حمایت

اگر این پروژه برای شما مفید بوده، لطفاً به آن Star دهید و با دیگران به اشتراک بگذارید. این کار به ما کمک می‌کند تا پروژه را بهبود دهیم و به کاربران بیشتری برسانیم.

---

**ساخته شده با ❤️ توسط تیم MRN**
