<?php
/**
 * Metals.Dev precious metals provider.
 *
 * @package MRN_Price_Engine
 */

namespace MRNPE\Price_Engine\Providers;

use MRNPE\Price_Engine\Abstract_Provider;
use MRNPE\Price_Engine\Settings;

defined( 'ABSPATH' ) || exit;

final class Metals_Dev_Provider extends Abstract_Provider {
	public function key(): string {
		return 'metals_dev';
	}

	public function label(): string {
		return 'Metals.Dev';
	}

	public function fetch( array $assets ): array|\WP_Error {
		$config = Settings::provider( $this->key() );
		$key    = trim( (string) ( $config['api_key'] ?? '' ) );

		if ( '' === $key ) {
			return new \WP_Error( 'mrnpe_missing_key', __( 'کلید API Metals.Dev وارد نشده است.', 'mrn-gold-currency-pricing' ) );
		}

		$response = $this->get_json(
			add_query_arg(
				array(
					'api_key'  => $key,
					'currency' => strtoupper( sanitize_key( (string) ( $config['currency'] ?? 'USD' ) ) ),
					'unit'     => sanitize_key( (string) ( $config['unit'] ?? 'g' ) ),
				),
				'https://api.metals.dev/v1/latest'
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( isset( $response['status'] ) && 'success' !== $response['status'] ) {
			return new \WP_Error( 'mrnpe_provider_error', sanitize_text_field( (string) ( $response['error_message'] ?? __( 'خطای نامشخص Metals.Dev', 'mrn-gold-currency-pricing' ) ) ) );
		}

		$values = isset( $response['metals'] ) && is_array( $response['metals'] ) ? $response['metals'] : array();
		$rates  = array();

		foreach ( $this->asset_keys( $assets ) as $provider_key ) {
			if ( isset( $values[ $provider_key ] ) && is_numeric( $values[ $provider_key ] ) ) {
				$rates[ $provider_key ] = array(
					'value'     => (float) $values[ $provider_key ],
					'timestamp' => $response['timestamp'] ?? null,
				);
			}
		}

		return $rates;
	}
}
