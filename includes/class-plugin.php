<?php
/**
 * Main plugin composition root.
 *
 * @package MRN_Price_Engine
 */

namespace MRNPE\Price_Engine;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	private static ?Plugin $instance = null;

	private Database $database;

	private Asset_Repository $assets;

	private Provider_Manager $providers;

	private Pricing_Engine $pricing;

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}

		return self::$instance;
	}

	/**
	 * Wire plugin services and hooks.
	 *
	 * @return void
	 */
	private function boot(): void {
		Activator::maybe_upgrade();

		$this->database  = new Database();
		$this->assets    = new Asset_Repository( $this->database );
		$this->pricing   = new Pricing_Engine( $this->assets );
		$this->providers = new Provider_Manager( $this->assets, $this->database );

		( new Scheduler( $this->providers, $this->pricing ) )->register();
		( new Shortcodes( $this->assets ) )->register();
		( new Blocks() )->register();
		( new REST_API( $this->assets, $this->providers ) )->register();

		if ( class_exists( 'WooCommerce' ) ) {
			( new WooCommerce_Integration( $this->assets, $this->pricing ) )->register();
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_notice' ) );
		}

		if ( is_admin() ) {
			( new Admin( $this->assets, $this->providers, $this->database, $this->pricing ) )->register();
		}

	}


	/**
	 * Tell administrators that WooCommerce is required.
	 *
	 * @return void
	 */
	public function woocommerce_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'برای قیمت‌گذاری محصولات، افزونه MRN Gold & Currency Pricing به ووکامرس نیاز دارد. بخش نرخ‌ها بدون ووکامرس همچنان قابل استفاده است.', 'mrn-gold-currency-pricing' );
		echo '</p></div>';
	}

	public function assets(): Asset_Repository {
		return $this->assets;
	}

	public function pricing(): Pricing_Engine {
		return $this->pricing;
	}

	public function providers(): Provider_Manager {
		return $this->providers;
	}
}
