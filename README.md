# MRN Gold & Currency Pricing

نرخ طلا، سکه و ارز و قیمت‌گذاری پویا برای فروشگاه‌های ایرانی.

نام معرفی در مخزن: **MRN Gold & Currency Pricing for WooCommerce**. متن فارسی صفحه و راهنمای ثبت ترجمه در [docs/wordpress-org/search-fa.md](docs/wordpress-org/search-fa.md) قرار دارد.

- [صفحه محصول](https://mrnwp.ir/mrnnerkh)
- [MRNWP](https://mrnwp.ir)
- [Mehran Marandi](https://mehranmarandi.ir)
- [کد و گزارش مشکل](https://github.com/mehran-mrn/wp-price-engine)

WordPress 6.6+، PHP 8.1+ و WooCommerce لازم است. رابط فارسی و RTL است. راهنمای فارسی در guide-fa.txt و اطلاعات مخزن، سرویس‌ها و حریم خصوصی در readme.txt قرار دارد.

## دریافت و ذخیرهٔ نرخ

WP-Cron یا تازه‌سازی مدیر، نرخ‌های منابع فعال را دریافت و در جدول‌های محلی ذخیره می‌کند. نمایش سایت، سبد خرید و REST عمومی فقط دادهٔ محلی را می‌خوانند. منابع خارجی در نصب تازه خاموش‌اند؛ فعال‌کردن هر منبع به پیکربندی مدیر و رعایت شرایط سرویس نیاز دارد. فهرست مقصدهای شبکه در services.txt است.

| جدول | کاربرد |
| --- | --- |
| `{prefix}mrnpe_assets` | دارایی‌ها و آخرین نرخ |
| `{prefix}mrnpe_rate_history` | تاریخچهٔ نرخ |
| `{prefix}mrnpe_logs` | رخدادهای دریافت و خطاها |

در خطای دریافت، آخرین نرخ معتبر حفظ می‌شود. مدیر می‌تواند خرید با نرخ قدیمی را غیرفعال کند. درخواست‌های هم‌زمان قفل می‌شوند و قیمت محصولات در دسته‌های محدود بازسازی می‌شود.

## فرمول محصول

```text
material = local_rate × quantity × product_multiplier
regular  = material + (material × markup_percent / 100) + fixed_amount
sale     = regular - (regular × discount_percent / 100) - fixed_discount
```

گرد کردن پس از محاسبه انجام می‌شود. اگر ارز نرخ با ارز فروشگاه متفاوت باشد، تبدیل با جفت‌نرخ‌های محلی انجام می‌شود. نرخ و مبنای قیمت در سبد و سفارش ثبت می‌شوند. قیمت محاسبه‌شده برای مرتب‌سازی و فیلتر کاتالوگ در فیلدهای WooCommerce نیز ذخیره می‌شود.

## نمایش در سایت

بلوک «تابلوی نرخ MRN» یا شورت‌کدهای زیر را به صفحه اضافه کنید:

```text
[mrnpe_price_board assets="GOLD_18K_IRT,USD_IRT,BTC_USD" layout="cards" theme="auto" columns="3" title="نرخ بازار"]
[mrnpe_rate symbol="GOLD_18K_IRT" decimals="0"]
```

چیدمان‌های تابلو `cards`، `table` و `ticker` هستند. تازه‌سازی مرورگر از REST محلی انجام می‌شود. کلیدها و توکن‌های سرویس در خروجی عمومی نمایش داده نمی‌شوند.

## توسعه

```powershell
./tools/verify.ps1
./tools/package.ps1
```

بستهٔ نصب و دامنهٔ ترجمه mrn-gold-currency-pricing است. جدول‌ها، تنظیمات، نرخ‌ها و فرمول محصولات حفظ می‌شوند. نسخهٔ قبلی با پوشهٔ mrn-price-engine یا mrn-nerkh را ابتدا غیرفعال کنید. نام شورت‌کدها به mrnpe_price_board و mrnpe_rate، نام بلوک به mrnpe/price-board و مسیر REST به mrnpe/v1 تغییر کرده است. راهنمای کامل ارتقا در guide-fa.txt است.

منابع با فیلتر `mrnpe_rate_providers` قابل توسعه‌اند. مسیرهای REST از پیشوند `mrnpe/v1` استفاده می‌کنند. حذف داده‌ها در uninstall تنها با انتخاب مدیر انجام می‌شود.

## مجوز

GPLv3 or later. متن مجوز در LICENSE و ارجاع مرجع اتصال‌ها در NOTICE.txt است.

## WordPress.org releases

GitHub Actions checks each push and pull request. Publishing a stable GitHub Release deploys the matching installable package and `.wordpress-org` images to `mrn-gold-currency-pricing` on WordPress.org. The release tag, plugin version and readme Stable tag must match. Configure repository secrets `SVN_USERNAME` (`mrnwp`) and `SVN_PASSWORD` (the separate WordPress.org SVN password). Pre-releases are not deployed.
