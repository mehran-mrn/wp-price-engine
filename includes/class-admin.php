<?php
/**
 * Persian administration screens.
 *
 * @package MRN_Price_Engine
 */

namespace MRNPE\Price_Engine;

defined( 'ABSPATH' ) || exit;

final class Admin {
	private const PAGE = 'mrnpe-pricing';

	public function __construct(
		private Asset_Repository $assets,
		private Provider_Manager $providers,
		private Database $database,
		private Pricing_Engine $pricing
	) {}

	/**
	 * Register menu, assets and protected form handlers.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MRNPE_FILE ), array( $this, 'plugin_links' ) );

		$actions = array(
			'save_asset',
			'delete_asset',
			'save_settings',
			'save_providers',
			'refresh',
			'recalculate',
		);
		foreach ( $actions as $action ) {
			add_action( 'admin_post_mrnpe_' . $action, array( $this, 'handle_' . $action ) );
		}
	}

	/**
	 * Add the top-level branded menu.
	 *
	 * @return void
	 */
	public function menu(): void {
		$capability = 'manage_woocommerce';
		$icon       = MRNPE_URL . 'assets/images/logo-menu.png';

		add_menu_page(
			__( 'MRN Gold & Currency Pricing', 'mrn-gold-currency-pricing' ),
			__( 'قیمت‌گذاری MRN', 'mrn-gold-currency-pricing' ),
			$capability,
			self::PAGE,
			array( $this, 'render' ),
			$icon
		);

		add_submenu_page( self::PAGE, __( 'داشبورد نرخ‌ها', 'mrn-gold-currency-pricing' ), __( 'داشبورد', 'mrn-gold-currency-pricing' ), $capability, self::PAGE, array( $this, 'render' ) );
		add_submenu_page( self::PAGE, __( 'مدیریت نرخ‌ها', 'mrn-gold-currency-pricing' ), __( 'نرخ‌ها', 'mrn-gold-currency-pricing' ), $capability, self::PAGE . '-rates', array( $this, 'render' ) );
		add_submenu_page( self::PAGE, __( 'منابع داده', 'mrn-gold-currency-pricing' ), __( 'منابع داده', 'mrn-gold-currency-pricing' ), $capability, self::PAGE . '-sources', array( $this, 'render' ) );
		add_submenu_page( self::PAGE, __( 'نمایش در سایت', 'mrn-gold-currency-pricing' ), __( 'نمایش در سایت', 'mrn-gold-currency-pricing' ), $capability, self::PAGE . '-display', array( $this, 'render' ) );
		add_submenu_page( self::PAGE, __( 'تنظیمات و ابزارها', 'mrn-gold-currency-pricing' ), __( 'تنظیمات', 'mrn-gold-currency-pricing' ), $capability, self::PAGE . '-settings', array( $this, 'render' ) );
	}


