# MRN Price Engine
# نبض قیمت

افزونه مستقل وردپرس قیمت‌گذاری پویا بر پایه طلا، ارز، سکه و رمزارز برای WordPress/WooCommerce.

نسخه فعلی: **1.1.0**

## نیازمندی‌ها

- WordPress 6.6+
- PHP 8.1+
- WooCommerce برای قیمت‌گذاری محصول (تابلوهای نرخ بدون WooCommerce هم کار می‌کنند)

## معماری

درخواست خارجی هرگز در رندر صفحه یا سبد خرید اجرا نمی‌شود:

```text
WP-Cron / refresh دستی
  → یک درخواست گروهی برای هر provider
  → قفل ضد اجرای هم‌زمان
  → ذخیره نرخ و تاریخچه در جدول محلی
  → بازسازی batch قیمت محصولات
  → نمایش/سبد/REST فقط از دیتای محلی
```

جدول‌های افزونه:

- `{prefix}_mrnpe_assets`: تعریف دارایی و آخرین نرخ معتبر
- `{prefix}_mrnpe_rate_history`: تاریخچه تغییرات
- `{prefix}_mrnpe_logs`: رخدادها و خطاهای عملیاتی

## منابع داده

- Navasan برای بازار ایران (نیازمند API key)
- CoinGecko برای رمزارز؛ public keyless یا Demo key
- Frankfurter برای نرخ مرجع بانک‌های مرکزی، بدون API key
- Metals.Dev برای فلزات گران‌بها (نیازمند API key)
- Custom JSON برای API اختصاصی یا منطقه‌ای
- منابع عمومی بازار ایران: Wallex، Nobitex، Bitpin، TetherLand، Exir، Ramzinex و ArzPaya
- اتصال‌های API نمونه شامل CoinMarketCap، CryptoCompare، Fixer، GoldAPI، Metals-API، OpenExchangeRates و سرویس‌های نرخ ایرانی

در مجموع ۳۹ اتصال در کاتالوگ افزونه وجود دارد. سرویس‌های عمومی پایدار بدون کلید به‌صورت پیش‌فرض فعال‌اند؛ اتصال‌های نیازمند کلید و endpointهای قدیمی یا مبتنی بر scraping تا زمان پیکربندی مدیر خاموش می‌مانند.

همه providerها با فیلتر `mrnpe_rate_providers` قابل توسعه‌اند.

## تبدیل ارز مبنا

اگر ارز نرخ انتخابی با ارز فروشگاه ووکامرس متفاوت باشد، افزونه قبل از اجرای فرمول از نرخ‌های جفت‌ارز ذخیره‌شده در دیتابیس استفاده می‌کند. برای نمونه، قیمت `BTC_USD` در فروشگاه تومانی می‌تواند از مسیر زیر محاسبه شود:

```text
BTC_USD × USD_USDT × USDT_IRT
```

این تبدیل نیز فقط با داده محلی انجام می‌شود و هنگام مشاهده محصول هیچ درخواست خارجی تازه‌ای ارسال نمی‌کند.

## فرمول محصول

```text
material = local_rate × quantity × product_multiplier
regular  = material + (material × markup_percent / 100) + fixed_amount
sale     = regular - (regular × discount_percent / 100) - fixed_discount
```

مثال ۱٫۴۳ گرم طلا:

- نرخ مبنا: `GOLD_18K_IRT`
- مقدار: `1.43`
- ضریب: `1`
- درصد: اجرت/سود دلخواه
- مبلغ ثابت: بسته‌بندی، سنگ یا هزینه ثابت
- گردکردن: نزدیک‌ترین/بالا/پایین با گام قابل انتخاب

قیمت محاسبه‌شده داخل فیلدهای استاندارد WooCommerce هم ذخیره می‌شود تا sorting، price filter، Store API و lookup table درست بمانند.

## نمایش در سایت

بلوک «تابلوی قیمت MRN» در Gutenberg ثبت می‌شود.

```text
[mrn_price_board assets="GOLD_18K_IRT,USD_IRT,BTC_USD" layout="cards" theme="auto" columns="3" title="قیمت لحظه‌ای بازار"]
```

چیدمان‌ها: `cards`، `table` و `ticker`.

```text
[mrn_rate symbol="GOLD_18K_IRT" decimals="0"]
```

تازه‌سازی فرانت‌اند فقط REST محلی افزونه را می‌خواند و درخواست خارجی ایجاد نمی‌کند.

## تست

```powershell
.\tools\verify.ps1
```

ساخت فایل نصب:

```powershell
.\tools\package.ps1
```

## امنیت و پایداری

- nonce و capability روی همه mutationهای مدیریت
- `wp_safe_remote_get` و URL فقط HTTPS برای provider سفارشی
- API keyها فقط سمت سرور و option با autoload خاموش
- آخرین نرخ معتبر در خطای provider حذف نمی‌شود
- امکان غیرفعال‌کردن خرید هنگام stale شدن نرخ
- snapshot نرخ و قیمت روی cart/order
- حذف داده‌ها در uninstall فقط با انتخاب صریح مدیر

License: GPL-2.0-or-later
