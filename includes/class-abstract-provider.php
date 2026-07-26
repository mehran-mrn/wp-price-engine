<?php
/**
 * Shared provider HTTP behavior.
 *
 * @package MRN_Price_Engine
 */

namespace MRN\Price_Engine;

defined( 'ABSPATH' ) || exit;

abstract class Abstract_Provider implements Provider_Interface {
	/**
	 * Perform a safe JSON GET request.
	 *
	 * @param string               $url Request URL.
	 * @param array<string, mixed> $headers Optional headers.
	 * @return array<mixed>|\WP_Error
	 */
	protected function get_json( string $url, array $headers = array() ): array|\WP_Error {
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'     => max( 3, min( 30, (int) Settings::get( 'request_timeout', 12 ) ) ),
				'redirection' => 3,
				'headers'     => array_merge(
					array(
						'Accept'     => 'application/json',
						'User-Agent' => 'MRN-Price-Engine/' . MRNPE_VERSION . '; ' . home_url( '/' ),
					),
					$headers
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new \WP_Error(
				'mrnpe_http_error',
				sprintf(
					/* translators: %d: HTTP response status. */
					__( 'منبع داده با وضعیت HTTP %d پاسخ داد.', 'mrn-price-engine' ),
					$code
				),
				array( 'status' => $code )
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $body ) ) {
			return new \WP_Error( 'mrnpe_invalid_json', __( 'پاسخ منبع داده JSON معتبر نیست.', 'mrn-price-engine' ) );
		}

		return $body;
	}

	/**
	 * Unique, non-empty provider keys assigned to assets.
	 *
	 * @param array<int, object> $assets Assets.
	 * @return array<int, string>
	 */
	protected function asset_keys( array $assets ): array {
		return array_values(
			array_unique(
				array_filter(
					array_map(
						static fn( object $asset ): string => trim( (string) $asset->provider_key ),
						$assets
					)
				)
			)
		);
	}
}
