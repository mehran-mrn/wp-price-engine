<?php
/**
 * WooCommerce-independent product pricing service.
 *
 * @package MRN_Price_Engine
 */

namespace MRNPE\Price_Engine;

defined( 'ABSPATH' ) || exit;

final class Pricing_Engine {
	public const META_PREFIX = '_mrnpe_';

	private Currency_Converter $converter;

	public function __construct( private Asset_Repository $assets ) {
		$this->converter = new Currency_Converter( $assets );
	}

	/**
	 * Read normalized dynamic pricing configuration.
	 *
	 * @param int $product_id Product or variation ID.
	 * @return array<string, mixed>
	 */
	public function config( int $product_id ): array {
		return array(
			'enabled'         => 'yes' === get_post_meta( $product_id, self::META_PREFIX . 'enabled', true ),
			'asset_id'        => absint( get_post_meta( $product_id, self::META_PREFIX . 'asset_id', true ) ),
			'quantity'        => (float) ( get_post_meta( $product_id, self::META_PREFIX . 'quantity', true ) ?: 1 ),
			'multiplier'      => (float) ( get_post_meta( $product_id, self::META_PREFIX . 'multiplier', true ) ?: 1 ),
			'percentage'      => (float) get_post_meta( $product_id, self::META_PREFIX . 'percentage', true ),
			'fixed'           => (float) get_post_meta( $product_id, self::META_PREFIX . 'fixed', true ),
			'rounding_step'   => (float) ( get_post_meta( $product_id, self::META_PREFIX . 'rounding_step', true ) ?: Settings::get( 'default_rounding_step', 0 ) ),
			'rounding_mode'   => (string) ( get_post_meta( $product_id, self::META_PREFIX . 'rounding_mode', true ) ?: Settings::get( 'default_rounding_mode', 'nearest' ) ),
			'sale_enabled'    => 'yes' === get_post_meta( $product_id, self::META_PREFIX . 'sale_enabled', true ),
			'sale_percentage' => (float) get_post_meta( $product_id, self::META_PREFIX . 'sale_percentage', true ),
			'sale_fixed'      => (float) get_post_meta( $product_id, self::META_PREFIX . 'sale_fixed', true ),
		);
	}

	/**
	 * Calculate one product from its locally cached asset.
	 *
	 * @param int $product_id Product or variation ID.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function calculate( int $product_id ): array|\WP_Error {
		$config = $this->config( $product_id );

		if ( ! $config['enabled'] ) {
			return new \WP_Error( 'mrnpe_not_dynamic', __( 'قیمت‌گذاری پویا برای این محصول فعال نیست.', 'mrn-gold-currency-pricing' ) );
		}

		$asset = $this->assets->find( (int) $config['asset_id'] );
		if ( ! $asset || null === $asset->value || (float) $asset->value <= 0 ) {
			return new \WP_Error( 'mrnpe_rate_missing', __( 'نرخ معتبر برای دارایی انتخاب‌شده در دسترس نیست.', 'mrn-gold-currency-pricing' ) );
		}

		$conversion = $this->normalize_asset_rate( $asset );
		if ( is_wp_error( $conversion ) ) {
			return $conversion;
		}

		$result                    = Pricing_Formula::calculate( (float) $conversion['value'], $config );
		$result['asset']           = $asset;
		$result['config']          = $config;
		$result['source_rate']     = (float) $asset->value;
		$result['source_currency'] = strtoupper( (string) $asset->quote_currency );
		$result['target_currency'] = (string) $conversion['to'];
		$result['conversion']      = $conversion;
		$result['rate_assets']     = array_merge( array( $asset ), (array) $conversion['assets'] );
		return $result;
	}

	/**
	 * Convert an asset rate into the WooCommerce store currency.
	 *
	 * @param object      $asset Local asset.
	 * @param string|null $target_currency Optional target override.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function normalize_asset_rate( object $asset, ?string $target_currency = null ): array|\WP_Error {
		if ( null === $asset->value || (float) $asset->value <= 0 ) {
			return new \WP_Error( 'mrnpe_rate_missing', __( 'نرخ معتبر برای دارایی انتخاب‌شده در دسترس نیست.', 'mrn-gold-currency-pricing' ) );
		}

		$target_currency = $target_currency ?: ( function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : (string) $asset->quote_currency );
		return $this->converter->convert( (float) $asset->value, (string) $asset->quote_currency, $target_currency );
	}

	/**
	 * Store calculated WooCommerce prices for lookup, filtering and sorting.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return bool
	 */
	public function persist( \WC_Product $product ): bool {
		$result = $this->calculate( $product->get_id() );
		if ( is_wp_error( $result ) ) {
			return false;
		}

		$product->set_regular_price( wc_format_decimal( $result['regular'] ) );
		$product->set_sale_price( null !== $result['sale'] ? wc_format_decimal( $result['sale'] ) : '' );
		$product->set_price( wc_format_decimal( $result['current'] ) );
		$product->update_meta_data( self::META_PREFIX . 'last_calculated', time() );
		$product->update_meta_data( self::META_PREFIX . 'rate_snapshot', (float) $result['rate'] );
		$product->save();

		return true;
	}

	/**
	 * Recalculate a bounded batch and schedule continuation.
	 *
	 * @param int $offset Result offset.
	 * @param int $limit Batch size.
	 * @return array<string, int>
	 */
	public function recalculate_batch( int $offset = 0, int $limit = 50 ): array {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array(
				'processed' => 0,
				'updated'   => 0,
				'remaining' => 0,
			);
		}

		$query = new \WP_Query(
			array(
				'post_type'              => array( 'product', 'product_variation' ),
				'post_status'            => array( 'publish', 'draft', 'private' ),
				'posts_per_page'         => max( 1, min( 200, $limit ) ),
				'offset'                 => max( 0, $offset ),
				'fields'                 => 'ids',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'meta_key'               => self::META_PREFIX . 'enabled',
				'meta_value'             => 'yes',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$updated = 0;
		$parents = array();
		foreach ( $query->posts as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			if ( $this->persist( $product ) ) {
				++$updated;
			}

			if ( $product->is_type( 'variation' ) ) {
				$parents[] = $product->get_parent_id();
			}
		}

		foreach ( array_unique( array_filter( $parents ) ) as $parent_id ) {
			\WC_Product_Variable::sync( $parent_id );
			wc_delete_product_transients( $parent_id );
		}

		$processed = count( $query->posts );
		$remaining = max( 0, (int) $query->found_posts - $offset - $processed );

		return compact( 'processed', 'updated', 'remaining' );
	}
}
