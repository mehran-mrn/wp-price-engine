=== MRN Gold & Currency Pricing for WooCommerce ===
Contributors: mrnwp
Tags: gold price, exchange rates, dynamic pricing, woocommerce, iran
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.1
Requires Plugins: woocommerce
Stable tag: 1.4.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Gold, coin and US dollar exchange rates for Iranian websites, with Persian rate boards and automatic WooCommerce product pricing.

== Description ==

MRN Gold & Currency Pricing displays gold prices, coin prices and currency exchange rates on Persian websites and uses those rates to update WooCommerce product prices. It is designed for Iranian shops that price goods in rial or toman using a gold rate, the US dollar exchange rate or another configured asset.

The administration interface is in Persian with RTL layouts. Use a shortcode or Gutenberg block to add a gold and currency rate board to a page. The included guide-fa.txt explains setup, pricing formulas and provider settings in Persian.

[Product website](https://mrnwp.ir/mrnnerkh) | [MRNWP](https://mrnwp.ir) | [Source and issues](https://github.com/mehran-mrn/wp-price-engine)

= Features =

* Gold, coin, US dollar and other configured currency rates, with update times and rate history.
* Persian administration screens and rate boards for Iranian websites.
* Manual rates, API providers, custom JSON and selected HTML rate sources.
* Scheduled refresh and administrator-triggered refresh; visitors read locally cached rates.
* Simple and variable WooCommerce product formulas, percentage and fixed adjustments, multipliers and rounding.
* Rial and toman conversion, and currency conversion using locally stored rate pairs.
* Cart price locks and order rate snapshots.
* Cards, tables and ticker rate boards with shortcodes and a Gutenberg block.
* Last-known rates on provider errors and configurable stale-rate handling.

= Gold, coin and exchange-rate boards =

Display selected assets such as 18-karat gold, gold coins, USD and EUR in a table, cards or a ticker. Prices can be shown in rial or toman according to the asset and store settings. Rate boards read locally stored values; visitors do not trigger requests to external rate services.

= Automatic product prices for WooCommerce =

Choose a rate for a simple product or a variation and set its quantity, multiplier, percentage adjustment, fixed adjustment and rounding. A gold shop can calculate a product from its weight and configured gold rate. A shop with dollar-based costs can calculate its toman price from a USD rate and its own pricing adjustments. No product price is invented by the plugin; configure the formula and the rate source for the shop.

The plugin also supports a cart price lock and records the rate used in an order item. Rates are refreshed on a schedule or by an administrator, and stored product prices are updated for WooCommerce sorting and price filters.

External providers are disabled on new installations. An administrator must configure and enable each provider. Manual rates do not need an external service. Service fees, quotas, credentials and data reuse permissions depend on the selected provider; local plugin features do not have a license gate.

Developer: [Mehran Marandi](https://mehranmarandi.ir). Code and bundled artwork are distributed under GPLv3 or later. Provider reference attribution is included in NOTICE.txt. Readable PHP, JavaScript and CSS sources are included; no build step is needed.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the plugin ZIP through Plugins > Add New > Upload Plugin.
3. Activate MRN Gold & Currency Pricing and open its Persian rate dashboard.
4. Create a manual rate or enable a provider after reviewing its service terms.
5. Configure the dynamic pricing tab in the WooCommerce product editor.
6. Display rates with the rate board block or `[mrnpe_price_board]`.

For upgrades from the development folders `mrn-price-engine` or `mrn-nerkh`, deactivate the old copy before activating `mrn-gold-currency-pricing`. Database tables, settings, rates, history and product formulas are preserved. Before using existing pages, change `[mrn_price_board]` to `[mrnpe_price_board]`, `[mrn_rate]` to `[mrnpe_rate]`, block names from `mrn-price-engine/price-board` to `mrnpe/price-board`, and REST integrations from `mrn-price-engine/v1` to `mrnpe/v1`. Update custom PHP integrations to the `MRNPE\Price_Engine` namespace and `mrnpe_plugin()`. Do not uninstall the old copy with its delete-data option enabled when you want to keep your rates and settings.

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

Asset definitions, rates, history, refresh logs and service settings are stored in the site's database. API keys and tokens are stored in WordPress options; restrict administrator and backup access. Product and order metadata store rate snapshots. Data deletion on uninstall is optional and disabled by default.

MRN Gold & Currency Pricing does not send usage statistics or store data to MRNWP or the developer. Display assets are local. The public REST endpoint exposes rate records and can be disabled in settings; it does not expose API keys or provider headers.

== Frequently Asked Questions ==

= Are rates real time? =

Refresh is periodic and depends on provider availability, the configured interval and WP-Cron execution. Review the last update time and stale-rate policy for an active store. Continuous provider access or a particular market rate is not guaranteed.

= Can I display gold prices and the dollar exchange rate together? =

Yes. Configure the assets and their source, then select them in the rate board block or shortcode. Iranian market rates require a provider supplying those rates; an international reference exchange-rate service does not supply the Iranian open-market dollar price.

= Can WooCommerce prices change automatically with gold or dollar rates? =

Yes. Enable dynamic pricing for a simple product or variation and choose its rate and formula. Updated local rates are used to recalculate product prices. Review the cart lock and stale-rate settings before accepting orders.

= Does it require a paid rate service? =

Manual rates work without a service. Providers offering public access can be used under their own terms. API fees, usage plans and quotas are set by the provider.

= Is the plugin intended for Iranian users? =

Yes. The interface and product documentation focus on Persian, gold, coins, rial and toman. International rate providers are available for currency conversion and crypto rates.

= Does the plugin handle tax compliance? =

The pricing formula is a calculation tool. Rates, units, adjustments, discounts and WooCommerce taxes must be configured and tested for the store's requirements.

= Where is the Persian guide? =

The installation package includes guide-fa.txt, covering setup, migration, privacy and provider usage in Persian.

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

1. Persian rate dashboard and source status in a demonstration installation.
2. Gold, currency and asset management with sample rates.
3. A Persian rate board displayed on a site with sample data.

== Changelog ==

= 1.4.1 =
* Remove nine rate-service adapters and their configuration and request endpoints.
* Convert assets assigned to retired providers to manual rates while preserving their last stored value.

= 1.4.0 =
* Rename the plugin to MRN Gold & Currency Pricing for WooCommerce.
* Use mrn-gold-currency-pricing as the package folder and translation domain.
* Prefix PHP namespaces, the public bootstrap function, shortcodes, block, REST routes and administration menu with MRNPE/mrnpe.
* Document each external rate connection, request payload and provider policy links.
* Preserve existing database tables, settings, rates and WooCommerce pricing metadata.

= 1.3.2 =
* Use the WordPress.org owner mrnwp in Contributors.
* Place the administration menu at the default bottom position and label it in Persian.
* Clarify gold, coin, dollar exchange-rate boards and automatic product pricing in the directory description.
* Keep the Persian guide and prepare a Persian directory translation separately from the English review readme.

= 1.3.1 =
* Prevent shared caching of administrator rate responses when the public REST endpoint is disabled.
* Clarify data licensing, TGJU written-permission requirements and CoinGecko attribution/storage conditions.
* Preserve all existing provider adapters and manual pricing features.

= 1.3.0 =
* Introduce MRN Gold & Currency Pricing branding, product page and developer information.
* Add the logo and WordPress menu icon.
* Disable external providers on fresh installations while keeping existing settings.
* Remove the site URL from request User-Agent headers and bound JSON response size.
* Align the translation domain and release folder with mrn-gold-currency-pricing.
* Document services, privacy and GPLv3-or-later licensing.
* Fix rate board refresh with plain WordPress permalinks.
* Fit rate cards to narrow theme content areas and mobile layouts.

= 1.2.0 =
* Add rate discovery from Milli, MelliGold and TGJU pages.
* Keep the last valid rate when source parsing fails.

= 1.1.0 =
* Convert rates into the store currency using local rate pairs.
* Expand the provider catalog and configuration.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.4.1 =
Assets assigned to retired providers become manual rates. Their last values remain stored; review them and select a supported source or update the manual rate before continuing automatic pricing.

= 1.4.0 =
Pre-release integrations must use the new mrnpe shortcodes, block name, REST routes and PHP namespace. Stored rates, settings and product formulas are preserved. See Installation for the exact migration names.

= 1.3.2 =
Directory metadata and administration menu placement have been updated. Existing rates, provider configurations and product formulas are preserved.

= 1.3.0 =
The public name is MRN Gold & Currency Pricing. Existing rates and settings are retained. External providers on a fresh installation must be enabled by an administrator.
