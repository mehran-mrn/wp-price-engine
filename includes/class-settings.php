<?php
/**
 * Typed access to plugin options.
 *
 * @package MRN_Price_Engine
 */

namespace MRNPE\Price_Engine;

use MRNPE\Price_Engine\Providers\Extended_Provider;
use MRNPE\Price_Engine\Providers\HTML_Scraper_Provider;

defined( 'ABSPATH' ) || exit;

final class Settings {
	/**
	 * General defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'refresh_interval'       => 15,
			'request_timeout'        => 12,
			'history_retention_days' => 90,
			'stale_after_minutes'    => 60,
			'stale_policy'           => 'last_known',
			'cart_price_lock'        => 'yes',
			'show_product_breakdown' => 'yes',
			'public_rest_api'        => 'yes',
			'frontend_refresh'       => 60,
			'default_rounding_step'  => 1000,
			'default_rounding_mode'  => 'nearest',
			'accent_color'           => '#b8872e',
			'delete_on_uninstall'    => 'no',
		);
	}

	/**
	 * Provider defaults.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function provider_defaults(): array {
		$native = array(
			'coingecko'   => array(
				'enabled'     => 'no',
				'interval'    => 10,
				'api_key'     => '',
				'vs_currency' => 'usd',
			),
			'frankfurter' => array(
				'enabled'  => 'no',
				'interval' => 360,
				'base'     => 'EUR',
			),
			'metals_dev'  => array(
				'enabled'  => 'no',
				'interval' => 15,
				'api_key'  => '',
				'currency' => 'USD',
				'unit'     => 'g',
			),
			'custom_json' => array(
				'enabled'     => 'no',
				'interval'    => 15,
				'url'         => '',
				'headers'     => '',
				'items_path'  => '',
				'key_field'   => 'symbol',
				'value_field' => 'price',
				'name_field'  => 'name',
			),
			);

		$providers = array_merge( $native, Extended_Provider::default_settings(), HTML_Scraper_Provider::default_settings() );
		foreach ( $providers as &$provider ) {
			$provider['enabled'] = 'no';
		}
		unset( $provider );
		return $providers;
	}

	/**
	 * Get merged general settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		return wp_parse_args( (array) get_option( 'mrnpe_settings', array() ), self::defaults() );
	}

	/**
	 * Get one general setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $fallback Optional fallback.
	 * @return mixed
	 */
	public static function get( string $key, mixed $fallback = null ): mixed {
		$settings = self::all();
		return $settings[ $key ] ?? $fallback;
	}

	/**
	 * Get merged provider settings.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function providers(): array {
		$saved    = (array) get_option( 'mrnpe_providers', array() );
		$defaults = self::provider_defaults();

		foreach ( $defaults as $key => $values ) {
			$saved[ $key ] = wp_parse_args( isset( $saved[ $key ] ) ? (array) $saved[ $key ] : array(), $values );
		}

		return $saved;
	}

	/**
	 * Get one provider configuration.
	 *
	 * @param string $key Provider key.
	 * @return array<string, mixed>
	 */
	public static function provider( string $key ): array {
		$providers = self::providers();
		return $providers[ $key ] ?? array();
	}
}
