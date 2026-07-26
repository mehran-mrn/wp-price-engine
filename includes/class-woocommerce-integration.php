<?php
/**
 * WooCommerce product editing, runtime pricing and order snapshots.
 *
 * @package MRN_Price_Engine
 */

namespace MRN\Price_Engine;

defined( 'ABSPATH' ) || exit;

final class WooCommerce_Integration {
	public function __construct(
		private Asset_Repository $assets,
		private Pricing_Engine $pricing
	) {}

	/**
	 * Register WooCommerce hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'product_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'product_panel' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_product' ), 99 );
		add_action( 'woocommerce_variation_options_pricing', array( $this, 'variation_fields' ), 20, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation' ), 99, 2 );

		foreach ( array( 'price', 'regular_price', 'sale_price' ) as $price_type ) {
			add_filter( 'woocommerce_product_get_' . $price_type, array( $this, 'runtime_price' ), 99, 2 );
			add_filter( 'woocommerce_product_variation_get_' . $price_type, array( $this, 'runtime_price' ), 99, 2 );
		}

		add_filter( 'woocommerce_is_purchasable', array( $this, 'is_purchasable' ), 99, 2 );
		add_filter( 'woocommerce_variation_is_purchasable', array( $this, 'is_purchasable' ), 99, 2 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'cart_snapshot' ), 20, 3 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_cart_snapshot' ), 20 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'order_snapshot' ), 20, 4 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'product_breakdown' ), 25 );
	}

	/**
	 * Add the dedicated product data tab.
	 *
	 * @param array<string, mixed> $tabs Product tabs.
	 * @return array<string, mixed>
	 */
	public function product_tab( array $tabs ): array {
		$tabs['mrnpe'] = array(
			'label'    => __( 'قیمت‌گذاری پویا', 'mrn-price-engine' ),
			'target'   => 'mrnpe_product_data',
			'class'    => array( 'show_if_simple', 'show_if_external' ),
			'priority' => 61,
		);
		return $tabs;
	}

	/**
	 * Render simple product formula controls.
	 *
	 * @return void
	 */
	public function product_panel(): void {
		global $post;
		$config = $this->pricing->config( (int) $post->ID );
		?>
		<div id="mrnpe_product_data" class="panel woocommerce_options_panel hidden mrnpe-product-panel">
			<div class="mrnpe-product-intro">
				<strong><?php esc_html_e( 'قیمت زنده، بدون درخواست زنده', 'mrn-price-engine' ); ?></strong>
				<span><?php esc_html_e( 'قیمت از آخرین نرخ معتبر محلی محاسبه و برای مرتب‌سازی و فیلتر ووکامرس ذخیره می‌شود.', 'mrn-price-engine' ); ?></span>
			</div>
			<?php
			woocommerce_wp_checkbox(
				array(
					'id'          => '_mrnpe_enabled',
					'label'       => __( 'فعال‌سازی قیمت پویا', 'mrn-price-engine' ),
					'value'       => $config['enabled'] ? 'yes' : 'no',
					'description' => __( 'قیمت‌های ثابت محصول با فرمول زیر جایگزین می‌شوند.', 'mrn-price-engine' ),
				)
			);
			$this->render_formula_fields( (int) $post->ID, '', $config );
			?>
			<div class="mrnpe-formula-preview" data-mrnpe-preview>
				<span><?php esc_html_e( 'پیش‌نمایش فرمول', 'mrn-price-engine' ); ?></span>
				<strong><?php esc_html_e( 'پس از انتخاب نرخ نمایش داده می‌شود', 'mrn-price-engine' ); ?></strong>
			</div>
		</div>
		<?php
	}

	/**
	 * Render pricing controls for a variation.
	 *
	 * @param int          $loop Variation loop index.
	 * @param array<mixed> $variation_data Variation data.
	 * @param \WP_Post     $variation Variation post.
	 * @return void
	 */
	public function variation_fields( int $loop, array $variation_data, \WP_Post $variation ): void {
		$config = $this->pricing->config( $variation->ID );
		?>
		<div class="mrnpe-variation-fields form-row form-row-full">
			<label>
				<input type="checkbox" name="mrnpe_variation[<?php echo esc_attr( (string) $loop ); ?>][enabled]" value="yes" <?php checked( $config['enabled'] ); ?>>
				<?php esc_html_e( 'قیمت‌گذاری پویای این متغیر', 'mrn-price-engine' ); ?>
			</label>
			<?php $this->render_variation_formula_fields( $loop, $config ); ?>
		</div>
		<?php
	}

