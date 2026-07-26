<?php
/**
 * Configurable JSON provider for private or regional APIs.
 *
 * @package MRN_Price_Engine
 */

namespace MRN\Price_Engine\Providers;

use MRN\Price_Engine\Abstract_Provider;
use MRN\Price_Engine\Settings;

defined( 'ABSPATH' ) || exit;

final class Custom_JSON_Provider extends Abstract_Provider {
	public function key(): string {
		return 'custom_json';
	}

	public function label(): string {
		return __( 'JSON سفارشی', 'mrn-price-engine' );
	}

	public function fetch( array $assets ): array|\WP_Error {
		$config = Settings::provider( $this->key() );
		$url    = esc_url_raw( (string) ( $config['url'] ?? '' ), array( 'https' ) );

		if ( ! $url ) {
			return new \WP_Error( 'mrnpe_missing_url', __( 'نشانی HTTPS منبع JSON وارد نشده است.', 'mrn-price-engine' ) );
		}

		$headers = array();
		if ( ! empty( $config['headers'] ) ) {
			$decoded = json_decode( (string) $config['headers'], true );
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $name => $value ) {
					$headers[ sanitize_key( (string) $name ) ] = sanitize_text_field( (string) $value );
				}
			}
		}

		$response = $this->get_json( $url, $headers );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$items = $this->read_path( $response, (string) ( $config['items_path'] ?? '' ) );
		if ( ! is_array( $items ) ) {
			return new \WP_Error( 'mrnpe_json_path', __( 'مسیر آیتم‌ها در پاسخ JSON پیدا نشد.', 'mrn-price-engine' ) );
		}

		$key_field   = sanitize_key( (string) ( $config['key_field'] ?? 'symbol' ) );
		$value_field = sanitize_key( (string) ( $config['value_field'] ?? 'price' ) );
		$name_field  = sanitize_key( (string) ( $config['name_field'] ?? 'name' ) );
		$rates       = array();

		foreach ( $items as $index => $item ) {
			if ( is_numeric( $item ) ) {
				$rates[ (string) $index ] = array( 'value' => (float) $item );
				continue;
			}

			if ( ! is_array( $item ) ) {
				continue;
			}

			$key = isset( $item[ $key_field ] ) ? (string) $item[ $key_field ] : (string) $index;
			if ( isset( $item[ $value_field ] ) && is_numeric( $item[ $value_field ] ) ) {
				$rates[ $key ] = array(
					'value' => (float) $item[ $value_field ],
					'name'  => isset( $item[ $name_field ] ) ? sanitize_text_field( (string) $item[ $name_field ] ) : '',
				);
			}
		}

		return $rates;
	}

	/**
	 * Traverse a dot-delimited object path.
	 *
	 * @param array<mixed> $data Decoded JSON.
	 * @param string       $path Dot path.
	 * @return mixed
	 */
	private function read_path( array $data, string $path ): mixed {
		if ( '' === trim( $path ) ) {
			return $data;
		}

		$current = $data;
		foreach ( explode( '.', $path ) as $segment ) {
			if ( ! is_array( $current ) || ! array_key_exists( $segment, $current ) ) {
				return null;
			}
			$current = $current[ $segment ];
		}

		return $current;
	}
}
