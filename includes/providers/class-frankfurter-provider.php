<?php
/**
 * Frankfurter central-bank FX provider.
 *
 * @package MRN_Price_Engine
 */

namespace MRNPE\Price_Engine\Providers;

use MRNPE\Price_Engine\Abstract_Provider;
use MRNPE\Price_Engine\Settings;

defined( 'ABSPATH' ) || exit;

final class Frankfurter_Provider extends Abstract_Provider {
	public function key(): string {
		return 'frankfurter';
	}

	public function label(): string {
		return 'Frankfurter';
	}

	public function fetch( array $assets ): array|\WP_Error {
		$config  = Settings::provider( $this->key() );
		$base    = strtoupper( sanitize_key( (string) ( $config['base'] ?? 'EUR' ) ) );
		$symbols = array_map( 'strtoupper', $this->asset_keys( $assets ) );

		if ( ! $symbols ) {
			return array();
		}

		$response = $this->get_json(
			add_query_arg(
				array(
					'base'    => $base,
					'symbols' => implode( ',', $symbols ),
				),
				'https://api.frankfurter.dev/v1/latest'
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$rates = array();
		foreach ( $symbols as $symbol ) {
			if ( isset( $response['rates'][ $symbol ] ) && is_numeric( $response['rates'][ $symbol ] ) ) {
				$rates[ $symbol ] = array(
					'value'     => (float) $response['rates'][ $symbol ],
					'timestamp' => sanitize_text_field( (string) ( $response['date'] ?? '' ) ),
				);
			}
		}

		return $rates;
	}
}
