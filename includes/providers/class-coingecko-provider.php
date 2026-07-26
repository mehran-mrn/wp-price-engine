<?php
/**
 * CoinGecko crypto provider.
 *
 * @package MRN_Price_Engine
 */

namespace MRN\Price_Engine\Providers;

use MRN\Price_Engine\Abstract_Provider;
use MRN\Price_Engine\Settings;

defined( 'ABSPATH' ) || exit;

final class Coingecko_Provider extends Abstract_Provider {
	public function key(): string {
		return 'coingecko';
	}

	public function label(): string {
		return 'CoinGecko';
	}

	public function fetch( array $assets ): array|\WP_Error {
		$config   = Settings::provider( $this->key() );
		$currency = strtolower( sanitize_key( (string) ( $config['vs_currency'] ?? 'usd' ) ) );
		$keys     = array_slice( $this->asset_keys( $assets ), 0, 250 );

		if ( ! $keys ) {
			return array();
		}

		$url = add_query_arg(
			array(
				'ids'                     => implode( ',', $keys ),
				'vs_currencies'           => $currency,
				'include_24hr_change'     => 'true',
				'include_last_updated_at' => 'true',
				'precision'               => 'full',
			),
			'https://api.coingecko.com/api/v3/simple/price'
		);

		$headers = array();
		if ( ! empty( $config['api_key'] ) ) {
			$headers['x-cg-demo-api-key'] = trim( (string) $config['api_key'] );
		}

		$response = $this->get_json( $url, $headers );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$rates = array();
		foreach ( $keys as $provider_key ) {
			if ( ! isset( $response[ $provider_key ][ $currency ] ) || ! is_numeric( $response[ $provider_key ][ $currency ] ) ) {
				continue;
			}

			$rates[ $provider_key ] = array(
				'value'     => (float) $response[ $provider_key ][ $currency ],
				'change'    => isset( $response[ $provider_key ][ $currency . '_24h_change' ] ) ? (float) $response[ $provider_key ][ $currency . '_24h_change' ] : null,
				'timestamp' => isset( $response[ $provider_key ]['last_updated_at'] ) ? (int) $response[ $provider_key ]['last_updated_at'] : null,
			);
		}

		return $rates;
	}
}
