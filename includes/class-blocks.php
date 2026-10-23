<?php
/**
 * Native dynamic block registration.
 *
 * @package MRN_Price_Engine
 */

namespace MRNPE\Price_Engine;

defined( 'ABSPATH' ) || exit;

final class Blocks {
	/**
	 * Register the rate board block.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Register scripts and server renderer.
	 *
	 * @return void
	 */
	public function register_block(): void {
		wp_register_script(
			'mrnpe-block-editor',
			MRNPE_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'wp-server-side-render' ),
			MRNPE_VERSION,
			true
		);

		wp_localize_script( 'mrnpe-block-editor', 'mrnpe_block', array( 'logoUrl' => MRNPE_URL . 'assets/images/logo.png' ) );

		register_block_type(
			MRNPE_PATH . 'blocks/price-board',
			array(
				'editor_script'   => 'mrnpe-block-editor',
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * Render block through the canonical shortcode view.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render( array $attributes ): string {
		$shortcodes = new Shortcodes( mrnpe_plugin()->assets() );
		return $shortcodes->board(
			array(
				'assets'       => (string) ( $attributes['assets'] ?? '' ),
				'layout'       => (string) ( $attributes['layout'] ?? 'cards' ),
				'theme'        => (string) ( $attributes['theme'] ?? 'auto' ),
				'columns'      => (string) ( $attributes['columns'] ?? 3 ),
				'show_change'  => ! empty( $attributes['showChange'] ) ? 'yes' : 'no',
				'show_updated' => ! empty( $attributes['showUpdated'] ) ? 'yes' : 'no',
				'title'        => (string) ( $attributes['title'] ?? '' ),
			)
		);
	}
}
