=== MRN Price Engine ===
Contributors: mrn
Tags: woocommerce, dynamic pricing, gold, currency, crypto, iran
Requires at least: 6.6
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional locally cached gold, currency and crypto rates with dynamic WooCommerce product pricing.

== Description ==

MRN Price Engine fetches provider rates only through scheduled or administrator-triggered jobs, stores them locally, and calculates WooCommerce prices from quantity, multiplier, percentage and fixed adjustments.

Features include:

* 42 rate-provider integrations, including public Iranian crypto markets, HTML discovery, free-key services and configurable JSON.
* Request coalescing, provider pacing and concurrent-run locking.
* Automatic cached currency conversion for assets whose quote currency differs from the WooCommerce store currency.
* Simple and variable WooCommerce product formulas.
* Stored prices for catalog sorting and filtering plus local runtime calculation.
* Cart price locks and auditable order rate snapshots.
* Gutenberg rate board block and customizable table, card and ticker shortcodes.
* Persian-first responsive administration UI.

== Installation ==

1. Upload the plugin ZIP in Plugins > Add New.
2. Activate MRN Price Engine.
3. Open "نبض قیمت" and configure at least one source.
4. Fetch rates, then select a rate in the product's "قیمت‌گذاری پویا" tab.

== Changelog ==

= 1.2.0 =
* Add bounded HTML rate discovery for Milli Gold, MelliGold and TGJU.
* Discover more than 100 TGJU symbols with one cached page request.
* Seed practical gold, coin and currency rates while preserving merchant-created assets.
* Keep the last valid rate when a public page changes and its parser cannot validate the result.

= 1.1.0 =
* Convert USD-quoted crypto rates to the WooCommerce store currency through locally cached rate pairs.
* Add the complete provider catalog from the reference plugin; stable no-key providers are available by default.
* Add searchable provider tiers and upgrade-safe provider settings.
* Fix escaped WooCommerce price markup in the product rate selector and refine the admin menu icon.

= 1.0.0 =
* Initial professional release.
