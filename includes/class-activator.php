<?php
/**
 * Activation and deactivation tasks.
 *
 * @package MRN_Price_Engine
 */

namespace MRN\Price_Engine;

defined( 'ABSPATH' ) || exit;

final class Activator {
	/**
	 * Prepare database, defaults and scheduled refresh.
	 *
	 * @return void
	 */
	public static function activate(): void {
		if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
			deactivate_plugins( plugin_basename( MRNPE_FILE ) );
			wp_die( esc_html__( 'MRN Price Engine به PHP 8.1 یا جدیدتر نیاز دارد.', 'mrn-price-engine' ) );
		}

		$database = new Database();
		$database->install();

		if ( false === get_option( 'mrnpe_settings', false ) ) {
			add_option( 'mrnpe_settings', Settings::defaults(), '', false );
		}

		if ( false === get_option( 'mrnpe_providers', false ) ) {
			add_option( 'mrnpe_providers', Settings::provider_defaults(), '', false );
		}

		$assets = new Asset_Repository( $database );
		$assets->seed_defaults();
		$assets->seed_upgrade_assets();

		wp_clear_scheduled_hook( 'mrnpe_refresh_rates' );
		wp_schedule_event( time() + MINUTE_IN_SECONDS, 'mrnpe_interval', 'mrnpe_refresh_rates' );

		update_option( 'mrnpe_db_version', Database::VERSION, false );
		update_option( 'mrnpe_plugin_version', MRNPE_VERSION, false );
	}

	/**
	 * Apply additive upgrades for already-active installations.
	 *
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		$current = (string) get_option( 'mrnpe_plugin_version', '0.0.0' );
		if ( version_compare( $current, MRNPE_VERSION, '>=' ) ) {
			return;
		}

		$database = new Database();
		$database->install();

		$providers = (array) get_option( 'mrnpe_providers', array() );
		foreach ( Settings::provider_defaults() as $key => $defaults ) {
			$providers[ $key ] = wp_parse_args( isset( $providers[ $key ] ) ? (array) $providers[ $key ] : array(), $defaults );
		}
		update_option( 'mrnpe_providers', $providers, false );

		$assets = new Asset_Repository( $database );
		$assets->seed_upgrade_assets();

		update_option( 'mrnpe_db_version', Database::VERSION, false );
		update_option( 'mrnpe_plugin_version', MRNPE_VERSION, false );
	}

	/**
	 * Clear plugin schedules without deleting merchant data.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'mrnpe_refresh_rates' );
		wp_clear_scheduled_hook( 'mrnpe_recalculate_products' );
		delete_transient( 'mrnpe_refresh_lock' );
		delete_transient( 'mrnpe_recalculation_lock' );
	}
}
