=== نرخ MRN — قیمت طلا، نرخ ارز و قیمت‌گذاری ووکامرس ===
Contributors: mrnwp
Tags: gold price, exchange rates, dynamic pricing, woocommerce, iran
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.1
Requires Plugins: woocommerce
Stable tag: 1.4.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

نمایش قیمت طلا و سکه، نرخ ارز و دلار و تغییر خودکار قیمت محصولات ووکامرس بر اساس نرخ انتخابی، با پنل و تابلوی فارسی.

== Description ==

طلا و ارز MRN قیمت طلا، سکه و نرخ ارز را روی سایت‌های فارسی نمایش می‌دهد و از همین نرخ‌ها برای تغییر خودکار قیمت محصولات ووکامرس استفاده می‌کند. این افزونه برای فروشگاه‌های ایرانی طراحی شده که قیمت کالا را به ریال یا تومان و بر مبنای نرخ طلا، قیمت دلار یا یک دارایی دیگر محاسبه می‌کنند.

رابط مدیریت فارسی و راست‌به‌چپ است. با شورت‌کد یا بلوک گوتنبرگ، تابلوی نرخ طلا و ارز را به صفحه اضافه کنید. راهنمای فارسی guide-fa.txt همراه بسته، تنظیم منابع و فرمول قیمت محصول را توضیح می‌دهد.

