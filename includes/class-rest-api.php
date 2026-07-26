<?php
/**
 * Local REST API for rate boards and admin actions.
 *
 * @package MRN_Price_Engine
 */

namespace MRN\Price_Engine;

defined( 'ABSPATH' ) || exit;

final class REST_API {
	public function __construct(
		private Asset_Repository $assets,
		private Provider_Manager $providers
	) {}

	/**
	 * Register API routes.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Define public read and protected refresh endpoints.
	 *
	 * @return void
	 */
	public function routes(): void {
		register_rest_route(
			'mrn-price-engine/v1',
			'/rates',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rates' ),
				'permission_callback' => array( $this, 'can_read_rates' ),
				'args'                => array(
					'symbols' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'mrn-price-engine/v1',
			'/refresh',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'refresh' ),
				'permission_callback' => static fn(): bool => current_user_can( 'manage_woocommerce' ),
				'args'                => array(
					'provider' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * Public access can be disabled in plugin settings.
	 *
	 * @return bool
	 */
	public function can_read_rates(): bool {
		return 'yes' === Settings::get( 'public_rest_api', 'yes' ) || current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Return sanitized local rate records.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function rates( \WP_REST_Request $request ): \WP_REST_Response {
		$symbols = array_filter( array_map( 'trim', explode( ',', (string) $request->get_param( 'symbols' ) ) ) );
		$assets  = $this->assets->all( $symbols ? array( 'symbols' => $symbols ) : array() );
		$data    = array_map( array( $this, 'serialize_asset' ), $assets );
		$latest  = 0;

		foreach ( $assets as $asset ) {
			$latest = max( $latest, $asset->updated_at ? (int) strtotime( $asset->updated_at . ' UTC' ) : 0 );
		}

		$response = new \WP_REST_Response(
			array(
				'data'        => $data,
				'count'       => count( $data ),
				'server_time' => time(),
				'updated_at'  => $latest,
			)
		);
		$response->header( 'Cache-Control', 'public, max-age=20, stale-while-revalidate=60' );
		return $response;
	}

	/**
	 * Run a forced provider refresh.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function refresh( \WP_REST_Request $request ): \WP_REST_Response {
		$provider = $request->get_param( 'provider' );
		return new \WP_REST_Response( $this->providers->refresh( true, $provider ?: null ) );
	}

	/**
	 * Convert an internal asset to public view data.
	 *
	 * @param object $asset Asset.
	 * @return array<string, mixed>
	 */
	private function serialize_asset( object $asset ): array {
		$updated = $asset->updated_at ? (int) strtotime( $asset->updated_at . ' UTC' ) : 0;
		$stale   = ! $updated || time() > $updated + ( max( 1, (int) Settings::get( 'stale_after_minutes', 60 ) ) * MINUTE_IN_SECONDS );

		return array(
			'id'             => (int) $asset->id,
			'symbol'         => (string) $asset->symbol,
			'name'           => (string) $asset->name,
			'category'       => (string) $asset->category,
			'unit'           => (string) $asset->unit,
			'quote_currency' => (string) $asset->quote_currency,
			'value'          => null !== $asset->value ? (float) $asset->value : null,
			'previous_value' => null !== $asset->previous_value ? (float) $asset->previous_value : null,
			'change_percent' => (float) $asset->change_percent,
			'status'         => $stale ? 'stale' : (string) $asset->status,
			'updated_at'     => $updated,
		);
	}
}