	/**
	 * Shared rich product formula fields.
	 *
	 * @param int                  $product_id Product ID.
	 * @param string               $prefix Field prefix.
	 * @param array<string, mixed> $config Values.
	 * @return void
	 */
	private function render_formula_fields( int $product_id, string $prefix, array $config ): void {
		$options = array( '' => __( 'یک نرخ را انتخاب کنید…', 'mrn-price-engine' ) );
		foreach ( $this->assets->all() as $asset ) {
			$rate_label = null !== $asset->value
				? number_format_i18n( (float) $asset->value, wc_get_price_decimals() ) . ' ' . strtoupper( (string) $asset->quote_currency )
				: __( 'بدون نرخ', 'mrn-price-engine' );
			$options[ (string) $asset->id ] = sprintf( '%s — %s (%s)', $asset->name, $asset->symbol, $rate_label );
		}

		woocommerce_wp_select(
			array(
				'id'          => '_mrnpe_asset_id',
				'label'       => __( 'نرخ مبنا', 'mrn-price-engine' ),
				'value'       => $config['asset_id'],
				'options'     => $options,
				'desc_tip'    => true,
				'description' => __( 'مثلاً قیمت هر گرم طلای ۱۸ عیار.', 'mrn-price-engine' ),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'                => '_mrnpe_quantity',
				'label'             => __( 'مقدار / وزن', 'mrn-price-engine' ),
				'value'             => $config['quantity'],
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '0.000001',
				),
				'description'       => __( 'مثال: 1.43 گرم', 'mrn-price-engine' ),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'                => '_mrnpe_multiplier',
				'label'             => __( 'ضریب محصول', 'mrn-price-engine' ),
				'value'             => $config['multiplier'],
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '0.000001',
				),
				'description'       => __( 'برای عیار، پرت یا تبدیل واحد؛ مقدار عادی ۱ است.', 'mrn-price-engine' ),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'                => '_mrnpe_percentage',
				'label'             => __( 'افزایش درصدی', 'mrn-price-engine' ),
				'value'             => $config['percentage'],
				'type'              => 'number',
				'custom_attributes' => array( 'step' => '0.01' ),
				'description'       => '%',
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'                => '_mrnpe_fixed',
				'label'             => __( 'مبلغ ثابت', 'mrn-price-engine' ),
				'value'             => $config['fixed'],
				'type'              => 'number',
				'custom_attributes' => array( 'step' => '1' ),
				'description'       => get_woocommerce_currency_symbol(),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'                => '_mrnpe_rounding_step',
				'label'             => __( 'گام گرد کردن', 'mrn-price-engine' ),
				'value'             => $config['rounding_step'],
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '1',
				),
				'description'       => __( 'مثلاً ۱۰۰۰؛ صفر یعنی بدون گرد کردن.', 'mrn-price-engine' ),
			)
		);
		woocommerce_wp_select(
			array(
				'id'      => '_mrnpe_rounding_mode',
				'label'   => __( 'روش گرد کردن', 'mrn-price-engine' ),
				'value'   => $config['rounding_mode'],
				'options' => array(
					'nearest' => __( 'نزدیک‌ترین', 'mrn-price-engine' ),
					'up'      => __( 'همیشه رو به بالا', 'mrn-price-engine' ),
					'down'    => __( 'همیشه رو به پایین', 'mrn-price-engine' ),
				),
			)
		);
		woocommerce_wp_checkbox(
			array(
				'id'    => '_mrnpe_sale_enabled',
				'label' => __( 'قیمت فروش ویژه پویا', 'mrn-price-engine' ),
				'value' => $config['sale_enabled'] ? 'yes' : 'no',
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'                => '_mrnpe_sale_percentage',
				'label'             => __( 'تخفیف درصدی', 'mrn-price-engine' ),
				'value'             => $config['sale_percentage'],
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => '0',
					'max'  => '100',
					'step' => '0.01',
				),
				'description'       => '%',
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'                => '_mrnpe_sale_fixed',
				'label'             => __( 'تخفیف ثابت', 'mrn-price-engine' ),
				'value'             => $config['sale_fixed'],
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '1',
				),
				'description'       => get_woocommerce_currency_symbol(),
			)
		);
	}

	/**
	 * Compact raw fields for variation rows.
	 *
	 * @param int                  $loop Variation loop.
	 * @param array<string, mixed> $config Values.
	 * @return void
	 */
	private function render_variation_formula_fields( int $loop, array $config ): void {
		$name = 'mrnpe_variation[' . $loop . ']';
		?>
		<div class="mrnpe-variation-grid">
			<label><?php esc_html_e( 'نرخ مبنا', 'mrn-price-engine' ); ?>
				<select name="<?php echo esc_attr( $name . '[asset_id]' ); ?>">
					<option value=""><?php esc_html_e( 'انتخاب نرخ…', 'mrn-price-engine' ); ?></option>
					<?php foreach ( $this->assets->all() as $asset ) : ?>
						<option value="<?php echo esc_attr( (string) $asset->id ); ?>" <?php selected( $config['asset_id'], $asset->id ); ?>><?php echo esc_html( $asset->name . ' — ' . $asset->symbol ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<?php
			$fields = array(
				'quantity'        => __( 'مقدار / وزن', 'mrn-price-engine' ),
				'multiplier'      => __( 'ضریب', 'mrn-price-engine' ),
				'percentage'      => __( 'افزایش %', 'mrn-price-engine' ),
				'fixed'           => __( 'مبلغ ثابت', 'mrn-price-engine' ),
				'rounding_step'   => __( 'گام گرد کردن', 'mrn-price-engine' ),
				'sale_percentage' => __( 'تخفیف %', 'mrn-price-engine' ),
				'sale_fixed'      => __( 'تخفیف ثابت', 'mrn-price-engine' ),
			);
			foreach ( $fields as $key => $label ) :
				?>
				<label><?php echo esc_html( $label ); ?>
					<input type="number" step="0.000001" name="<?php echo esc_attr( $name . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( (string) $config[ $key ] ); ?>">
				</label>
			<?php endforeach; ?>
			<label><?php esc_html_e( 'گرد کردن', 'mrn-price-engine' ); ?>
				<select name="<?php echo esc_attr( $name . '[rounding_mode]' ); ?>">
					<option value="nearest" <?php selected( $config['rounding_mode'], 'nearest' ); ?>><?php esc_html_e( 'نزدیک‌ترین', 'mrn-price-engine' ); ?></option>
					<option value="up" <?php selected( $config['rounding_mode'], 'up' ); ?>><?php esc_html_e( 'رو به بالا', 'mrn-price-engine' ); ?></option>
					<option value="down" <?php selected( $config['rounding_mode'], 'down' ); ?>><?php esc_html_e( 'رو به پایین', 'mrn-price-engine' ); ?></option>
				</select>
			</label>
			<label class="mrnpe-check"><input type="checkbox" name="<?php echo esc_attr( $name . '[sale_enabled]' ); ?>" value="yes" <?php checked( $config['sale_enabled'] ); ?>> <?php esc_html_e( 'فروش ویژه', 'mrn-price-engine' ); ?></label>
		</div>
		<?php
	}

	/**
	 * Save simple product pricing configuration and materialized price.
	 *
	 * @param \WC_Product $product Product object.
	 * @return void
	 */
	public function save_product( \WC_Product $product ): void {
		if ( $product->is_type( 'variable' ) ) {
			return;
		}

		$this->save_meta( $product->get_id(), wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the product nonce.

		if ( 'yes' === get_post_meta( $product->get_id(), '_mrnpe_enabled', true ) ) {
			$this->pricing->persist( $product );
		}
	}

	/**
	 * Save variation formula.
	 *
	 * @param int $variation_id Variation ID.
	 * @param int $loop Variation loop.
	 * @return void
	 */
	public function save_variation( int $variation_id, int $loop ): void {
		$rows = isset( $_POST['mrnpe_variation'] ) ? wp_unslash( (array) $_POST['mrnpe_variation'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$data = isset( $rows[ $loop ] ) ? (array) $rows[ $loop ] : array();
		$this->save_meta( $variation_id, $data, '' );

		$product = wc_get_product( $variation_id );
		if ( $product && 'yes' === get_post_meta( $variation_id, '_mrnpe_enabled', true ) ) {
			$this->pricing->persist( $product );
		}
	}

	/**
	 * Sanitize and save product pricing meta.
	 *
	 * @param int                  $product_id Product ID.
	 * @param array<string, mixed> $data Request values.
	 * @param string               $field_prefix Optional request field prefix.
	 * @return void
	 */
	private function save_meta( int $product_id, array $data, string $field_prefix = '_' ): void {
		$source = array();
		foreach ( array( 'enabled', 'asset_id', 'quantity', 'multiplier', 'percentage', 'fixed', 'rounding_step', 'rounding_mode', 'sale_enabled', 'sale_percentage', 'sale_fixed' ) as $field ) {
			$request_key      = $field_prefix . 'mrnpe_' . $field;
			$source[ $field ] = $data[ $request_key ] ?? $data[ $field ] ?? null;
		}

		update_post_meta( $product_id, '_mrnpe_enabled', 'yes' === $source['enabled'] ? 'yes' : 'no' );
		update_post_meta( $product_id, '_mrnpe_asset_id', absint( $source['asset_id'] ) );
		update_post_meta( $product_id, '_mrnpe_quantity', max( 0, (float) $source['quantity'] ) );
		update_post_meta( $product_id, '_mrnpe_multiplier', max( 0, (float) $source['multiplier'] ) );
		update_post_meta( $product_id, '_mrnpe_percentage', (float) $source['percentage'] );
		update_post_meta( $product_id, '_mrnpe_fixed', (float) $source['fixed'] );
		update_post_meta( $product_id, '_mrnpe_rounding_step', max( 0, (float) $source['rounding_step'] ) );
		update_post_meta( $product_id, '_mrnpe_rounding_mode', in_array( $source['rounding_mode'], array( 'nearest', 'up', 'down' ), true ) ? $source['rounding_mode'] : 'nearest' );
		update_post_meta( $product_id, '_mrnpe_sale_enabled', 'yes' === $source['sale_enabled'] ? 'yes' : 'no' );
		update_post_meta( $product_id, '_mrnpe_sale_percentage', max( 0, min( 100, (float) $source['sale_percentage'] ) ) );
		update_post_meta( $product_id, '_mrnpe_sale_fixed', max( 0, (float) $source['sale_fixed'] ) );
	}

	/**
	 * Return a live local calculation for WooCommerce reads.
	 *
	 * @param string|float $value Stored price.
	 * @param \WC_Product  $product Product.
	 * @return string|float
	 */
	public function runtime_price( string|float $value, \WC_Product $product ): string|float {
		$result = $this->pricing->calculate( $product->get_id() );
		if ( is_wp_error( $result ) ) {
			return $value;
		}

		$filter = current_filter();
		if ( str_ends_with( $filter, '_regular_price' ) ) {
			return wc_format_decimal( $result['regular'] );
		}
		if ( str_ends_with( $filter, '_sale_price' ) ) {
			return null !== $result['sale'] ? wc_format_decimal( $result['sale'] ) : '';
		}
		return wc_format_decimal( $result['current'] );
	}

	/**
	 * Disable purchasing only when the merchant chose strict stale handling.
	 *
	 * @param bool        $purchasable Current value.
	 * @param \WC_Product $product Product.
	 * @return bool
	 */
	public function is_purchasable( bool $purchasable, \WC_Product $product ): bool {
		if ( 'disable_purchase' !== Settings::get( 'stale_policy', 'last_known' ) ) {
			return $purchasable;
		}

		$result = $this->pricing->calculate( $product->get_id() );
		if ( is_wp_error( $result ) ) {
			return 'mrnpe_not_dynamic' === $result->get_error_code() ? $purchasable : false;
		}

		$stale = max( 1, (int) Settings::get( 'stale_after_minutes', 60 ) ) * MINUTE_IN_SECONDS;
		foreach ( (array) ( $result['rate_assets'] ?? array( $result['asset'] ) ) as $rate_asset ) {
			$updated = $rate_asset->updated_at ? strtotime( $rate_asset->updated_at . ' UTC' ) : 0;
			if ( ! $updated || time() > $updated + $stale ) {
				return false;
			}
		}

		return $purchasable;
	}

	/**
	 * Capture the rate and price when the item enters the cart.
	 *
	 * @param array<string, mixed> $cart_item_data Cart data.
	 * @param int                  $product_id Product ID.
	 * @param int                  $variation_id Variation ID.
	 * @return array<string, mixed>
	 */
	public function cart_snapshot( array $cart_item_data, int $product_id, int $variation_id ): array {
		if ( 'yes' !== Settings::get( 'cart_price_lock', 'yes' ) ) {
			return $cart_item_data;
		}

		$id     = $variation_id ?: $product_id;
		$result = $this->pricing->calculate( $id );
		if ( is_wp_error( $result ) ) {
			return $cart_item_data;
		}

		$cart_item_data['mrnpe_snapshot'] = array(
			'asset_id'          => (int) $result['asset']->id,
			'symbol'            => (string) $result['asset']->symbol,
			'asset_name'        => (string) $result['asset']->name,
			'source_rate'       => (float) $result['source_rate'],
			'source_currency'   => (string) $result['source_currency'],
			'rate'              => (float) $result['rate'],
			'target_currency'   => (string) $result['target_currency'],
			'conversion_path'   => implode( ' → ', (array) $result['conversion']['path'] ),
			'quantity'          => (float) $result['quantity'],
			'price'             => (float) $result['current'],
			'captured_at'       => time(),
		);
		return $cart_item_data;
	}

	/**
	 * Keep a cart item at its captured price.
	 *
	 * @param \WC_Cart $cart Cart.
	 * @return void
	 */
	public function apply_cart_snapshot( \WC_Cart $cart ): void {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		foreach ( $cart->get_cart() as $item ) {
			if ( isset( $item['mrnpe_snapshot']['price'] ) && isset( $item['data'] ) && $item['data'] instanceof \WC_Product ) {
				$item['data']->set_price( wc_format_decimal( $item['mrnpe_snapshot']['price'] ) );
			}
		}
	}

	/**
	 * Persist an auditable rate snapshot on order items.
	 *
	 * @param \WC_Order_Item_Product $item Order item.
	 * @param string                 $cart_item_key Cart key.
	 * @param array<string, mixed>   $values Cart values.
	 * @param \WC_Order              $order Order.
	 * @return void
	 */
	public function order_snapshot( \WC_Order_Item_Product $item, string $cart_item_key, array $values, \WC_Order $order ): void {
		if ( empty( $values['mrnpe_snapshot'] ) ) {
			return;
		}

		$snapshot = $values['mrnpe_snapshot'];
		$item->add_meta_data( '_mrnpe_rate_snapshot', wp_json_encode( $snapshot, JSON_UNESCAPED_UNICODE ), true );
		$item->add_meta_data(
			__( 'مبنای قیمت', 'mrn-price-engine' ),
			sprintf(
				'%1$s — %2$s %3$s → %4$s × %5$s',
				$snapshot['asset_name'],
				wc_format_localized_decimal( $snapshot['source_rate'] ),
				$snapshot['source_currency'],
				wp_strip_all_tags( wc_price( $snapshot['rate'] ) ),
				$snapshot['quantity']
			),
			true
		);
	}

	/**
	 * Show a compact, theme-neutral formula summary on product pages.
	 *
	 * @return void
	 */
	public function product_breakdown(): void {
		if ( 'yes' !== Settings::get( 'show_product_breakdown', 'yes' ) ) {
			return;
		}

		global $product;
		if ( ! $product instanceof \WC_Product || $product->is_type( 'variable' ) ) {
			return;
		}

		$result = $this->pricing->calculate( $product->get_id() );
		if ( is_wp_error( $result ) ) {
			return;
		}

		wp_enqueue_style( 'mrnpe-frontend', MRNPE_URL . 'assets/css/frontend.css', array(), MRNPE_VERSION );
		?>
		<div class="mrnpe-product-breakdown">
			<span class="mrnpe-product-breakdown__mark" aria-hidden="true"></span>
			<div>
				<strong><?php echo esc_html( $result['asset']->name ); ?></strong>
				<small>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: 1: quantity, 2: unit, 3: last update. */
							__( '%1$s %2$s بر پایه آخرین نرخ محلی • به‌روزرسانی %3$s', 'mrn-price-engine' ),
							wc_format_localized_decimal( $result['quantity'] ),
							esc_html( $result['asset']->unit ),
							esc_html( human_time_diff( strtotime( $result['asset']->updated_at . ' UTC' ), time() ) . ' پیش' )
						)
					);
					?>
				</small>
			</div>
		</div>
		<?php
	}
}
