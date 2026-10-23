<?php
/**
 * Database schema and operational logging.
 *
 * @package MRN_Price_Engine
 */

namespace MRNPE\Price_Engine;

defined( 'ABSPATH' ) || exit;

final class Database {
	public const VERSION = '1.0.0';

	/**
	 * Return a plugin table name.
	 *
	 * @param string $suffix Table suffix.
	 * @return string
	 */
	public function table( string $suffix ): string {
		global $wpdb;
		return $wpdb->prefix . 'mrnpe_' . $suffix;
	}

	/**
	 * Create or upgrade plugin tables.
	 *
	 * @return void
	 */
	public function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$assets  = $this->table( 'assets' );
		$history = $this->table( 'rate_history' );
		$logs    = $this->table( 'logs' );

		dbDelta(
			"CREATE TABLE {$assets} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				symbol varchar(64) NOT NULL,
				name varchar(190) NOT NULL,
				category varchar(32) NOT NULL DEFAULT 'currency',
				unit varchar(64) NOT NULL DEFAULT 'unit',
				quote_currency varchar(12) NOT NULL DEFAULT 'IRT',
				provider varchar(64) NOT NULL DEFAULT 'manual',
				provider_key varchar(190) NOT NULL DEFAULT '',
				provider_value decimal(30,10) NULL,
				value decimal(30,10) NULL,
				previous_value decimal(30,10) NULL,
				change_percent decimal(16,6) NOT NULL DEFAULT 0,
				multiplier decimal(20,10) NOT NULL DEFAULT 1,
				adjustment decimal(30,10) NOT NULL DEFAULT 0,
				status varchar(24) NOT NULL DEFAULT 'inactive',
				fetched_at datetime NULL,
				updated_at datetime NULL,
				created_at datetime NOT NULL,
				meta longtext NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY symbol (symbol),
				KEY provider (provider),
				KEY status (status),
				KEY updated_at (updated_at)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$history} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				asset_id bigint(20) unsigned NOT NULL,
				value decimal(30,10) NOT NULL,
				recorded_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY asset_time (asset_id, recorded_at),
				KEY recorded_at (recorded_at)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$logs} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				level varchar(16) NOT NULL DEFAULT 'info',
				source varchar(64) NOT NULL DEFAULT 'system',
				message text NOT NULL,
				context longtext NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY source_time (source, created_at),
				KEY created_at (created_at)
			) {$charset};"
		);
	}

	/**
	 * Store an operational event.
	 *
	 * @param string               $level Log level.
	 * @param string               $source Event source.
	 * @param string               $message Human-readable message.
	 * @param array<string, mixed> $context Safe diagnostic context.
	 * @return void
	 */
	public function log( string $level, string $source, string $message, array $context = array() ): void {
		global $wpdb;

		$wpdb->insert(
			$this->table( 'logs' ),
			array(
				'level'      => sanitize_key( $level ),
				'source'     => sanitize_key( $source ),
				'message'    => sanitize_textarea_field( $message ),
				'context'    => $context ? wp_json_encode( $context, JSON_UNESCAPED_UNICODE ) : null,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Read recent logs.
	 *
	 * @param int $limit Maximum records.
	 * @return array<int, object>
	 */
	public function logs( int $limit = 30 ): array {
		global $wpdb;
		$table = $this->table( 'logs' );
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC LIMIT %d', $table, max( 1, min( 200, $limit ) ) ) );
	}

	/**
	 * Clear records beyond configured retention.
	 *
	 * @return void
	 */
	public function prune(): void {
		global $wpdb;

		$days   = max( 1, (int) Settings::get( 'history_retention_days', 90 ) );
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( DAY_IN_SECONDS * $days ) );

		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE recorded_at < %s', $this->table( 'rate_history' ), $cutoff ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE created_at < %s', $this->table( 'logs' ), $cutoff ) );
	}
}
