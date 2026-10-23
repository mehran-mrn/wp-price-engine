<?php
/**
 * Scheduled fetching, cleanup and product recalculation.
 *
 * @package MRN_Price_Engine
 */

namespace MRNPE\Price_Engine;

defined( 'ABSPATH' ) || exit;

final class Scheduler {
	public function __construct(
		private Provider_Manager $providers,
		private Pricing_Engine $pricing
	) {}

	/**
	 * Register cron hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'cron_schedules', array( $this, 'schedules' ) );
		add_action( 'init', array( $this, 'ensure_scheduled' ) );
		add_action( 'mrnpe_refresh_rates', array( $this, 'refresh' ) );
		add_action( 'mrnpe_rates_updated', array( $this, 'queue_recalculation' ) );
		add_action( 'mrnpe_recalculate_products', array( $this, 'recalculate' ), 10, 1 );
	}

	/**
	 * Add the merchant refresh cadence.
	 *
	 * @param array<string, array<string, mixed>> $schedules Schedules.
	 * @return array<string, array<string, mixed>>
	 */
	public function schedules( array $schedules ): array {
		$minutes                     = max( 1, (int) Settings::get( 'refresh_interval', 15 ) );
		$schedules['mrnpe_interval'] = array(
			'interval' => $minutes * MINUTE_IN_SECONDS,
			'display'  => sprintf(
				/* translators: %d: minutes. */
				_n( 'هر %d دقیقه', 'هر %d دقیقه', $minutes, 'mrn-gold-currency-pricing' ),
				$minutes
			),
		);
		return $schedules;
	}

	/**
	 * Ensure cron exists after migrations or cron loss.
	 *
	 * @return void
	 */
	public function ensure_scheduled(): void {
		if ( ! wp_next_scheduled( 'mrnpe_refresh_rates' ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'mrnpe_interval', 'mrnpe_refresh_rates' );
		}
	}

	/**
	 * Run a due-provider refresh.
	 *
	 * @return void
	 */
	public function refresh(): void {
		$this->providers->refresh();
		( new Database() )->prune();
	}

	/**
	 * Queue one product recalculation chain.
	 *
	 * @return void
	 */
	public function queue_recalculation(): void {
		if ( wp_next_scheduled( 'mrnpe_recalculate_products' ) ) {
			return;
		}

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( 'mrnpe_recalculate_products', array( 0 ), 'mrnpe-pricing', true );
			return;
		}

		wp_schedule_single_event( time() + 2, 'mrnpe_recalculate_products', array( 0 ) );
	}

	/**
	 * Recalculate products in bounded batches.
	 *
	 * @param int $offset Query offset.
	 * @return void
	 */
	public function recalculate( int $offset = 0 ): void {
		if ( get_transient( 'mrnpe_recalculation_lock' ) ) {
			return;
		}

		set_transient( 'mrnpe_recalculation_lock', 1, 2 * MINUTE_IN_SECONDS );
		$result = $this->pricing->recalculate_batch( max( 0, $offset ), 50 );
		delete_transient( 'mrnpe_recalculation_lock' );

		if ( $result['remaining'] > 0 ) {
			$next_offset = $offset + $result['processed'];

			if ( function_exists( 'as_enqueue_async_action' ) ) {
				as_enqueue_async_action( 'mrnpe_recalculate_products', array( $next_offset ), 'mrnpe-pricing', true );
			} else {
				wp_schedule_single_event( time() + 5, 'mrnpe_recalculate_products', array( $next_offset ) );
			}
		}
	}
}