[صفحه افزونه](https://mrnwp.ir/mrnnerkh) | [MRNWP](https://mrnwp.ir) | [کد و گزارش مشکل](https://github.com/mehran-mrn/wp-price-engine)

= امکانات =

* قیمت طلا، سکه، دلار آمریکا و ارزهای انتخابی، همراه زمان به‌روزرسانی و تاریخچهٔ نرخ.
* پنل و تابلوی نرخ فارسی برای سایت‌های ایرانی.
* ورود نرخ دستی، اتصال API، خروجی JSON سفارشی و منابع HTML منتخب.
* دریافت نرخ با زمان‌بندی یا درخواست مدیر؛ نمایش سایت از دادهٔ محلی.
* فرمول محصولات ساده و متغیر ووکامرس، درصد و مبلغ ثابت، ضریب و گرد کردن.
* تبدیل ریال و تومان و تبدیل ارز با جفت‌نرخ‌های ذخیره‌شده روی سایت.
* قفل قیمت سبد خرید و ذخیرهٔ مبنای نرخ در سفارش.
* نمایش نرخ‌ها به شکل کارت، جدول یا نوار با شورت‌کد و بلوک گوتنبرگ.
* حفظ آخرین نرخ معتبر در خطای منبع و تنظیم رفتار نرخ قدیمی.

= تابلوی قیمت طلا، سکه و نرخ ارز =

دارایی‌هایی مثل طلای ۱۸ عیار، سکه، دلار و یورو را در قالب جدول، کارت یا نوار نمایش دهید. واحد نمایش بر اساس تنظیم دارایی و فروشگاه می‌تواند ریال یا تومان باشد. تابلو از نرخ‌های ذخیره‌شده می‌خواند و بازدیدکنندگان درخواست تازه‌ای به سرویس نرخ نمی‌فرستند.

= قیمت‌گذاری خودکار محصولات ووکامرس =

برای محصول ساده یا هر متغیر، یک نرخ انتخاب کنید و مقدار، ضریب، درصد، مبلغ ثابت و روش گرد کردن را تنظیم کنید. فروشگاه طلا می‌تواند قیمت محصول را از وزن و نرخ طلا به دست بیاورد. فروشگاهی با هزینهٔ خرید دلاری می‌تواند قیمت تومانی کالا را از نرخ دلار و تعدیل‌های خودش محاسبه کند. فرمول و منبع نرخ باید متناسب با فروشگاه تنظیم شوند.

قفل قیمت سبد خرید و ثبت مبنای نرخ در هر قلم سفارش نیز پشتیبانی می‌شود. نرخ‌ها با زمان‌بندی یا درخواست مدیر به‌روز می‌شوند و قیمت ذخیره‌شدهٔ محصولات برای مرتب‌سازی و فیلتر قیمت ووکامرس نیز محاسبه می‌شود.

اتصال‌های خارجی در نصب تازه خاموش هستند. مدیر باید هر منبع مورد نیاز را تنظیم و فعال کند. نرخ دستی به سرویس خارجی نیاز ندارد. هزینه، سهمیه، کلید دسترسی و مجوز داده به سرویس انتخابی وابسته‌اند؛ امکانات محلی افزونه قفل لایسنس ندارند.

توسعه‌دهنده: [Mehran Marandi](https://mehranmarandi.ir). کد و تصاویر همراه با مجوز GPLv3 or later عرضه می‌شوند. انتساب مرجع اتصال‌ها در NOTICE.txt و کد خوانای PHP، JavaScript و CSS در بسته قرار دارند؛ build جداگانه لازم نیست.

== Installation ==

= ارتقا از نسخهٔ پیش از انتشار =

ابتدا نسخهٔ قبلی با پوشهٔ mrn-price-engine یا mrn-nerkh را غیرفعال و سپس نسخهٔ جدید با پوشهٔ mrn-gold-currency-pricing را فعال کنید. جدول‌ها، تنظیمات، نرخ‌ها، تاریخچه و فرمول محصولات حفظ می‌شوند. نسخهٔ قبلی را با گزینهٔ حذف داده uninstall نکنید.

نام شورت‌کدهای قبلی mrn_price_board و mrn_rate را در صفحات به mrnpe_price_board و mrnpe_rate تغییر دهید. نام بلوک ذخیره‌شده از mrn-price-engine/price-board به mrnpe/price-board و مسیر REST از mrn-price-engine/v1 به mrnpe/v1 تغییر کرده است. اتصال‌های PHP باید از namespace برابر MRNPE\Price_Engine و تابع mrnpe_plugin() استفاده کنند.


1. ووکامرس را نصب و فعال کنید.
2. ZIP افزونه را از «افزونه‌ها ← افزودن ← بارگذاری افزونه» نصب کنید.
3. افزونه را فعال و منوی «نرخ MRN» را باز کنید.
4. نرخ دستی بسازید یا پس از بررسی شرایط سرویس، یک منبع را فعال کنید.
5. در ویرایش محصول ووکامرس، بخش قیمت‌گذاری پویا را تنظیم کنید.
6. برای نمایش نرخ‌ها از بلوک تابلو یا شورت‌کد `[mrnpe_price_board]` استفاده کنید.


== External services ==

Requests occur only during scheduled refreshes or an administrator-triggered refresh, and only for an enabled provider with assigned assets. This timing applies to every service below. External providers are disabled on fresh installations. Front-end rate boards and product calculations use locally stored rates; they do not contact the providers directly.

Every contacted provider receives the web server's IP address, the requested URL and HTTP headers, including the plugin name/version User-Agent. HTML requests also include language and cache-control headers, and may return a source cookie when retrying a same-page redirect. The User-Agent does not contain the store URL. The adapters do not send customer names, email addresses, billing details, cart contents or order payloads. Configured credentials are sent only to the selected service as described below.

An API subscription and permission to display, retain or redistribute its data are separate matters. No rate-service account, subscription or third-party data licence is included. Consult the selected provider's terms, including plan-specific conditions.

See the Frequently Asked Questions below for each connection, its purpose, request data and provider policy links. The complete endpoint inventory is also included in services.txt.

= Data permissions and source limitations =

Enabling a provider authorizes a network connection; it does not grant a data license. Before displaying, storing history or exposing rates through the public REST endpoint, obtain permission covering those uses and comply with the provider's plan, attribution, retention and redistribution rules. Disable the public REST endpoint when the data license does not allow onward distribution.

TGJU's [terms, clause 18](https://www.tgju.org/terms) require written agreement for reuse of its prices and data. Third-party data licenses are not included with MRN Gold & Currency Pricing. A permission covering one website must not be assumed to cover every site installing this plugin. Obtain authorization covering the intended use before enabling TGJU, Milli or MelliGold; merely being able to access a page is insufficient. [MelliGold terms](https://melligold.com/terms).

Milli's [terms, Security and Confidentiality clause 7](https://milli.gold/main/terms/) prohibit scripts for indexing, study or data mining of the service. The public widget and JSON fallback must not be assumed to override that restriction; obtain explicit permission covering the integration.

The [CoinGecko API terms](https://www.coingecko.com/en/api_terms) include attribution and data-storage conditions. The plugin does not automatically place a CoinGecko credit on the public site or implement a CoinGecko-specific retention policy. Configure the required attribution and storage controls, or obtain suitable contractual permission, before using this integration.

[Frankfurter](https://frankfurter.dev/) permits commercial API use, subject to its underlying data providers' terms. API access and permission for every form of redistribution are distinct. The plugin's GPL license covers its code and artwork, not third-party rate data.

[Metals.dev terms](https://metals.dev/policy/terms) permit publishing rates with an active subscription, but prohibit reselling and creating a directly competing API; [privacy policy](https://metals.dev/policy/privacy). The general-purpose public REST feed must be configured consistently with these restrictions.

== Privacy ==

دارایی‌ها، نرخ‌ها، تاریخچه، گزارش دریافت و تنظیمات سرویس در دیتابیس سایت ذخیره می‌شوند. کلیدها و توکن‌ها در optionهای وردپرس هستند؛ دسترسی مدیر و نسخهٔ پشتیبان را محدود کنید. مبنای نرخ در متادیتای محصول و سفارش ذخیره می‌شود. حذف داده هنگام uninstall اختیاری و پیش‌فرض خاموش است.

نرخ MRN آمار استفاده یا دادهٔ فروشگاه را برای MRNWP و توسعه‌دهنده نمی‌فرستد. فایل‌های نمایشی محلی‌اند. REST عمومی رکوردهای نرخ را نمایش می‌دهد و از تنظیمات قابل خاموش‌کردن است؛ کلید API و header سرویس در خروجی آن نیستند.

== Frequently Asked Questions ==

= آیا نرخ‌ها لحظه‌ای هستند؟ =

به‌روزرسانی دوره‌ای است و به دسترسی منبع، فاصلهٔ تنظیم‌شده و اجرای WP-Cron وابسته است. زمان آخرین دریافت و تنظیم رفتار نرخ قدیمی را در فروشگاه بررسی کنید. اتصال پیوسته یا نرخ مشخص بازار تضمین نمی‌شود.

= می‌توان قیمت طلا و نرخ دلار را کنار هم نمایش داد؟ =

بله؛ دارایی‌ها و منبع آن‌ها را تنظیم و در بلوک تابلو یا شورت‌کد انتخاب کنید. قیمت بازار ایران به منبعی نیاز دارد که همان نرخ را ارائه کند؛ نرخ مرجع بین‌المللی، قیمت دلار بازار آزاد ایران نیست.

= قیمت محصولات ووکامرس با نرخ طلا یا دلار خودکار تغییر می‌کند؟ =

بله؛ برای محصول ساده یا متغیر، قیمت‌گذاری پویا را فعال و نرخ و فرمول را تعیین کنید. با به‌روزشدن نرخ محلی، قیمت محصول دوباره محاسبه می‌شود. پیش از پذیرش سفارش، قفل سبد خرید و تنظیم نرخ قدیمی را بررسی کنید.

= سرویس پولی لازم است؟ =

نرخ دستی بدون سرویس کار می‌کند. منابع عمومی نیز تابع شرایط خودشان هستند. هزینه، پلن و سهمیه را ارائه‌دهندهٔ داده تعیین می‌کند.

= مخاطب افزونه کاربران ایرانی هستند؟ =

بله؛ رابط و راهنما بر زبان فارسی، طلا، سکه، ریال و تومان متمرکزند. منابع بین‌المللی برای تبدیل ارز و نرخ رمزارز نیز در دسترس‌اند.

= افزونه الزامات مالیاتی را اجرا می‌کند؟ =

فرمول صرفاً ابزار محاسبه است. نرخ، واحد، تعدیل، تخفیف و مالیات ووکامرس باید متناسب با فروشگاه تنظیم و بررسی شوند.

= راهنمای فارسی کجاست؟ =

فایل guide-fa.txt داخل بسته، نصب، ارتقا، حریم خصوصی و استفاده از منابع را توضیح می‌دهد.

= CoinGecko =

Cryptocurrency prices. Destination: `api.coingecko.com`.

Data sent during the enabled refresh described above: Configured coin IDs, quote currency, change/timestamp flags and the optional Demo API key in a request header.

[Terms of service](https://www.coingecko.com/en/api_terms) | [Privacy policy/provisions](https://www.coingecko.com/en/privacy).

= Frankfurter =

International reference exchange rates; this is not the Iranian open-market exchange rate. Destination: `api.frankfurter.dev`.

Data sent during the enabled refresh described above: The configured base currency and selected target currency codes; no API key.

[Terms of service](https://frankfurter.dev/license/) | [Privacy policy/provisions](https://frankfurter.dev/#faq).

The FAQ contains the API privacy policy under "What is the privacy policy of the API?".

= Metals.Dev =

Precious-metal prices. Destination: `api.metals.dev`.

Data sent during the enabled refresh described above: The configured API key, quote currency and weight unit in query parameters.

[Terms of service](https://metals.dev/policy/terms) | [Privacy policy/provisions](https://metals.dev/policy/privacy).

= Nobitex =

Public cryptocurrency market prices. Destination: `apiv2.nobitex.ir`.

Data sent during the enabled refresh described above: A request for the public market ticker list; no account credentials or customer information.

[Terms of service](https://nobitex.ir/policies/?tab=terms) | [Privacy policy/provisions](https://nobitex.ir/policies/?tab=privacy).

= Bitpin =

Public cryptocurrency market prices. Destination: `api.bitpin.ir`.

Data sent during the enabled refresh described above: A request for the public market list; no account credentials or customer information.

[Terms of service](https://bitpin.ir/rules/) | [Privacy policy/provisions](https://bitpin.ir/rules/).

The combined document includes section 4, protection of privacy and user rights.

= Exir =

Public cryptocurrency market prices. Destination: `api.exir.io`.

Data sent during the enabled refresh described above: A request for the public ticker list; no account credentials or customer information.

[Terms of service](https://www.exir.io/terms_privacy/) | [Privacy policy/provisions](https://www.exir.io/terms_privacy/).

The combined terms include a personal-information protection section.

= TetherLand =

Public cryptocurrency prices. Destination: `api.tetherland.com`.

Data sent during the enabled refresh described above: A request for the public currency list; no account credentials or customer information.

[Terms of service](https://tetherland.com/terms/) | [Privacy policy/provisions](https://tetherland.com/terms/).

The combined document includes privacy and confidentiality provisions.

= Ramzinex =

Public cryptocurrency market prices. Destination: `publicapi.ramzinex.com`.

Data sent during the enabled refresh described above: A request for the public exchange-price list; no account credentials or customer information.

[Terms of service](https://ramzinex.ir/info/terms/) | [Privacy policy/provisions](https://ramzinex.ir/info/terms/).

The combined document includes protection of personal information.

= ArzPaya =

Public cryptocurrency market prices. Destination: `api.arzpaya.com`.

Data sent during the enabled refresh described above: A request for public exchange information; no account credentials or customer information.

[Terms of service](https://arzpaya.com/rules/) | [Privacy policy/provisions](https://arzpaya.com/rules/).

The combined document includes confidentiality of user information in clause 7.

= Aban Tether =

Cryptocurrency prices from the account API. Destination: `abantether.com`.

Data sent during the enabled refresh described above: The configured API key in the Authorization header and the JSON response-format parameter.

[Terms of service](https://abantether.ir/rules) | [Privacy policy/provisions](https://abantether.ir/rules).

The official site links to this combined document; its identity-verification section covers confidentiality and disclosure to authorities.

= CoinMarketCap =

Cryptocurrency quotes in USD. Destination: `pro-api.coinmarketcap.com`.

Data sent during the enabled refresh described above: Selected cryptocurrency symbols, the skip-invalid flag and the configured API key in the X-CMC_PRO_API_KEY header.

[Terms of service](https://coinmarketcap.com/terms/) | [Privacy policy/provisions](https://coinmarketcap.com/privacy/).

= CryptoCompare =

Cryptocurrency prices. Destination: `min-api.cryptocompare.com`.

Data sent during the enabled refresh described above: Selected cryptocurrency symbols, the quote currency, the static application identifier MRNPriceEngine and an optional API key in a request header.

[Terms of service](https://www.cryptocompare.com/terms-conditions/) | [Privacy policy/provisions](https://www.cryptocompare.com/privacy-policy/).

= ExchangeRate-API =

International currency conversion rates. Destination: `v6.exchangerate-api.com`.

Data sent during the enabled refresh described above: The configured API key and base currency in the request path.

[Terms of service](https://www.exchangerate-api.com/terms) | [Privacy policy/provisions](https://www.exchangerate-api.com/terms).

Terms and Privacy Policy are sections of the same document.

= Fixer / APILayer =

International currency conversion rates. Destination: `api.apilayer.com`.

Data sent during the enabled refresh described above: The configured base and selected currency codes in the query, and the API key in the apikey header.

[Terms of service](https://www.ideracorp.com/Legal/Terms-of-Use) | [Privacy policy/provisions](https://www.ideracorp.com/en/legal/privacypolicy).

APILayer legal resources: https://www.ideracorp.com/legal/APILayer . Additional subscription and data-use terms can apply to the selected plan.

= Commodities-API =

Commodity prices converted from USD quotes. Destination: `commodities-api.com`.

Data sent during the enabled refresh described above: The configured API key, USD base and selected commodity symbols in the query.

[Terms of service](https://commodities-api.com/terms#terms-conditions) | [Privacy policy/provisions](https://commodities-api.com/terms#privacy).

= MetalpriceAPI =

Metal and currency reference rates. Destination: `api.metalpriceapi.com`.

Data sent during the enabled refresh described above: The configured API key, base currency and selected currency/metal codes in the query.

[Terms of service](https://metalpriceapi.com/terms) | [Privacy policy/provisions](https://metalpriceapi.com/privacy).

= Metals-API =

Precious-metal rates. Destination: `metals-api.com`.

Data sent during the enabled refresh described above: The configured API key, base currency and selected metal/currency symbols in the query.

[Terms of service](https://metals-api.com/terms#terms-conditions) | [Privacy policy/provisions](https://metals-api.com/terms#privacy).

= GoldAPI =

Precious-metal prices and gram/karat quotes. Destination: `www.goldapi.io`.

Data sent during the enabled refresh described above: Each selected metal code and USD in the request path, with the configured API key in the x-access-token header. One request is made per selected metal.

[Terms of service](https://www.goldapi.io/blog/terms-of-service) | [Privacy policy/provisions](https://www.goldapi.io/blog/privacy-policy).

= Open Exchange Rates =

International reference exchange rates. Destination: `openexchangerates.org`.

Data sent during the enabled refresh described above: The configured App ID and selected currency symbols in the query.

[Terms of service](https://openexchangerates.org/terms) | [Privacy policy/provisions](https://openexchangerates.org/privacy).

= Milli =

The 18-karat gold rate from the public widget, with a public JSON fallback when the widget cannot be parsed. Destination: `milli.gold`.

Data sent during the enabled refresh described above: Requests for the widget and, if necessary, the public price endpoint; no account credentials or customer information.

[Terms of service](https://milli.gold/main/terms/) | [Privacy policy/provisions](https://milli.gold/main/privacy/).

= MelliGold =

Gold and coin rates parsed from the selected public market pages. Destination: `melligold.com`.

Data sent during the enabled refresh described above: The selected market-page paths; no account credentials or customer information. A source cookie can be returned on a same-page redirect retry.

[Terms of service](https://melligold.com/terms) | [Privacy policy/provisions](https://melligold.com/terms).

The combined document includes an "امنیت و محرمانگی" (Security and Confidentiality) section.

= TGJU =

Gold, currency, coin and other rates parsed from public market pages. Destination: `www.tgju.org`.

Data sent during the enabled refresh described above: Requests for the public pages; no account credentials or customer information. A source cookie can be returned on a same-page redirect retry.

[Terms of service](https://www.tgju.org/terms) | [Privacy policy/provisions](https://www.tgju.org/terms).

The combined document addresses collection of member IP/contact information and restrictions on disclosure in clauses 12-13. It does not provide a separate detailed policy for anonymous page requests.

= Custom JSON and TGJU Local =

These are configurable connections rather than a bundled third-party service. No destination is set on a fresh installation. During an enabled refresh, the plugin sends a GET request to the exact HTTPS URL chosen by the administrator, including its configured query parameters. Custom JSON also sends the configured request headers, which can contain credentials. TGJU Local sends the URL parameters and the standard request headers only. The remote operator receives the server IP and plugin User-Agent. The provider's terms and privacy policy depend on that chosen destination; review both before entering a URL or enabling the connection. These adapters are not a proxy operated by MRNWP.

= Legacy catalog entries =

Bonbast, Hamyar Android, Metals Live, OK-EX.io, Parsijoo, Persian API, Taban Gohar, Tala Clinic Fars and Tala.ir Scraper are compatibility entries without a supported fetch endpoint. They return an unavailable-adapter error and do not transmit data. Their presence in existing settings does not activate a network connection.

== Screenshots ==

1. پیشخوان فارسی نرخ‌ها و وضعیت منابع در نصب نمایشی.
2. مدیریت طلا، ارز و دارایی‌ها با نرخ‌های نمونه.
3. تابلوی فارسی نرخ‌ها روی سایت با دادهٔ نمایشی.

== Changelog ==

= 1.4.1 =
* حذف ۹ اتصال نرخ و تنظیمات و مقصدهای درخواست آن‌ها.
* تبدیل دارایی‌های منابع حذف‌شده به نرخ دستی با حفظ مقدارهای قبلی.

= 1.4.0 =
* نام و شناسهٔ جدید برای انتشار عمومی.
* پیشوند MRNPE/mrnpe برای namespace، تابع عمومی، شورت‌کدها، بلوک و مسیر REST.
* توضیح مقصد و داده‌های ارسالی هر سرویس و پیوند شرایط و حریم خصوصی آن.
* حفظ جدول‌ها، تنظیمات، نرخ‌ها و فرمول محصولات.

= 1.3.2 =
* اصلاح نام کاربری سازندهٔ مخزن به mrnwp.
* انتقال منو به انتهای منوهای مدیریت با عنوان فارسی.
* توضیح کاربردهای نرخ طلا، سکه، دلار و قیمت‌گذاری خودکار محصول.
* حفظ راهنمای فارسی و آماده‌سازی جداگانهٔ ترجمهٔ صفحهٔ مخزن.

== Upgrade Notice ==

= 1.4.1 =
دارایی‌های متصل به منابع حذف‌شده به نرخ دستی تبدیل می‌شوند و مقدار قبلی حفظ می‌شود. نرخ‌ها را بررسی و منبع پشتیبانی‌شده انتخاب یا نرخ دستی را به‌روز کنید.

= 1.3.2 =
اطلاعات مخزن و جایگاه منوی مدیریت به‌روز شده‌اند. نرخ‌ها، تنظیم منابع و فرمول محصولات حفظ می‌شوند.
