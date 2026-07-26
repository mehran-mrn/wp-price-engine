<?php
/**
 * Local rate asset persistence.
 *
 * @package MRN_Price_Engine
 */

namespace MRN\Price_Engine;

defined( 'ABSPATH' ) || exit;

final class Asset_Repository {
	public function __construct( private Database $database ) {}

	/**
	 * Read all configured assets.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<int, object>
	 */
	public function all( array $args = array() ): array {
		global $wpdb;

		$table = $this->database->table( 'assets' );
		$sql   = "SELECT * FROM {$table}";
		$where = array();
		$bind  = array();

		if ( ! empty( $args['provider'] ) ) {
			$where[] = 'provider = %s';
			$bind[]  = sanitize_key( (string) $args['provider'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$bind[]  = sanitize_key( (string) $args['status'] );
		}

		if ( ! empty( $args['symbols'] ) && is_array( $args['symbols'] ) ) {
			$symbols = array_values( array_filter( array_map( array( $this, 'normalize_symbol' ), $args['symbols'] ) ) );
			if ( $symbols ) {
				$where[] = 'symbol IN (' . implode( ',', array_fill( 0, count( $symbols ), '%s' ) ) . ')';
				$bind    = array_merge( $bind, $symbols );
			}
		}

		if ( $where ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}

		$sql .= ' ORDER BY category ASC, id ASC';

		if ( $bind ) {
			$sql = $wpdb->prepare( $sql, $bind ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return (array) $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Find one asset by ID or symbol.
	 *
	 * @param int|string $identifier Asset ID or symbol.
	 * @return object|null
	 */
	public function find( int|string $identifier ): ?object {
		global $wpdb;
		$table = $this->database->table( 'assets' );

		if ( is_numeric( $identifier ) ) {
			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $identifier ) );
		}

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE symbol = %s", $this->normalize_symbol( (string) $identifier ) )
		);
	}

	/**
	 * Insert or update an asset definition.
	 *
	 * @param array<string, mixed> $data Asset fields.
	 * @param int                  $id Existing ID.
	 * @return int
	 */
	public function save( array $data, int $id = 0 ): int {
		global $wpdb;

		$existing = $id ? $this->find( $id ) : null;
		$value    = isset( $data['value'] ) && '' !== $data['value'] ? (float) $data['value'] : ( $existing->value ?? null );
		$now      = current_time( 'mysql', true );
		$fields   = array(
			'symbol'         => $this->normalize_symbol( (string) ( $data['symbol'] ?? '' ) ),
			'name'           => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'category'       => sanitize_key( (string) ( $data['category'] ?? 'currency' ) ),
			'unit'           => sanitize_text_field( (string) ( $data['unit'] ?? 'unit' ) ),
			'quote_currency' => strtoupper( sanitize_key( (string) ( $data['quote_currency'] ?? 'IRT' ) ) ),
			'provider'       => sanitize_key( (string) ( $data['provider'] ?? 'manual' ) ),
			'provider_key'   => sanitize_text_field( (string) ( $data['provider_key'] ?? '' ) ),
			'value'          => $value,
			'multiplier'     => (float) ( $data['multiplier'] ?? 1 ),
			'adjustment'     => (float) ( $data['adjustment'] ?? 0 ),
			'status'         => 'manual' === ( $data['provider'] ?? 'manual' ) && null !== $value ? 'active' : ( $existing->status ?? 'inactive' ),
			'updated_at'     => $now,
			'meta'           => ! empty( $data['meta'] ) ? wp_json_encode( $data['meta'], JSON_UNESCAPED_UNICODE ) : ( $existing->meta ?? null ),
		);

		if ( $id ) {
			$wpdb->update( $this->database->table( 'assets' ), $fields, array( 'id' => $id ) );
			return $id;
		}

		$fields['created_at'] = $now;
		$wpdb->insert( $this->database->table( 'assets' ), $fields );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Persist a retrieved provider value and history sample.
	 *
	 * @param int                  $id Asset ID.
	 * @param float                $provider_value Raw provider value.
	 * @param array<string, mixed> $meta Provider metadata.
	 * @return bool
	 */
	public function update_rate( int $id, float $provider_value, array $meta = array() ): bool {
		global $wpdb;

		$asset = $this->find( $id );
		if ( ! $asset || ! is_finite( $provider_value ) || $provider_value <= 0 ) {
			return false;
		}

		$value    = ( $provider_value * (float) $asset->multiplier ) + (float) $asset->adjustment;
		$previous = null !== $asset->value ? (float) $asset->value : $value;
		$change   = $previous > 0 ? ( ( $value - $previous ) / $previous ) * 100 : 0;
		$now      = current_time( 'mysql', true );

		$updated = $wpdb->update(
			$this->database->table( 'assets' ),
			array(
				'provider_value' => $provider_value,
				'previous_value' => $previous,
				'value'          => $value,
				'change_percent' => $change,
				'status'         => 'active',
				'fetched_at'     => $now,
				'updated_at'     => $now,
				'meta'           => $meta ? wp_json_encode( $meta, JSON_UNESCAPED_UNICODE ) : $asset->meta,
			),
			array( 'id' => $id ),
			array( '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false !== $updated && ( null === $asset->value || abs( $value - (float) $asset->value ) > 0.00000001 ) ) {
			$wpdb->insert(
				$this->database->table( 'rate_history' ),
				array(
					'asset_id'    => $id,
					'value'       => $value,
					'recorded_at' => $now,
				),
				array( '%d', '%f', '%s' )
			);
		}

		return false !== $updated;
	}

	/**
	 * Mark all assets from a failed source without destroying the last value.
	 *
	 * @param string $provider Provider key.
	 * @return void
	 */
	public function mark_provider_error( string $provider ): void {
		global $wpdb;
		$wpdb->update(
			$this->database->table( 'assets' ),
			array( 'status' => 'error' ),
			array( 'provider' => sanitize_key( $provider ) ),
			array( '%s' ),
			array( '%s' )
		);
	}

	/**
	 * Delete one asset.
	 *
	 * @param int $id Asset ID.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		global $wpdb;
		$wpdb->delete( $this->database->table( 'rate_history' ), array( 'asset_id' => $id ), array( '%d' ) );
		return false !== $wpdb->delete( $this->database->table( 'assets' ), array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Seed safe examples. External sources remain merchant-configurable.
	 *
	 * @return void
	 */
	public function seed_defaults(): void {
		if ( $this->all() ) {
			return;
		}

		$defaults = array(
			array(
				'symbol'         => 'GOLD_18K_IRT',
				'name'           => 'طلای ۱۸ عیار',
				'category'       => 'metal',
				'unit'           => 'گرم',
				'quote_currency' => 'IRT',
				'provider'       => 'navasan',
				'provider_key'   => 'geram18',
				'multiplier'     => 1,
			),
			array(
				'symbol'         => 'USD_IRT',
				'name'           => 'دلار آمریکا',
				'category'       => 'currency',
				'unit'           => 'دلار',
				'quote_currency' => 'IRT',
				'provider'       => 'navasan',
				'provider_key'   => 'usd_sell',
				'multiplier'     => 1,
			),
			array(
				'symbol'         => 'EMAMI_IRT',
				'name'           => 'سکه امامی',
				'category'       => 'coin',
				'unit'           => 'عدد',
				'quote_currency' => 'IRT',
				'provider'       => 'navasan',
				'provider_key'   => 'sekke_emami',
				'multiplier'     => 1,
			),
			array(
				'symbol'         => 'BTC_USD',
				'name'           => 'بیت‌کوین',
				'category'       => 'crypto',
				'unit'           => 'BTC',
				'quote_currency' => 'USD',
				'provider'       => 'coingecko',
				'provider_key'   => 'bitcoin',
				'multiplier'     => 1,
			),
			array(
				'symbol'         => 'ETH_USD',
				'name'           => 'اتریوم',
				'category'       => 'crypto',
				'unit'           => 'ETH',
				'quote_currency' => 'USD',
				'provider'       => 'coingecko',
				'provider_key'   => 'ethereum',
				'multiplier'     => 1,
			),
			array(
				'symbol'         => 'EUR_USD',
				'name'           => 'یورو به دلار',
				'category'       => 'currency',
				'unit'           => 'EUR',
				'quote_currency' => 'USD',
				'provider'       => 'frankfurter',
					'provider_key'   => 'USD',
				'multiplier'     => 1,
			),
		);

		foreach ( $defaults as $asset ) {
			$this->save( $asset );
		}
	}

	/**
	 * Add upgrade-safe bridge assets without changing merchant-created rates.
	 *
	 * @return void
	 */
	public function seed_upgrade_assets(): void {
		foreach ( $this->all() as $asset ) {
			if ( 'USDT_IRT' === (string) $asset->symbol ) {
				return;
			}
		}

		$this->save(
			array(
				'symbol'         => 'USDT_IRT',
				'name'           => 'تتر بازار (پل تبدیل دلار)',
				'category'       => 'currency',
				'unit'           => 'USDT',
				'quote_currency' => 'IRT',
				'provider'       => 'wallexir',
				'provider_key'   => 'USDTTMN',
				'multiplier'     => 1,
			)
		);
	}

	/**
	 * Normalize merchant-facing symbols.
	 *
	 * @param string $symbol Raw symbol.
	 * @return string
	 */
	private function normalize_symbol( string $symbol ): string {
		$symbol = strtoupper( trim( $symbol ) );
		return preg_replace( '/[^A-Z0-9_-]/', '', $symbol ) ?: '';
	}
}