	/**
	 * Load scoped assets on plugin and product editor screens.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public function assets( string $hook ): void {
		wp_enqueue_style( 'mrnpe-menu', MRNPE_URL . 'assets/css/menu.css', array(), MRNPE_VERSION );
		$screen         = get_current_screen();
		$is_plugin_page = str_contains( $hook, self::PAGE );
		$is_product     = $screen && 'product' === $screen->post_type;

		if ( ! $is_plugin_page && ! $is_product ) {
			return;
		}

		wp_enqueue_style( 'mrnpe-admin', MRNPE_URL . 'assets/css/admin.css', array(), MRNPE_VERSION );
		wp_enqueue_script( 'mrnpe-admin', MRNPE_URL . 'assets/js/admin.js', array(), MRNPE_VERSION, true );

		$asset_data    = array();
		$currency_code = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'IRT';
		foreach ( $this->assets->all() as $asset ) {
			$conversion = $this->pricing->normalize_asset_rate( $asset, $currency_code );
			$asset_data[ (string) $asset->id ] = array(
				'name'            => $asset->name,
				'value'           => null !== $asset->value ? (float) $asset->value : null,
				'unit'            => $asset->unit,
				'currency'        => $asset->quote_currency,
				'localValue'      => is_wp_error( $conversion ) ? null : (float) $conversion['value'],
				'conversionPath'  => is_wp_error( $conversion ) ? '' : implode( ' → ', (array) $conversion['path'] ),
				'conversionError' => is_wp_error( $conversion ) ? $conversion->get_error_message() : '',
			);
		}

		wp_localize_script(
			'mrnpe-admin',
			'mrnpe_admin',
			array(
					'assets'        => $asset_data,
					'currency'      => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : 'IRT',
					'currencyCode'  => $currency_code,
					'confirmDelete' => __( 'این نرخ و تاریخچه‌اش حذف شود؟ این کار قابل بازگشت نیست.', 'mrn-gold-currency-pricing' ),
					'copied'        => __( 'کد کپی شد', 'mrn-gold-currency-pricing' ),
					'locale'        => str_replace( '_', '-', get_locale() ),
					'previewLabels' => array(
						'missing'    => __( 'نرخ معتبر انتخاب نشده است', 'mrn-gold-currency-pricing' ),
						'converted'  => __( 'نرخ تبدیل‌شده', 'mrn-gold-currency-pricing' ),
						'path'       => __( 'مسیر تبدیل', 'mrn-gold-currency-pricing' ),
					),
				)
			);
	}

	/**
	 * Add direct settings link on Plugins.
	 *
	 * @param array<int, string> $links Links.
	 * @return array<int, string>
	 */
	public function plugin_links( array $links ): array {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE ) ) . '">' . esc_html__( 'داشبورد نرخ', 'mrn-gold-currency-pricing' ) . '</a>' );
		return $links;
	}

	/**
	 * Render the current submenu route.
	 *
	 * @return void
	 */
	public function render(): void {
		$this->authorize();
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : self::PAGE;
		$tab  = str_replace( self::PAGE . '-', '', $page );
		$tab  = self::PAGE === $page ? 'dashboard' : $tab;
		?>
		<div class="wrap mrnpe-admin" dir="rtl">
			<?php $this->render_header( $tab ); ?>
			<?php $this->render_notice(); ?>
			<main class="mrnpe-admin__content">
				<?php
				match ( $tab ) {
					'rates'    => $this->render_rates(),
					'sources'  => $this->render_sources(),
					'display'  => $this->render_display(),
					'settings' => $this->render_settings(),
					default    => $this->render_dashboard(),
				};
		?>
			</main>
			<footer class="mrnpe-admin__footer">
				<span><a href="https://mrnwp.ir/mrnnerkh" target="_blank" rel="noopener noreferrer">MRN Gold & Currency Pricing</a> <b>v<?php echo esc_html( MRNPE_VERSION ); ?></b></span>
				<span><a href="https://mrnwp.ir" target="_blank" rel="noopener noreferrer">MRNWP</a> · <a href="https://mehranmarandi.ir" target="_blank" rel="noopener noreferrer">Mehran Marandi</a></span>
			</footer>
		</div>
		<?php
	}

	/**
	 * Branded header and navigation.
	 *
	 * @param string $tab Current tab.
	 * @return void
	 */
	private function render_header( string $tab ): void {
		$tabs = array(
			'dashboard' => array( self::PAGE, __( 'داشبورد', 'mrn-gold-currency-pricing' ) ),
			'rates'     => array( self::PAGE . '-rates', __( 'نرخ‌ها', 'mrn-gold-currency-pricing' ) ),
			'sources'   => array( self::PAGE . '-sources', __( 'منابع داده', 'mrn-gold-currency-pricing' ) ),
			'display'   => array( self::PAGE . '-display', __( 'نمایش در سایت', 'mrn-gold-currency-pricing' ) ),
			'settings'  => array( self::PAGE . '-settings', __( 'تنظیمات', 'mrn-gold-currency-pricing' ) ),
		);
		?>
		<header class="mrnpe-admin__hero">
			<div class="mrnpe-admin__brand">
				<img src="<?php echo esc_url( MRNPE_URL . 'assets/images/logo.png' ); ?>" alt="" width="58" height="58">
				<div>
					<span>MRN GOLD &amp; CURRENCY PRICING</span>
					<h1><?php esc_html_e( 'قیمت‌گذاری MRN', 'mrn-gold-currency-pricing' ); ?></h1>
					<p><?php esc_html_e( 'مرکز امن و سریع نرخ‌های طلا، ارز و دارایی‌های دیجیتال', 'mrn-gold-currency-pricing' ); ?></p>
				</div>
			</div>
			<div class="mrnpe-admin__hero-art" aria-hidden="true">
				<img src="<?php echo esc_url( MRNPE_URL . 'assets/images/admin-hero.svg' ); ?>" alt="">
			</div>
			<div class="mrnpe-admin__hero-actions">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="mrnpe_refresh">
					<?php wp_nonce_field( 'mrnpe_refresh' ); ?>
					<button class="mrnpe-button mrnpe-button--primary" type="submit"><span class="dashicons dashicons-update"></span><?php esc_html_e( 'دریافت نرخ‌ها', 'mrn-gold-currency-pricing' ); ?></button>
				</form>
				<span class="mrnpe-secure"><i></i><?php esc_html_e( 'کش محلی فعال', 'mrn-gold-currency-pricing' ); ?></span>
			</div>
		</header>
		<nav class="mrnpe-tabs" aria-label="<?php esc_attr_e( 'بخش‌های افزونه', 'mrn-gold-currency-pricing' ); ?>">
			<?php foreach ( $tabs as $key => $item ) : ?>
				<a class="<?php echo $tab === $key ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $item[0] ) ); ?>"><?php echo esc_html( $item[1] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Dashboard status and operational summary.
	 *
	 * @return void
	 */
	private function render_dashboard(): void {
		$assets   = $this->assets->all();
		$active   = array_filter( $assets, static fn( object $asset ): bool => 'active' === $asset->status );
		$state    = (array) get_option( 'mrnpe_provider_state', array() );
		$dynamic  = $this->count_dynamic_products();
		$last_run = (int) ( $state['last_run'] ?? 0 );
		$next_run = wp_next_scheduled( 'mrnpe_refresh_rates' );
		?>
		<section class="mrnpe-stats">
			<?php $this->stat_card( __( 'نرخ تعریف‌شده', 'mrn-gold-currency-pricing' ), count( $assets ), __( 'دارایی قابل استفاده', 'mrn-gold-currency-pricing' ), 'chart-line' ); ?>
			<?php $this->stat_card( __( 'نرخ سالم', 'mrn-gold-currency-pricing' ), count( $active ), count( $assets ) ? round( count( $active ) / count( $assets ) * 100 ) . '% پوشش' : '—', 'yes-alt' ); ?>
			<?php $this->stat_card( __( 'محصول پویا', 'mrn-gold-currency-pricing' ), $dynamic, __( 'محصول و متغیر', 'mrn-gold-currency-pricing' ), 'products' ); ?>
			<?php $this->stat_card( __( 'درخواست خارجی', 'mrn-gold-currency-pricing' ), (int) ( $state['request_count_today'] ?? 0 ), __( 'امروز؛ صفحات سایت صفر', 'mrn-gold-currency-pricing' ), 'cloud' ); ?>
		</section>

		<div class="mrnpe-grid mrnpe-grid--dashboard">
			<section class="mrnpe-panel mrnpe-health">
				<div class="mrnpe-panel__head">
					<div><span class="mrnpe-kicker"><?php esc_html_e( 'وضعیت سیستم', 'mrn-gold-currency-pricing' ); ?></span><h2><?php esc_html_e( 'همه‌چیز زیر کنترل است', 'mrn-gold-currency-pricing' ); ?></h2></div>
					<span class="mrnpe-health__score"><?php echo esc_html( count( $assets ) ? (string) round( count( $active ) / count( $assets ) * 100 ) : '0' ); ?><small>/100</small></span>
				</div>
				<div class="mrnpe-health__bar"><i style="width:<?php echo esc_attr( count( $assets ) ? (string) round( count( $active ) / count( $assets ) * 100 ) : '0' ); ?>%"></i></div>
				<ul class="mrnpe-checklist">
					<li><span class="dashicons dashicons-database"></span><div><b><?php esc_html_e( 'ذخیره محلی نرخ‌ها', 'mrn-gold-currency-pricing' ); ?></b><small><?php esc_html_e( 'فرانت‌اند هیچ اتصال مستقیمی به APIها ندارد.', 'mrn-gold-currency-pricing' ); ?></small></div><em><?php esc_html_e( 'فعال', 'mrn-gold-currency-pricing' ); ?></em></li>
					<li><span class="dashicons dashicons-clock"></span><div><b><?php esc_html_e( 'زمان‌بندی خودکار', 'mrn-gold-currency-pricing' ); ?></b><small><?php echo esc_html( $next_run ? sprintf( /* translators: %s: Time until the next scheduled refresh. */ __( 'اجرای بعدی %s دیگر', 'mrn-gold-currency-pricing' ), human_time_diff( time(), $next_run ) ) : __( 'در انتظار زمان‌بندی', 'mrn-gold-currency-pricing' ) ); ?></small></div><em><?php echo esc_html( Settings::get( 'refresh_interval', 15 ) . ' دقیقه' ); ?></em></li>
					<li><span class="dashicons dashicons-lock"></span><div><b><?php esc_html_e( 'قفل درخواست هم‌زمان', 'mrn-gold-currency-pricing' ); ?></b><small><?php esc_html_e( 'Cron و به‌روزرسانی دستی هرگز هم‌زمان API را صدا نمی‌زنند.', 'mrn-gold-currency-pricing' ); ?></small></div><em><?php esc_html_e( 'ایمن', 'mrn-gold-currency-pricing' ); ?></em></li>
				</ul>
			</section>

			<aside class="mrnpe-panel mrnpe-quickstart">
				<span class="mrnpe-kicker"><?php esc_html_e( 'شروع سریع', 'mrn-gold-currency-pricing' ); ?></span>
				<h2><?php esc_html_e( 'اولین محصول طلایی در سه گام', 'mrn-gold-currency-pricing' ); ?></h2>
				<ol>
					<li><b>۱</b><span><?php esc_html_e( 'منبع داده را فعال و کلید API را ذخیره کنید.', 'mrn-gold-currency-pricing' ); ?></span></li>
					<li><b>۲</b><span><?php esc_html_e( 'نرخ طلای ۱۸ عیار را بررسی یا تعریف کنید.', 'mrn-gold-currency-pricing' ); ?></span></li>
					<li><b>۳</b><span><?php esc_html_e( 'در ویرایش محصول، تب «قیمت‌گذاری پویا» را باز کنید.', 'mrn-gold-currency-pricing' ); ?></span></li>
				</ol>
				<a class="mrnpe-button mrnpe-button--soft" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE . '-sources' ) ); ?>"><?php esc_html_e( 'پیکربندی منابع', 'mrn-gold-currency-pricing' ); ?><span class="dashicons dashicons-arrow-left-alt"></span></a>
			</aside>
		</div>

		<section class="mrnpe-panel">
			<div class="mrnpe-panel__head">
				<div><span class="mrnpe-kicker"><?php esc_html_e( 'نرخ‌های کلیدی', 'mrn-gold-currency-pricing' ); ?></span><h2><?php esc_html_e( 'آخرین تصویر بازار محلی', 'mrn-gold-currency-pricing' ); ?></h2></div>
				<span class="mrnpe-muted"><?php echo esc_html( $last_run ? sprintf( /* translators: %s: Time since the last refresh. */ __( 'آخرین بررسی %s پیش', 'mrn-gold-currency-pricing' ), human_time_diff( $last_run, time() ) ) : __( 'هنوز اجرا نشده', 'mrn-gold-currency-pricing' ) ); ?></span>
			</div>
			<?php $this->render_rate_cards( array_slice( $assets, 0, 6 ) ); ?>
		</section>
		<?php
	}

	/**
	 * Complete asset manager.
	 *
	 * @return void
	 */
	private function render_rates(): void {
		$assets = $this->assets->all();
		$edit   = isset( $_GET['edit'] ) ? $this->assets->find( absint( $_GET['edit'] ) ) : null;
		?>
		<div class="mrnpe-page-title">
			<div><span class="mrnpe-kicker"><?php esc_html_e( 'دارایی‌ها', 'mrn-gold-currency-pricing' ); ?></span><h2><?php esc_html_e( 'مدیریت نرخ‌ها و واحدها', 'mrn-gold-currency-pricing' ); ?></h2><p><?php esc_html_e( 'هر ردیف یک منبع قابل استفاده در فرمول محصول یا تابلوی قیمت است.', 'mrn-gold-currency-pricing' ); ?></p></div>
			<button type="button" class="mrnpe-button mrnpe-button--primary" data-mrnpe-open-asset><span class="dashicons dashicons-plus-alt2"></span><?php esc_html_e( 'نرخ جدید', 'mrn-gold-currency-pricing' ); ?></button>
		</div>
		<section class="mrnpe-panel mrnpe-table-panel">
			<div class="mrnpe-table-toolbar">
				<label><span class="dashicons dashicons-search"></span><input type="search" placeholder="<?php esc_attr_e( 'جست‌وجوی نام یا نماد…', 'mrn-gold-currency-pricing' ); ?>" data-mrnpe-table-search></label>
				<span><?php echo esc_html( sprintf( /* translators: %d: Number of configured rates. */ __( '%d نرخ تعریف شده', 'mrn-gold-currency-pricing' ), count( $assets ) ) ); ?></span>
			</div>
			<div class="mrnpe-responsive-table">
				<table class="mrnpe-data-table">
					<thead><tr><th><?php esc_html_e( 'دارایی', 'mrn-gold-currency-pricing' ); ?></th><th><?php esc_html_e( 'قیمت محلی', 'mrn-gold-currency-pricing' ); ?></th><th><?php esc_html_e( 'منبع', 'mrn-gold-currency-pricing' ); ?></th><th><?php esc_html_e( 'تغییر', 'mrn-gold-currency-pricing' ); ?></th><th><?php esc_html_e( 'وضعیت', 'mrn-gold-currency-pricing' ); ?></th><th><span class="screen-reader-text"><?php esc_html_e( 'عملیات', 'mrn-gold-currency-pricing' ); ?></span></th></tr></thead>
					<tbody>
					<?php foreach ( $assets as $asset ) : ?>
						<tr data-mrnpe-search="<?php echo esc_attr( strtolower( $asset->name . ' ' . $asset->symbol . ' ' . $asset->provider ) ); ?>">
							<td><div class="mrnpe-asset-cell"><i><?php echo esc_html( mb_substr( $asset->name, 0, 1 ) ); ?></i><span><b><?php echo esc_html( $asset->name ); ?></b><small><?php echo esc_html( $asset->symbol . ' · ' . $asset->unit ); ?></small></span></div></td>
							<td><b class="mrnpe-price"><?php echo esc_html( null !== $asset->value ? number_format_i18n( (float) $asset->value, 0 ) : '—' ); ?></b><small><?php echo esc_html( $asset->quote_currency ); ?></small></td>
							<td><code><?php echo esc_html( $asset->provider ); ?></code><small><?php echo esc_html( $asset->provider_key ); ?></small></td>
							<td><?php $this->render_change( (float) $asset->change_percent ); ?></td>
							<td><?php $this->render_status( $asset ); ?></td>
							<td class="mrnpe-row-actions">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE . '-rates&edit=' . $asset->id ) ); ?>" title="<?php esc_attr_e( 'ویرایش', 'mrn-gold-currency-pricing' ); ?>"><span class="dashicons dashicons-edit"></span></a>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-mrnpe-delete>
									<input type="hidden" name="action" value="mrnpe_delete_asset"><input type="hidden" name="id" value="<?php echo esc_attr( (string) $asset->id ); ?>">
									<?php wp_nonce_field( 'mrnpe_delete_asset_' . $asset->id ); ?>
									<button type="submit" title="<?php esc_attr_e( 'حذف', 'mrn-gold-currency-pricing' ); ?>"><span class="dashicons dashicons-trash"></span></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>
		<?php $this->render_asset_form( $edit ); ?>
		<?php
	}

	/**
	 * Provider settings cards.
	 *
	 * @return void
	 */
	private function render_sources(): void {
		$configs      = Settings::providers();
		$state        = (array) get_option( 'mrnpe_provider_state', array() );
		$descriptions = array(
			'coingecko'   => __( 'قیمت تجمیعی رمزارزها؛ یک درخواست برای صدها شناسه', 'mrn-gold-currency-pricing' ),
			'frankfurter' => __( 'نرخ مرجع بانک‌های مرکزی؛ رایگان و بدون کلید API', 'mrn-gold-currency-pricing' ),
			'metals_dev'  => __( 'طلای جهانی، نقره، پلاتین و پالادیوم با واحد دلخواه', 'mrn-gold-currency-pricing' ),
			'custom_json' => __( 'اتصال امن به API اختصاصی یا هر خروجی JSON ساختاریافته', 'mrn-gold-currency-pricing' ),
		);
			?>
			<div class="mrnpe-page-title"><div><span class="mrnpe-kicker"><?php esc_html_e( 'اتصالات', 'mrn-gold-currency-pricing' ); ?></span><h2><?php esc_html_e( 'منابع داده', 'mrn-gold-currency-pricing' ); ?></h2><p><?php esc_html_e( 'منابع بیرونی در نصب تازه خاموش هستند. با فعال‌سازی منبع، درخواست دریافت نرخ از سرور سایت شما به آن سرویس ارسال می‌شود. پیش از فعال‌سازی شرایط استفاده و مجوز نمایش دادهٔ منبع را بررسی کنید.', 'mrn-gold-currency-pricing' ); ?></p></div></div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mrnpe_save_providers">
				<?php wp_nonce_field( 'mrnpe_save_providers' ); ?>
				<div class="mrnpe-source-tools">
					<label>
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
						<span class="screen-reader-text"><?php esc_html_e( 'جستجوی منبع', 'mrn-gold-currency-pricing' ); ?></span>
						<input type="search" data-mrnpe-source-search placeholder="<?php esc_attr_e( 'جستجو میان منابع داده…', 'mrn-gold-currency-pricing' ); ?>">
					</label>
					<div class="mrnpe-source-filters" data-mrnpe-source-filters>
						<button type="button" class="is-active" data-tier="all"><?php esc_html_e( 'همه', 'mrn-gold-currency-pricing' ); ?></button>
						<button type="button" data-tier="public"><?php esc_html_e( 'رایگان و بدون کلید', 'mrn-gold-currency-pricing' ); ?></button>
						<button type="button" data-tier="free-key"><?php esc_html_e( 'پلن رایگان با کلید', 'mrn-gold-currency-pricing' ); ?></button>
						<button type="button" data-tier="paid"><?php esc_html_e( 'تجاری', 'mrn-gold-currency-pricing' ); ?></button>
						<button type="button" data-tier="scraper"><?php esc_html_e( 'کشف از HTML', 'mrn-gold-currency-pricing' ); ?></button>
						<button type="button" data-tier="legacy"><?php esc_html_e( 'قدیمی / سفارشی', 'mrn-gold-currency-pricing' ); ?></button>
					</div>
				</div>
				<div class="mrnpe-sources">
					<?php foreach ( $this->providers->all() as $key => $provider ) : ?>
						<?php
						$config         = $configs[ $key ];
						$provider_state = $state[ $key ] ?? array();
						$description    = method_exists( $provider, 'description' ) ? $provider->description() : ( $descriptions[ $key ] ?? '' );
						$native_tiers   = array(
							'coingecko'   => 'public',
							'frankfurter' => 'public',
							'metals_dev'  => 'free-key',
							'custom_json' => 'legacy',
						);
						$tier           = method_exists( $provider, 'tier' ) ? $provider->tier() : ( $native_tiers[ $key ] ?? 'paid' );
						$tier_labels    = array(
							'public'   => __( 'رایگان، بدون کلید', 'mrn-gold-currency-pricing' ),
							'free-key' => __( 'پلن رایگان با کلید', 'mrn-gold-currency-pricing' ),
							'paid'     => __( 'تجاری', 'mrn-gold-currency-pricing' ),
							'scraper'  => __( 'کشف از HTML', 'mrn-gold-currency-pricing' ),
							'legacy'   => __( 'قدیمی / سفارشی', 'mrn-gold-currency-pricing' ),
						);
						?>
						<section class="mrnpe-source <?php echo 'yes' === $config['enabled'] ? 'is-enabled' : ''; ?>" data-provider-tier="<?php echo esc_attr( $tier ); ?>" data-provider-search="<?php echo esc_attr( $provider->label() . ' ' . $description . ' ' . $key ); ?>">
							<header>
								<div class="mrnpe-source__logo"><?php echo esc_html( mb_substr( $provider->label(), 0, 1 ) ); ?></div>
								<div><h3><?php echo esc_html( $provider->label() ); ?> <small class="mrnpe-tier mrnpe-tier--<?php echo esc_attr( $tier ); ?>"><?php echo esc_html( $tier_labels[ $tier ] ?? $tier ); ?></small></h3><p><?php echo esc_html( $description ); ?></p></div>
								<label class="mrnpe-switch"><input type="checkbox" name="providers[<?php echo esc_attr( $key ); ?>][enabled]" value="yes" <?php checked( $config['enabled'], 'yes' ); ?>><i></i><span><?php esc_html_e( 'فعال', 'mrn-gold-currency-pricing' ); ?></span></label>
						</header>
						<div class="mrnpe-source__meta">
							<span><i class="dashicons dashicons-clock"></i><?php echo esc_html( ! empty( $provider_state['last_success'] ) ? sprintf( /* translators: %s: Time since the last successful provider refresh. */ __( 'موفق: %s پیش', 'mrn-gold-currency-pricing' ), human_time_diff( (int) $provider_state['last_success'], time() ) ) : __( 'بدون دریافت موفق', 'mrn-gold-currency-pricing' ) ); ?></span>
							<span><i class="dashicons dashicons-database"></i><?php echo esc_html( sprintf( /* translators: %d: Number of assets assigned to this provider. */ __( '%d نرخ متصل', 'mrn-gold-currency-pricing' ), count( $this->assets->all( array( 'provider' => $key ) ) ) ) ); ?></span>
							<?php
							if ( ! empty( $provider_state['last_error'] ) ) :
								?>
								<span class="has-error"><i class="dashicons dashicons-warning"></i><?php echo esc_html( (string) $provider_state['last_error'] ); ?></span><?php endif; ?>
						</div>
						<div class="mrnpe-source__fields">
							<label><span><?php esc_html_e( 'فاصله دریافت (دقیقه)', 'mrn-gold-currency-pricing' ); ?></span><input type="number" min="1" name="providers[<?php echo esc_attr( $key ); ?>][interval]" value="<?php echo esc_attr( (string) $config['interval'] ); ?>"></label>
							<?php $this->render_provider_fields( $key, $config ); ?>
						</div>
					</section>
				<?php endforeach; ?>
			</div>
			<div class="mrnpe-sticky-actions"><span><?php esc_html_e( 'کلیدهای API هرگز به مرورگر بازدیدکننده ارسال نمی‌شوند.', 'mrn-gold-currency-pricing' ); ?></span><button class="mrnpe-button mrnpe-button--primary" type="submit"><?php esc_html_e( 'ذخیره منابع', 'mrn-gold-currency-pricing' ); ?></button></div>
		</form>
		<?php
	}

	/**
	 * Shortcode builder and native block help.
	 *
	 * @return void
	 */
	private function render_display(): void {
		$symbols = implode( ',', array_slice( wp_list_pluck( $this->assets->all(), 'symbol' ), 0, 4 ) );
		?>
		<div class="mrnpe-page-title"><div><span class="mrnpe-kicker"><?php esc_html_e( 'ویترین بازار', 'mrn-gold-currency-pricing' ); ?></span><h2><?php esc_html_e( 'نمایش در سایت', 'mrn-gold-currency-pricing' ); ?></h2><p><?php esc_html_e( 'با بلوک بومی وردپرس یا شورت‌کد، تابلوی قیمت را دقیقاً مطابق طراحی سایت بسازید.', 'mrn-gold-currency-pricing' ); ?></p></div></div>
		<div class="mrnpe-grid mrnpe-grid--display">
			<section class="mrnpe-panel mrnpe-builder">
				<div class="mrnpe-panel__head"><div><span class="mrnpe-kicker"><?php esc_html_e( 'سازنده شورت‌کد', 'mrn-gold-currency-pricing' ); ?></span><h2><?php esc_html_e( 'خروجی دلخواه شما', 'mrn-gold-currency-pricing' ); ?></h2></div></div>
				<div class="mrnpe-builder__fields">
					<label><span><?php esc_html_e( 'عنوان', 'mrn-gold-currency-pricing' ); ?></span><input type="text" value="قیمت لحظه‌ای بازار" data-shortcode-field="title"></label>
					<label><span><?php esc_html_e( 'نمادها با ویرگول', 'mrn-gold-currency-pricing' ); ?></span><input type="text" value="<?php echo esc_attr( $symbols ); ?>" data-shortcode-field="assets"></label>
					<label><span><?php esc_html_e( 'چیدمان', 'mrn-gold-currency-pricing' ); ?></span><select data-shortcode-field="layout"><option value="cards"><?php esc_html_e( 'کارت‌ها', 'mrn-gold-currency-pricing' ); ?></option><option value="table"><?php esc_html_e( 'جدول', 'mrn-gold-currency-pricing' ); ?></option><option value="ticker"><?php esc_html_e( 'نوار قیمت', 'mrn-gold-currency-pricing' ); ?></option></select></label>
					<label><span><?php esc_html_e( 'پوسته', 'mrn-gold-currency-pricing' ); ?></span><select data-shortcode-field="theme"><option value="auto"><?php esc_html_e( 'خودکار', 'mrn-gold-currency-pricing' ); ?></option><option value="light"><?php esc_html_e( 'روشن', 'mrn-gold-currency-pricing' ); ?></option><option value="dark"><?php esc_html_e( 'تیره', 'mrn-gold-currency-pricing' ); ?></option></select></label>
					<label><span><?php esc_html_e( 'ستون', 'mrn-gold-currency-pricing' ); ?></span><input type="number" min="1" max="6" value="3" data-shortcode-field="columns"></label>
					<label><span><?php esc_html_e( 'رنگ تأکیدی', 'mrn-gold-currency-pricing' ); ?></span><input type="color" value="<?php echo esc_attr( (string) Settings::get( 'accent_color', '#b8872e' ) ); ?>" data-shortcode-field="accent"></label>
				</div>
				<div class="mrnpe-code"><code data-shortcode-output>[mrnpe_price_board assets="<?php echo esc_html( $symbols ); ?>" layout="cards" theme="auto" columns="3" title="قیمت لحظه‌ای بازار"]</code><button type="button" data-copy-shortcode><span class="dashicons dashicons-clipboard"></span><?php esc_html_e( 'کپی', 'mrn-gold-currency-pricing' ); ?></button></div>
			</section>
			<aside class="mrnpe-panel mrnpe-block-card">
				<div class="mrnpe-block-card__icon"><span class="dashicons dashicons-layout"></span></div>
				<span class="mrnpe-kicker"><?php esc_html_e( 'ویرایشگر بلوک', 'mrn-gold-currency-pricing' ); ?></span>
				<h2><?php esc_html_e( 'بدون کد طراحی کنید', 'mrn-gold-currency-pricing' ); ?></h2>
				<p><?php esc_html_e( 'در ویرایشگر وردپرس عبارت «تابلوی قیمت MRN» را جست‌وجو کنید. همه تنظیمات در نوار کناری بلوک در دسترس است.', 'mrn-gold-currency-pricing' ); ?></p>
				<div class="mrnpe-block-card__badge"><span class="dashicons dashicons-wordpress"></span> Gutenberg Native Block</div>
			</aside>
		</div>
		<section class="mrnpe-panel">
			<div class="mrnpe-panel__head"><div><span class="mrnpe-kicker"><?php esc_html_e( 'مرجع کوتاه', 'mrn-gold-currency-pricing' ); ?></span><h2><?php esc_html_e( 'نمایش یک مقدار', 'mrn-gold-currency-pricing' ); ?></h2></div></div>
			<div class="mrnpe-reference"><code>[mrnpe_rate symbol="GOLD_18K_IRT" decimals="0"]</code><p><?php esc_html_e( 'برای استفاده داخل تیتر، متن، جدول‌ساز یا قالب محصول.', 'mrn-gold-currency-pricing' ); ?></p></div>
		</section>
		<?php
	}

	/**
	 * General settings, queue controls and logs.
	 *
	 * @return void
	 */
	private function render_settings(): void {
		$settings = Settings::all();
		$logs     = $this->database->logs( 20 );
		?>
		<div class="mrnpe-page-title"><div><span class="mrnpe-kicker"><?php esc_html_e( 'کنترل مرکزی', 'mrn-gold-currency-pricing' ); ?></span><h2><?php esc_html_e( 'تنظیمات و ابزارها', 'mrn-gold-currency-pricing' ); ?></h2></div></div>
		<div class="mrnpe-grid mrnpe-grid--settings">
			<form class="mrnpe-panel mrnpe-settings-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mrnpe_save_settings"><?php wp_nonce_field( 'mrnpe_save_settings' ); ?>
				<div class="mrnpe-panel__head"><div><span class="mrnpe-kicker"><?php esc_html_e( 'رفتار افزونه', 'mrn-gold-currency-pricing' ); ?></span><h2><?php esc_html_e( 'به‌روزرسانی و ایمنی قیمت', 'mrn-gold-currency-pricing' ); ?></h2></div></div>
				<div class="mrnpe-form-grid">
					<?php $this->number_setting( 'refresh_interval', __( 'اجرای Cron (دقیقه)', 'mrn-gold-currency-pricing' ), $settings, 1 ); ?>
					<?php $this->number_setting( 'request_timeout', __( 'مهلت API (ثانیه)', 'mrn-gold-currency-pricing' ), $settings, 3 ); ?>
					<?php $this->number_setting( 'stale_after_minutes', __( 'قدیمی‌شدن نرخ (دقیقه)', 'mrn-gold-currency-pricing' ), $settings, 1 ); ?>
					<?php $this->number_setting( 'history_retention_days', __( 'نگهداری تاریخچه (روز)', 'mrn-gold-currency-pricing' ), $settings, 1 ); ?>
					<?php $this->number_setting( 'frontend_refresh', __( 'تازه‌سازی نمایشی (ثانیه)', 'mrn-gold-currency-pricing' ), $settings, 0 ); ?>
					<?php $this->number_setting( 'default_rounding_step', __( 'گام گرد کردن پیش‌فرض', 'mrn-gold-currency-pricing' ), $settings, 0 ); ?>
					<label><span><?php esc_html_e( 'رفتار نرخ قدیمی', 'mrn-gold-currency-pricing' ); ?></span><select name="settings[stale_policy]"><option value="last_known" <?php selected( $settings['stale_policy'], 'last_known' ); ?>><?php esc_html_e( 'استفاده از آخرین نرخ معتبر', 'mrn-gold-currency-pricing' ); ?></option><option value="disable_purchase" <?php selected( $settings['stale_policy'], 'disable_purchase' ); ?>><?php esc_html_e( 'غیرفعال‌کردن خرید', 'mrn-gold-currency-pricing' ); ?></option></select></label>
					<label><span><?php esc_html_e( 'روش گرد کردن پیش‌فرض', 'mrn-gold-currency-pricing' ); ?></span><select name="settings[default_rounding_mode]"><option value="nearest" <?php selected( $settings['default_rounding_mode'], 'nearest' ); ?>><?php esc_html_e( 'نزدیک‌ترین', 'mrn-gold-currency-pricing' ); ?></option><option value="up" <?php selected( $settings['default_rounding_mode'], 'up' ); ?>><?php esc_html_e( 'رو به بالا', 'mrn-gold-currency-pricing' ); ?></option><option value="down" <?php selected( $settings['default_rounding_mode'], 'down' ); ?>><?php esc_html_e( 'رو به پایین', 'mrn-gold-currency-pricing' ); ?></option></select></label>
					<label><span><?php esc_html_e( 'رنگ تأکیدی', 'mrn-gold-currency-pricing' ); ?></span><input type="color" name="settings[accent_color]" value="<?php echo esc_attr( (string) $settings['accent_color'] ); ?>"></label>
				</div>
				<div class="mrnpe-toggle-list">
					<?php $this->toggle_setting( 'cart_price_lock', __( 'قفل قیمت هنگام افزودن به سبد', 'mrn-gold-currency-pricing' ), __( 'نوسان نرخ، مبلغ همان سبد را تغییر نمی‌دهد.', 'mrn-gold-currency-pricing' ), $settings ); ?>
					<?php $this->toggle_setting( 'show_product_breakdown', __( 'نمایش مبنای قیمت در محصول', 'mrn-gold-currency-pricing' ), __( 'یک توضیح کوتاه و شفاف زیر قیمت نشان داده می‌شود.', 'mrn-gold-currency-pricing' ), $settings ); ?>
					<?php $this->toggle_setting( 'public_rest_api', __( 'REST عمومی فقط-خواندنی', 'mrn-gold-currency-pricing' ), __( 'برای تازه‌سازی جدول‌های سایت از داده محلی.', 'mrn-gold-currency-pricing' ), $settings ); ?>
					<?php $this->toggle_setting( 'delete_on_uninstall', __( 'حذف داده هنگام uninstall', 'mrn-gold-currency-pricing' ), __( 'در حالت عادی داده‌ها برای نصب مجدد حفظ می‌شوند.', 'mrn-gold-currency-pricing' ), $settings ); ?>
				</div>
				<button class="mrnpe-button mrnpe-button--primary" type="submit"><?php esc_html_e( 'ذخیره تنظیمات', 'mrn-gold-currency-pricing' ); ?></button>
			</form>
			<aside class="mrnpe-panel mrnpe-tools">
				<div class="mrnpe-panel__head"><div><span class="mrnpe-kicker"><?php esc_html_e( 'عملیات نگهداری', 'mrn-gold-currency-pricing' ); ?></span><h2><?php esc_html_e( 'ابزارها', 'mrn-gold-currency-pricing' ); ?></h2></div></div>
				<div class="mrnpe-tool">
					<span class="dashicons dashicons-update-alt"></span><div><b><?php esc_html_e( 'بازسازی همه قیمت‌ها', 'mrn-gold-currency-pricing' ); ?></b><p><?php esc_html_e( 'قیمت ذخیره‌شده و lookup ووکامرس را با آخرین نرخ هماهنگ می‌کند.', 'mrn-gold-currency-pricing' ); ?></p></div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="mrnpe_recalculate"><?php wp_nonce_field( 'mrnpe_recalculate' ); ?><button class="mrnpe-button mrnpe-button--soft" type="submit"><?php esc_html_e( 'شروع', 'mrn-gold-currency-pricing' ); ?></button></form>
				</div>
				<div class="mrnpe-tool">
					<span class="dashicons dashicons-info-outline"></span><div><b><?php esc_html_e( 'وضعیت زمان‌بندی', 'mrn-gold-currency-pricing' ); ?></b><p><?php echo esc_html( wp_next_scheduled( 'mrnpe_refresh_rates' ) ? sprintf( /* translators: %s: Date and time of the next refresh. */ __( 'اجرای بعدی: %s', 'mrn-gold-currency-pricing' ), wp_date( 'Y/m/d H:i:s', wp_next_scheduled( 'mrnpe_refresh_rates' ) ) ) : __( 'Cron ثبت نشده است؛ با ذخیره تنظیمات بازسازی می‌شود.', 'mrn-gold-currency-pricing' ) ); ?></p></div>
				</div>
			</aside>
		</div>
		<section class="mrnpe-panel mrnpe-logs">
			<div class="mrnpe-panel__head"><div><span class="mrnpe-kicker"><?php esc_html_e( 'رویدادهای اخیر', 'mrn-gold-currency-pricing' ); ?></span><h2><?php esc_html_e( 'گزارش سلامت منابع', 'mrn-gold-currency-pricing' ); ?></h2></div></div>
			<?php
			if ( ! $logs ) :
				?>
				<p class="mrnpe-muted"><?php esc_html_e( 'هنوز رویدادی ثبت نشده است.', 'mrn-gold-currency-pricing' ); ?></p><?php endif; ?>
			<ul>
				<?php
				foreach ( $logs as $log ) :
					?>
					<li class="is-<?php echo esc_attr( $log->level ); ?>"><i></i><time><?php echo esc_html( wp_date( 'Y/m/d H:i', strtotime( $log->created_at . ' UTC' ) ) ); ?></time><code><?php echo esc_html( $log->source ); ?></code><span><?php echo esc_html( $log->message ); ?></span></li><?php endforeach; ?>
			</ul>
		</section>
		<?php
	}

	/**
	 * Handle asset create/update.
	 *
	 * @return void
	 */
	public function handle_save_asset(): void {
		$this->authorize();
		check_admin_referer( 'mrnpe_save_asset' );
		$data = isset( $_POST['asset'] ) ? wp_unslash( (array) $_POST['asset'] ) : array();
		$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( empty( $data['symbol'] ) || empty( $data['name'] ) ) {
			$this->redirect( 'rates', 'asset-invalid' );
		}

		$this->assets->save( $data, $id );
		$this->redirect( 'rates', 'asset-saved' );
	}

	/**
	 * Handle asset deletion, protecting referenced products.
	 *
	 * @return void
	 */
	public function handle_delete_asset(): void {
		$this->authorize();
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		check_admin_referer( 'mrnpe_delete_asset_' . $id );

		global $wpdb;
		$references = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_mrnpe_asset_id' AND meta_value = %s", (string) $id ) );
		if ( $references > 0 ) {
			$this->redirect( 'rates', 'asset-in-use' );
		}

		$this->assets->delete( $id );
		$this->redirect( 'rates', 'asset-deleted' );
	}

	/**
	 * Save general plugin behavior and rebuild cron.
	 *
	 * @return void
	 */
	public function handle_save_settings(): void {
		$this->authorize();
		check_admin_referer( 'mrnpe_save_settings' );
		$raw      = isset( $_POST['settings'] ) ? wp_unslash( (array) $_POST['settings'] ) : array();
		$defaults = Settings::defaults();
		$saved    = array(
			'refresh_interval'       => max( 1, absint( $raw['refresh_interval'] ?? $defaults['refresh_interval'] ) ),
			'request_timeout'        => max( 3, min( 30, absint( $raw['request_timeout'] ?? $defaults['request_timeout'] ) ) ),
			'history_retention_days' => max( 1, absint( $raw['history_retention_days'] ?? $defaults['history_retention_days'] ) ),
			'stale_after_minutes'    => max( 1, absint( $raw['stale_after_minutes'] ?? $defaults['stale_after_minutes'] ) ),
			'stale_policy'           => in_array( $raw['stale_policy'] ?? '', array( 'last_known', 'disable_purchase' ), true ) ? $raw['stale_policy'] : 'last_known',
			'cart_price_lock'        => isset( $raw['cart_price_lock'] ) ? 'yes' : 'no',
			'show_product_breakdown' => isset( $raw['show_product_breakdown'] ) ? 'yes' : 'no',
			'public_rest_api'        => isset( $raw['public_rest_api'] ) ? 'yes' : 'no',
			'frontend_refresh'       => max( 0, absint( $raw['frontend_refresh'] ?? $defaults['frontend_refresh'] ) ),
			'default_rounding_step'  => max( 0, (float) ( $raw['default_rounding_step'] ?? $defaults['default_rounding_step'] ) ),
			'default_rounding_mode'  => in_array( $raw['default_rounding_mode'] ?? '', array( 'nearest', 'up', 'down' ), true ) ? $raw['default_rounding_mode'] : 'nearest',
			'accent_color'           => sanitize_hex_color( (string) ( $raw['accent_color'] ?? $defaults['accent_color'] ) ) ?: $defaults['accent_color'],
			'delete_on_uninstall'    => isset( $raw['delete_on_uninstall'] ) ? 'yes' : 'no',
		);
		update_option( 'mrnpe_settings', $saved, false );
		wp_clear_scheduled_hook( 'mrnpe_refresh_rates' );
		wp_schedule_event( time() + MINUTE_IN_SECONDS, 'mrnpe_interval', 'mrnpe_refresh_rates' );
		$this->redirect( 'settings', 'settings-saved' );
	}

	/**
	 * Save provider credentials and parsing settings.
	 *
	 * @return void
	 */
	public function handle_save_providers(): void {
		$this->authorize();
		check_admin_referer( 'mrnpe_save_providers' );
		$raw      = isset( $_POST['providers'] ) ? wp_unslash( (array) $_POST['providers'] ) : array();
		$current  = Settings::providers();
		$defaults = Settings::provider_defaults();
		$saved    = array();

		foreach ( $defaults as $key => $fields ) {
			$input         = isset( $raw[ $key ] ) ? (array) $raw[ $key ] : array();
			$saved[ $key ] = array(
				'enabled'  => isset( $input['enabled'] ) ? 'yes' : 'no',
				'interval' => max( 1, absint( $input['interval'] ?? $fields['interval'] ) ),
			);

			foreach ( $fields as $field => $default ) {
				if ( in_array( $field, array( 'enabled', 'interval' ), true ) ) {
					continue;
				}

					$value    = isset( $input[ $field ] ) ? trim( (string) $input[ $field ] ) : '';
					$provider = $this->providers->all()[ $key ] ?? null;
					$schema   = $provider && method_exists( $provider, 'fields' ) ? (array) $provider->fields() : array();
					$secret   = ! empty( $schema[ $field ]['secret'] ) || in_array( $field, array( 'api_key', 'token', 'password', 'username' ), true );
					if ( $secret && '' === $value ) {
						$value = (string) ( $current[ $key ][ $field ] ?? '' );
					}

				if ( 'url' === $field ) {
					$value = esc_url_raw( $value, array( 'https' ) );
				} elseif ( 'headers' === $field ) {
					$decoded = json_decode( $value, true );
					$value   = is_array( $decoded ) ? wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE ) : '';
				} else {
					$value = sanitize_text_field( $value );
				}
				$saved[ $key ][ $field ] = $value;
			}
		}

		update_option( 'mrnpe_providers', $saved, false );
		$this->redirect( 'sources', 'providers-saved' );
	}

	/**
	 * Force rate retrieval.
	 *
	 * @return void
	 */
	public function handle_refresh(): void {
		$this->authorize();
		check_admin_referer( 'mrnpe_refresh' );
		$report = $this->providers->refresh( true );
		set_transient( 'mrnpe_last_manual_report_' . get_current_user_id(), $report, MINUTE_IN_SECONDS );
		$this->redirect( 'dashboard', $report['ok'] ? 'refresh-success' : 'refresh-partial' );
	}

	/**
	 * Queue full WooCommerce materialization.
	 *
	 * @return void
	 */
	public function handle_recalculate(): void {
		$this->authorize();
		check_admin_referer( 'mrnpe_recalculate' );
		do_action( 'mrnpe_rates_updated', array() );
		$this->redirect( 'settings', 'recalculation-queued' );
	}

	/**
	 * Form and UI helpers below.
	 */
	private function render_asset_form( ?object $asset ): void {
		$providers = array( 'manual' => __( 'دستی', 'mrn-gold-currency-pricing' ) );
		foreach ( $this->providers->all() as $key => $provider ) {
			$providers[ $key ] = $provider->label();
		}
		?>
		<div class="mrnpe-asset-modal <?php echo $asset ? 'is-open' : ''; ?>" data-mrnpe-asset-modal>
			<button class="mrnpe-asset-modal__backdrop" type="button" data-mrnpe-close-asset aria-label="<?php esc_attr_e( 'بستن', 'mrn-gold-currency-pricing' ); ?>"></button>
			<form class="mrnpe-asset-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mrnpe_save_asset"><input type="hidden" name="id" value="<?php echo esc_attr( $asset ? (string) $asset->id : '0' ); ?>"><?php wp_nonce_field( 'mrnpe_save_asset' ); ?>
				<header><div><span class="mrnpe-kicker"><?php echo esc_html( $asset ? __( 'ویرایش دارایی', 'mrn-gold-currency-pricing' ) : __( 'دارایی جدید', 'mrn-gold-currency-pricing' ) ); ?></span><h2><?php echo esc_html( $asset ? $asset->name : __( 'تعریف نرخ قابل استفاده', 'mrn-gold-currency-pricing' ) ); ?></h2></div><button type="button" data-mrnpe-close-asset><span class="dashicons dashicons-no-alt"></span></button></header>
				<div class="mrnpe-form-grid">
					<label><span><?php esc_html_e( 'نام نمایشی', 'mrn-gold-currency-pricing' ); ?></span><input required type="text" name="asset[name]" value="<?php echo esc_attr( $asset->name ?? '' ); ?>" placeholder="طلای ۱۸ عیار"></label>
					<label><span><?php esc_html_e( 'نماد یکتا', 'mrn-gold-currency-pricing' ); ?></span><input required dir="ltr" type="text" name="asset[symbol]" value="<?php echo esc_attr( $asset->symbol ?? '' ); ?>" placeholder="GOLD_18K_IRT"></label>
					<label><span><?php esc_html_e( 'دسته', 'mrn-gold-currency-pricing' ); ?></span><select name="asset[category]">
					<?php
					foreach ( array(
						'metal'    => 'فلز',
						'currency' => 'ارز',
						'coin'     => 'سکه',
						'crypto'   => 'رمزارز',
						'custom'   => 'سفارشی',
					) as $key => $label ) :
						?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $asset->category ?? '', $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'واحد دارایی', 'mrn-gold-currency-pricing' ); ?></span><input type="text" name="asset[unit]" value="<?php echo esc_attr( $asset->unit ?? '' ); ?>" placeholder="گرم"></label>
					<label><span><?php esc_html_e( 'ارز قیمت', 'mrn-gold-currency-pricing' ); ?></span><input dir="ltr" type="text" name="asset[quote_currency]" value="<?php echo esc_attr( $asset->quote_currency ?? 'IRT' ); ?>" placeholder="IRT"></label>
					<label><span><?php esc_html_e( 'منبع', 'mrn-gold-currency-pricing' ); ?></span><select name="asset[provider]" data-asset-provider>
					<?php
					foreach ( $providers as $key => $label ) :
						?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $asset->provider ?? 'manual', $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'کلید در منبع', 'mrn-gold-currency-pricing' ); ?></span><input dir="ltr" type="text" name="asset[provider_key]" value="<?php echo esc_attr( $asset->provider_key ?? '' ); ?>" placeholder="geram18 / bitcoin / EUR"></label>
					<label><span><?php esc_html_e( 'قیمت دستی', 'mrn-gold-currency-pricing' ); ?></span><input type="number" step="0.000001" name="asset[value]" value="<?php echo esc_attr( isset( $asset->value ) ? (string) $asset->value : '' ); ?>"></label>
					<label><span><?php esc_html_e( 'ضریب تبدیل ورودی', 'mrn-gold-currency-pricing' ); ?></span><input type="number" step="0.000001" name="asset[multiplier]" value="<?php echo esc_attr( isset( $asset->multiplier ) ? (string) $asset->multiplier : '1' ); ?>"><small><?php esc_html_e( 'مثلاً ۰٫۱ برای ریال به تومان', 'mrn-gold-currency-pricing' ); ?></small></label>
					<label><span><?php esc_html_e( 'تعدیل ثابت نرخ', 'mrn-gold-currency-pricing' ); ?></span><input type="number" step="0.000001" name="asset[adjustment]" value="<?php echo esc_attr( isset( $asset->adjustment ) ? (string) $asset->adjustment : '0' ); ?>"></label>
				</div>
				<footer><button type="button" class="mrnpe-button mrnpe-button--ghost" data-mrnpe-close-asset><?php esc_html_e( 'انصراف', 'mrn-gold-currency-pricing' ); ?></button><button type="submit" class="mrnpe-button mrnpe-button--primary"><?php esc_html_e( 'ذخیره نرخ', 'mrn-gold-currency-pricing' ); ?></button></footer>
			</form>
		</div>
		<?php
	}

	private function render_provider_fields( string $key, array $config ): void {
		$provider = $this->providers->all()[ $key ] ?? null;
		if ( $provider && method_exists( $provider, 'fields' ) ) {
			foreach ( (array) $provider->fields() as $field => $schema ) {
				$type        = in_array( $schema['type'] ?? 'text', array( 'text', 'password', 'url', 'number' ), true ) ? $schema['type'] : 'text';
				$is_secret   = ! empty( $schema['secret'] );
				$value       = $is_secret ? '' : (string) ( $config[ $field ] ?? '' );
				$placeholder = $is_secret
					? ( empty( $config[ $field ] ) ? __( 'وارد نشده', 'mrn-gold-currency-pricing' ) : __( 'ذخیره شده — برای حفظ خالی بگذارید', 'mrn-gold-currency-pricing' ) )
					: (string) ( $schema['placeholder'] ?? '' );
				?>
				<label class="<?php echo ! empty( $schema['wide'] ) ? 'is-wide' : ''; ?>">
					<span><?php echo esc_html( (string) ( $schema['label'] ?? $field ) ); ?></span>
					<input dir="ltr" type="<?php echo esc_attr( $type ); ?>" name="providers[<?php echo esc_attr( $key ); ?>][<?php echo esc_attr( $field ); ?>]" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" <?php echo $is_secret ? 'autocomplete="new-password"' : ''; ?>>
				</label>
				<?php
			}
			return;
		}

		if ( in_array( $key, array( 'coingecko', 'metals_dev' ), true ) ) {
			?>
			<label><span><?php esc_html_e( 'کلید API', 'mrn-gold-currency-pricing' ); ?></span><input dir="ltr" type="password" name="providers[<?php echo esc_attr( $key ); ?>][api_key]" value="" placeholder="<?php echo empty( $config['api_key'] ) ? esc_attr__( 'وارد نشده', 'mrn-gold-currency-pricing' ) : esc_attr__( 'ذخیره شده — برای حفظ خالی بگذارید', 'mrn-gold-currency-pricing' ); ?>" autocomplete="new-password"></label>
			<?php
		}
		if ( 'coingecko' === $key ) {
			?>
			<label><span><?php esc_html_e( 'ارز مقصد', 'mrn-gold-currency-pricing' ); ?></span><input dir="ltr" name="providers[coingecko][vs_currency]" value="<?php echo esc_attr( $config['vs_currency'] ); ?>"></label>
			<?php
		} elseif ( 'frankfurter' === $key ) {
			?>
			<label><span><?php esc_html_e( 'ارز مبنا', 'mrn-gold-currency-pricing' ); ?></span><input dir="ltr" name="providers[frankfurter][base]" value="<?php echo esc_attr( $config['base'] ); ?>"></label>
			<?php
		} elseif ( 'metals_dev' === $key ) {
			?>
			<label><span><?php esc_html_e( 'ارز خروجی', 'mrn-gold-currency-pricing' ); ?></span><input dir="ltr" name="providers[metals_dev][currency]" value="<?php echo esc_attr( $config['currency'] ); ?>"></label><label><span><?php esc_html_e( 'واحد', 'mrn-gold-currency-pricing' ); ?></span><select name="providers[metals_dev][unit]"><option value="g" <?php selected( $config['unit'], 'g' ); ?>>Gram</option><option value="toz" <?php selected( $config['unit'], 'toz' ); ?>>Troy ounce</option><option value="kg" <?php selected( $config['unit'], 'kg' ); ?>>Kilogram</option></select></label>
			<?php
		} elseif ( 'custom_json' === $key ) {
			?>
			<label class="is-wide"><span><?php esc_html_e( 'نشانی HTTPS', 'mrn-gold-currency-pricing' ); ?></span><input dir="ltr" type="url" name="providers[custom_json][url]" value="<?php echo esc_attr( $config['url'] ); ?>" placeholder="https://api.example.com/rates"></label>
			<label class="is-wide"><span><?php esc_html_e( 'Headerها به JSON', 'mrn-gold-currency-pricing' ); ?></span><textarea dir="ltr" name="providers[custom_json][headers]" placeholder='{"Authorization":"Bearer ..."}'><?php echo esc_textarea( $config['headers'] ); ?></textarea></label>
			<label><span><?php esc_html_e( 'مسیر آیتم‌ها', 'mrn-gold-currency-pricing' ); ?></span><input dir="ltr" name="providers[custom_json][items_path]" value="<?php echo esc_attr( $config['items_path'] ); ?>" placeholder="data.rates"></label>
			<label><span><?php esc_html_e( 'فیلد کلید', 'mrn-gold-currency-pricing' ); ?></span><input dir="ltr" name="providers[custom_json][key_field]" value="<?php echo esc_attr( $config['key_field'] ); ?>"></label>
			<label><span><?php esc_html_e( 'فیلد قیمت', 'mrn-gold-currency-pricing' ); ?></span><input dir="ltr" name="providers[custom_json][value_field]" value="<?php echo esc_attr( $config['value_field'] ); ?>"></label>
			<label><span><?php esc_html_e( 'فیلد نام', 'mrn-gold-currency-pricing' ); ?></span><input dir="ltr" name="providers[custom_json][name_field]" value="<?php echo esc_attr( $config['name_field'] ); ?>"></label>
			<?php
		}
	}

	private function render_rate_cards( array $assets ): void {
		echo '<div class="mrnpe-rate-cards">';
		foreach ( $assets as $asset ) {
			$direction = (float) $asset->change_percent > 0 ? 'up' : ( (float) $asset->change_percent < 0 ? 'down' : 'flat' );
			echo '<article><div><i>' . esc_html( mb_substr( $asset->name, 0, 1 ) ) . '</i><span><b>' . esc_html( $asset->name ) . '</b><small>' . esc_html( $asset->symbol ) . '</small></span></div><strong>' . esc_html( null !== $asset->value ? number_format_i18n( (float) $asset->value, 0 ) : '—' ) . ' <small>' . esc_html( $asset->quote_currency ) . '</small></strong><span class="mrnpe-change mrnpe-change--' . esc_attr( $direction ) . '">' . esc_html( number_format_i18n( abs( (float) $asset->change_percent ), 2 ) . '%' ) . '</span></article>';
		}
		echo '</div>';
	}

	private function stat_card( string $label, int $value, string $detail, string $icon ): void {
		echo '<article class="mrnpe-stat"><span class="dashicons dashicons-' . esc_attr( $icon ) . '"></span><div><small>' . esc_html( $label ) . '</small><strong>' . esc_html( number_format_i18n( $value ) ) . '</strong><em>' . esc_html( $detail ) . '</em></div></article>';
	}

	private function render_change( float $change ): void {
		$direction = $change > 0 ? 'up' : ( $change < 0 ? 'down' : 'flat' );
		echo '<span class="mrnpe-change mrnpe-change--' . esc_attr( $direction ) . '">' . esc_html( number_format_i18n( abs( $change ), 2 ) . '%' ) . '</span>';
	}

	private function render_status( object $asset ): void {
		$stale = $asset->updated_at && time() > strtotime( $asset->updated_at . ' UTC' ) + ( (int) Settings::get( 'stale_after_minutes', 60 ) * MINUTE_IN_SECONDS );
		$key   = $stale ? 'stale' : $asset->status;
		$label = array(
			'active'   => __( 'به‌روز', 'mrn-gold-currency-pricing' ),
			'manual'   => __( 'دستی', 'mrn-gold-currency-pricing' ),
			'error'    => __( 'خطا', 'mrn-gold-currency-pricing' ),
			'inactive' => __( 'غیرفعال', 'mrn-gold-currency-pricing' ),
			'stale'    => __( 'قدیمی', 'mrn-gold-currency-pricing' ),
		)[ $key ] ?? $key;
		echo '<span class="mrnpe-status mrnpe-status--' . esc_attr( $key ) . '"><i></i>' . esc_html( $label ) . '</span>';
		if ( $asset->updated_at ) {
			echo '<small>' . esc_html( human_time_diff( strtotime( $asset->updated_at . ' UTC' ), time() ) . ' پیش' ) . '</small>';
		}
	}

	private function number_setting( string $key, string $label, array $settings, int $min ): void {
		echo '<label><span>' . esc_html( $label ) . '</span><input type="number" min="' . esc_attr( (string) $min ) . '" name="settings[' . esc_attr( $key ) . ']" value="' . esc_attr( (string) $settings[ $key ] ) . '"></label>';
	}

	private function toggle_setting( string $key, string $label, string $description, array $settings ): void {
		echo '<label><span><b>' . esc_html( $label ) . '</b><small>' . esc_html( $description ) . '</small></span><span class="mrnpe-switch"><input type="checkbox" name="settings[' . esc_attr( $key ) . ']" value="yes" ' . checked( $settings[ $key ], 'yes', false ) . '><i></i></span></label>';
	}

	private function count_dynamic_products(): int {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_mrnpe_enabled' AND meta_value = 'yes'" );
	}

	private function render_notice(): void {
		$migrated = (int) get_option( 'mrnpe_provider_migration_count', 0 );
		if ( $migrated > 0 ) {
			echo '<div class="mrnpe-notice mrnpe-notice--warning">';
			echo esc_html(
				sprintf(
					/* translators: %d: Number of migrated rate assets. */
					__( '%d نرخ پس از حذف منبع قبلی به حالت دستی تبدیل شدند. مقدارهای قبلی حفظ شده‌اند؛ نرخ‌ها را بررسی و منبع جایگزین انتخاب کنید یا نرخ دستی را به‌روز کنید.', 'mrn-gold-currency-pricing' ),
					$migrated
				)
			);
			echo '</div>';
			delete_option( 'mrnpe_provider_migration_count' );
		}
		$key      = isset( $_GET['mrnpe_notice'] ) ? sanitize_key( wp_unslash( $_GET['mrnpe_notice'] ) ) : '';
		$messages = array(
			'asset-saved'          => array( 'success', __( 'نرخ با موفقیت ذخیره شد.', 'mrn-gold-currency-pricing' ) ),
			'asset-deleted'        => array( 'success', __( 'نرخ حذف شد.', 'mrn-gold-currency-pricing' ) ),
			'asset-invalid'        => array( 'error', __( 'نام و نماد نرخ الزامی است.', 'mrn-gold-currency-pricing' ) ),
			'asset-in-use'         => array( 'error', __( 'این نرخ در یک یا چند محصول استفاده شده و قابل حذف نیست.', 'mrn-gold-currency-pricing' ) ),
			'settings-saved'       => array( 'success', __( 'تنظیمات و زمان‌بندی جدید ذخیره شد.', 'mrn-gold-currency-pricing' ) ),
			'providers-saved'      => array( 'success', __( 'پیکربندی منابع ذخیره شد.', 'mrn-gold-currency-pricing' ) ),
			'refresh-success'      => array( 'success', __( 'منابع فعال بررسی و نرخ‌های محلی تازه شدند.', 'mrn-gold-currency-pricing' ) ),
			'refresh-partial'      => array( 'warning', __( 'به‌روزرسانی انجام شد، اما یک یا چند منبع خطا داشتند؛ آخرین نرخ معتبر حفظ شده است.', 'mrn-gold-currency-pricing' ) ),
			'recalculation-queued' => array( 'success', __( 'بازسازی قیمت محصولات در صف پردازش قرار گرفت.', 'mrn-gold-currency-pricing' ) ),
		);
		if ( isset( $messages[ $key ] ) ) {
			echo '<div class="mrnpe-notice mrnpe-notice--' . esc_attr( $messages[ $key ][0] ) . '"><span class="dashicons dashicons-' . ( 'error' === $messages[ $key ][0] ? 'warning' : 'yes-alt' ) . '"></span>' . esc_html( $messages[ $key ][1] ) . '</div>';
		}
	}

	private function authorize(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'شما اجازه مدیریت نرخ‌های فروشگاه را ندارید.', 'mrn-gold-currency-pricing' ) );
		}
	}

	private function redirect( string $tab, string $notice ): never {
		$page = 'dashboard' === $tab ? self::PAGE : self::PAGE . '-' . $tab;
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => $page,
					'mrnpe_notice' => $notice,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
