<?php
/**
 * Provider orchestration, quota pacing and update locking.
 *
 * @package MRN_Price_Engine
 */

namespace MRNPE\Price_Engine;

use MRNPE\Price_Engine\Providers\Coingecko_Provider;
use MRNPE\Price_Engine\Providers\Custom_JSON_Provider;
use MRNPE\Price_Engine\Providers\Extended_Provider;
use MRNPE\Price_Engine\Providers\Frankfurter_Provider;
use MRNPE\Price_Engine\Providers\HTML_Scraper_Provider;
use MRNPE\Price_Engine\Providers\Metals_Dev_Provider;

defined( 'ABSPATH' ) || exit;

final class Provider_Manager {
	/** @var array<string, Provider_Interface> */
	private array $providers = array();

	public function __construct(
		private Asset_Repository $assets,
		private Database $database
	) {
		$providers = array_merge(
			array(
				new Coingecko_Provider(),
				new Frankfurter_Provider(),
				new Metals_Dev_Provider(),
				new Custom_JSON_Provider(),
			),
			Extended_Provider::instances(),
			HTML_Scraper_Provider::instances()
		);

		foreach ( $providers as $provider ) {
			$this->providers[ $provider->key() ] = $provider;
		}

		$this->providers = apply_filters( 'mrnpe_rate_providers', $this->providers );
	}

	/**
	 * Refresh due providers with one lock shared by cron and manual actions.
	 *
	 * @param bool        $force Ignore per-provider refresh intervals.
	 * @param string|null $only Refresh one provider.
	 * @return array<string, mixed> Refresh report.
	 */
	public function refresh( bool $force = false, ?string $only = null ): array {
		if ( get_transient( 'mrnpe_refresh_lock' ) ) {
			return array(
				'ok'      => false,
				'message' => __( 'یک به‌روزرسانی دیگر در حال اجراست.', 'mrn-gold-currency-pricing' ),
				'locked'  => true,
			);
		}

		set_transient( 'mrnpe_refresh_lock', wp_generate_uuid4(), 2 * MINUTE_IN_SECONDS );

		$started = microtime( true );
		$state   = (array) get_option( 'mrnpe_provider_state', array() );
		$report  = array(
			'ok'        => true,
			'providers' => array(),
			'updated'   => 0,
			'requests'  => 0,
		);
		$changed = array();

		try {
			foreach ( $this->providers as $key => $provider ) {
				if ( $only && $key !== $only ) {
					continue;
				}

				$config = Settings::provider( $key );
				if ( 'yes' !== ( $config['enabled'] ?? 'no' ) ) {
					continue;
				}

				$provider_assets = $this->assets->all( array( 'provider' => $key ) );
				if ( ! $provider_assets ) {
					continue;
				}

				$interval_minutes = max( 1, (int) ( $config['interval'] ?? Settings::get( 'refresh_interval', 15 ) ) );
				$last_attempt     = (int) ( $state[ $key ]['last_attempt'] ?? 0 );

				if ( ! $force && $last_attempt && time() < $last_attempt + ( $interval_minutes * MINUTE_IN_SECONDS ) ) {
					$report['providers'][ $key ] = array(
						'status'  => 'skipped',
						'updated' => 0,
					);
					continue;
				}

				$state[ $key ]['last_attempt'] = time();
				$result                        = $provider->fetch( $provider_assets );
				$report['requests']           += method_exists( $provider, 'request_count' )
					? max( 0, (int) $provider->request_count() )
					: 1;

				if ( is_wp_error( $result ) ) {
					$this->assets->mark_provider_error( $key );
					$state[ $key ]['last_error'] = $result->get_error_message();
					$report['providers'][ $key ] = array(
						'status'  => 'error',
						'updated' => 0,
						'message' => $result->get_error_message(),
					);
					$report['ok']                = false;
					$this->database->log( 'error', $key, $result->get_error_message() );
					continue;
				}

				$updated = 0;
				foreach ( $provider_assets as $asset ) {
					$provider_key = (string) $asset->provider_key;
					$rate         = $result[ $provider_key ] ?? $result[ strtoupper( $provider_key ) ] ?? null;

					if ( ! is_array( $rate ) || ! isset( $rate['value'] ) || ! is_numeric( $rate['value'] ) ) {
						continue;
					}

					if ( $this->assets->update_rate( (int) $asset->id, (float) $rate['value'], $rate ) ) {
						++$updated;
						$changed[] = (int) $asset->id;
					}
				}

				$state[ $key ]['last_success'] = time();
				$state[ $key ]['last_error']   = '';
				$state[ $key ]['last_count']   = $updated;
				$report['providers'][ $key ]   = array(
					'status'  => 'success',
					'updated' => $updated,
				);
				$report['updated']            += $updated;
				$this->database->log( 'info', $key, sprintf( 'Updated %d assets in one request.', $updated ), array( 'assets' => count( $provider_assets ) ) );
			}

			$state['last_run'] = time();
			$today             = gmdate( 'Y-m-d' );
			if ( $today !== ( $state['request_count_date'] ?? '' ) ) {
				$state['request_count_date']  = $today;
				$state['request_count_today'] = 0;
			}
			$state['request_count_today'] = (int) ( $state['request_count_today'] ?? 0 ) + (int) $report['requests'];
			update_option( 'mrnpe_provider_state', $state, false );

			if ( $changed ) {
				do_action( 'mrnpe_rates_updated', array_values( array_unique( $changed ) ) );
			}

			$report['duration_ms'] = (int) round( ( microtime( true ) - $started ) * 1000 );
			do_action( 'mrnpe_after_refresh', $report );
		} finally {
			delete_transient( 'mrnpe_refresh_lock' );
		}

		return $report;
	}

	/**
	 * Provider metadata for UI and extensions.
	 *
	 * @return array<string, Provider_Interface>
	 */
	public function all(): array {
		return $this->providers;
	}
}
