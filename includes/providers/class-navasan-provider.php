<?php
/**
 * Navasan Iranian market provider.
 *
 * @package MRN_Price_Engine
 */

namespace MRN\Price_Engine\Providers;

use MRN\Price_Engine\Abstract_Provider;
use MRN\Price_Engine\Settings;

defined( 'ABSPATH' ) || exit;

final class Navasan_Provider extends Abstract_Provider {
	public function key(): string {
		return 'navasan';
	}

	public function label(): string {
		return 'Navasan';
	}

	public function fetch( array $assets ): array|\WP_Error {
		$config = Settings::provider( $this->key() );
		$key    = trim( (string) ( $config['api_key'] ?? '' ) );

		if ( '' === $key ) {
			return new \WP_Error( 'mrnpe_missing_key', __( 'کلید API نوسان وارد نشده است.', 'mrn-price-engine' ) );
		}

		$response = $this->get_json( add_query_arg( 'api_key', $key, 'https://api.navasan.tech/latest/' ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$rates = array();
		foreach ( $this->asset_keys( $assets ) as $provider_key ) {
			if ( isset( $response[ $provider_key ]['value'] ) && is_numeric( $response[ $provider_key ]['value'] ) ) {
				$rates[ $provider_key ] = array(
					'value'     => (float) $response[ $provider_key ]['value'],
					'timestamp' => isset( $response[ $provider_key ]['date'] ) ? sanitize_text_field( (string) $response[ $provider_key ]['date'] ) : null,
				);
			}
		}

		return $rates;
	}
}
