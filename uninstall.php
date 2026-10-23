<?php
/**
 * Optional complete data removal.
 *
 * @package MRN_Price_Engine
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$mrnpe_settings = (array) get_option( 'mrnpe_settings', array() );
if ( 'yes' !== ( $mrnpe_settings['delete_on_uninstall'] ?? 'no' ) ) {
	return;
}

global $wpdb;

foreach ( array( 'assets', 'rate_history', 'logs' ) as $mrnpe_suffix ) {
	$mrnpe_table = $wpdb->prefix . 'mrnpe_' . $mrnpe_suffix;
	$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %i", $mrnpe_table ) );
}

$mrnpe_meta_keys = array(
	'_mrnpe_enabled',
	'_mrnpe_asset_id',
	'_mrnpe_quantity',
	'_mrnpe_multiplier',
	'_mrnpe_percentage',
	'_mrnpe_fixed',
	'_mrnpe_rounding_step',
	'_mrnpe_rounding_mode',
	'_mrnpe_sale_enabled',
	'_mrnpe_sale_percentage',
	'_mrnpe_sale_fixed',
	'_mrnpe_last_calculated',
	'_mrnpe_rate_snapshot',
);

foreach ( $mrnpe_meta_keys as $mrnpe_meta_key ) {
	delete_post_meta_by_key( $mrnpe_meta_key );
}

delete_option( 'mrnpe_settings' );
delete_option( 'mrnpe_providers' );
delete_option( 'mrnpe_provider_state' );
delete_option( 'mrnpe_provider_migration_count' );
delete_option( 'mrnpe_db_version' );
delete_option( 'mrnpe_plugin_version' );
wp_clear_scheduled_hook( 'mrnpe_refresh_rates' );
wp_clear_scheduled_hook( 'mrnpe_recalculate_products' );
