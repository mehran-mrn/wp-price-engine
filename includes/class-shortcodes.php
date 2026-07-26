<?php
/**
 * Theme-neutral rate board shortcodes.
 *
 * @package MRN_Price_Engine
 */

namespace MRN\Price_Engine;

defined( 'ABSPATH' ) || exit;

final class Shortcodes {
	public function __construct( private Asset_Repository $assets ) {}

	/**
	 * Register public shortcodes and assets.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_shortcode( 'mrn_price_board', array( $this, 'board' ) );
		add_shortcode( 'mrn_rate', array( $this, 'single_rate' ) );
	}

	/**
	 * Register assets lazily.
	 *
	 * @return void
	 */
	public function register_assets(): void {
		wp_register_style( 'mrnpe-frontend', MRNPE_URL . 'assets/css/frontend.css', array(), MRNPE_VERSION );
		wp_register_script( 'mrnpe-frontend', MRNPE_URL . 'assets/js/frontend.js', array(), MRNPE_VERSION, true );
		wp_localize_script(
			'mrnpe-frontend',
			'mrnpePublic',
			array(
				'endpoint' => rest_url( 'mrn-price-engine/v1/rates' ),
				'locale'   => str_replace( '_', '-', get_locale() ),
				'labels'   => array(
					'updated' => __( 'به‌روزرسانی شد', 'mrn-price-engine' ),
					'error'   => __( 'دریافت نرخ محلی ممکن نشد', 'mrn-price-engine' ),
				),
			)
		);
	}

	/**
	 * Render a rate board table, cards or ticker.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function board( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'assets'       => '',
				'symbols'      => '',
				'layout'       => 'table',
				'theme'        => 'auto',
				'columns'      => '3',
				'show_change'  => 'yes',
				'show_updated' => 'yes',
				'auto_refresh' => (string) Settings::get( 'frontend_refresh', 60 ),
				'decimals'     => '0',
				'accent'       => (string) Settings::get( 'accent_color', '#b8872e' ),
				'title'        => '',
			),
			$atts,
			'mrn_price_board'
		);

		$layout      = in_array( $atts['layout'], array( 'table', 'cards', 'ticker' ), true ) ? $atts['layout'] : 'table';
		$theme       = in_array( $atts['theme'], array( 'auto', 'light', 'dark' ), true ) ? $atts['theme'] : 'auto';
		$symbol_list = $atts['symbols'] ?: $atts['assets'];
		$symbols     = array_filter( array_map( 'trim', explode( ',', (string) $symbol_list ) ) );
		$assets  = $this->assets->all( $symbols ? array( 'symbols' => $symbols ) : array() );

		if ( ! $assets ) {
			return current_user_can( 'manage_woocommerce' )
				? '<div class="mrnpe-empty">' . esc_html__( 'هنوز نرخی برای نمایش تعریف نشده است.', 'mrn-price-engine' ) . '</div>'
				: '';
		}

		wp_enqueue_style( 'mrnpe-frontend' );
		wp_enqueue_script( 'mrnpe-frontend' );

		$classes = array( 'mrnpe-board', 'mrnpe-board--' . $layout, 'mrnpe-board--' . $theme );
		$style   = '--mrnpe-accent:' . sanitize_hex_color( (string) $atts['accent'] ) . ';--mrnpe-columns:' . max( 1, min( 6, (int) $atts['columns'] ) );

		ob_start();
		?>
		<section
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			style="<?php echo esc_attr( $style ); ?>"
			data-mrnpe-board
			data-symbols="<?php echo esc_attr( implode( ',', wp_list_pluck( $assets, 'symbol' ) ) ); ?>"
			data-refresh="<?php echo esc_attr( (string) max( 0, (int) $atts['auto_refresh'] ) ); ?>"
			data-decimals="<?php echo esc_attr( (string) max( 0, min( 8, (int) $atts['decimals'] ) ) ); ?>"
		>
			<?php if ( $atts['title'] ) : ?>
				<header class="mrnpe-board__header">
					<div>
						<span class="mrnpe-board__eyebrow"><?php esc_html_e( 'نبض بازار', 'mrn-price-engine' ); ?></span>
						<h2><?php echo esc_html( (string) $atts['title'] ); ?></h2>
					</div>
					<span class="mrnpe-board__live"><i></i><?php esc_html_e( 'داده محلی زنده', 'mrn-price-engine' ); ?></span>
				</header>
			<?php endif; ?>

			<?php if ( 'table' === $layout ) : ?>
				<div class="mrnpe-board__table-wrap">
					<table class="mrnpe-board__table">
						<thead><tr>
							<th><?php esc_html_e( 'عنوان', 'mrn-price-engine' ); ?></th>
							<th><?php esc_html_e( 'قیمت', 'mrn-price-engine' ); ?></th>
							<?php
							if ( 'yes' === $atts['show_change'] ) :
								?>
								<th><?php esc_html_e( 'تغییر', 'mrn-price-engine' ); ?></th><?php endif; ?>
							<?php
							if ( 'yes' === $atts['show_updated'] ) :
								?>
								<th><?php esc_html_e( 'آخرین نرخ', 'mrn-price-engine' ); ?></th><?php endif; ?>
						</tr></thead>
						<tbody>
							<?php foreach ( $assets as $asset ) : ?>
								<?php $this->render_table_row( $asset, $atts ); ?>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<div class="mrnpe-board__items">
					<?php foreach ( $assets as $asset ) : ?>
						<?php $this->render_card( $asset, $atts, 'ticker' === $layout ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<span class="screen-reader-text" data-mrnpe-status aria-live="polite"></span>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render a single inline local rate.
	 *
	 * @param array<string, mixed> $atts Attributes.
	 * @return string
	 */
	public function single_rate( array $atts = array() ): string {
		$atts  = shortcode_atts(
			array(
				'symbol'        => '',
				'decimals'      => '0',
				'show_currency' => 'yes',
			),
			$atts,
			'mrn_rate'
		);
		$asset = $this->assets->find( (string) $atts['symbol'] );

		if ( ! $asset || null === $asset->value ) {
			return '';
		}

		$value = number_format_i18n( (float) $asset->value, max( 0, min( 8, (int) $atts['decimals'] ) ) );
		return sprintf(
			'<span class="mrnpe-rate" data-mrnpe-symbol="%1$s">%2$s%3$s</span>',
			esc_attr( $asset->symbol ),
			esc_html( $value ),
			'yes' === $atts['show_currency'] ? ' <small>' . esc_html( $asset->quote_currency ) . '</small>' : ''
		);
	}

	/**
	 * Table row.
	 *
	 * @param object               $asset Asset.
	 * @param array<string, mixed> $atts Attributes.
	 * @return void
	 */
	private function render_table_row( object $asset, array $atts ): void {
		$direction = (float) $asset->change_percent > 0 ? 'up' : ( (float) $asset->change_percent < 0 ? 'down' : 'flat' );
		?>
		<tr data-mrnpe-asset="<?php echo esc_attr( $asset->symbol ); ?>">
			<td><strong><?php echo esc_html( $asset->name ); ?></strong><small><?php echo esc_html( $asset->symbol . ' · ' . $asset->unit ); ?></small></td>
			<td><b data-mrnpe-value><?php echo esc_html( null !== $asset->value ? number_format_i18n( (float) $asset->value, (int) $atts['decimals'] ) : '—' ); ?></b> <em><?php echo esc_html( $asset->quote_currency ); ?></em></td>
			<?php
			if ( 'yes' === $atts['show_change'] ) :
				?>
				<td><span class="mrnpe-change mrnpe-change--<?php echo esc_attr( $direction ); ?>" data-mrnpe-change><?php echo esc_html( number_format_i18n( abs( (float) $asset->change_percent ), 2 ) . '%' ); ?></span></td><?php endif; ?>
			<?php
			if ( 'yes' === $atts['show_updated'] ) :
				?>
				<td><time data-mrnpe-time datetime="<?php echo esc_attr( $asset->updated_at ?: '' ); ?>"><?php echo esc_html( $asset->updated_at ? human_time_diff( strtotime( $asset->updated_at . ' UTC' ), time() ) . ' پیش' : '—' ); ?></time></td><?php endif; ?>
		</tr>
		<?php
	}

	/**
	 * Card or ticker item.
	 *
	 * @param object               $asset Asset.
	 * @param array<string, mixed> $atts Attributes.
	 * @param bool                 $compact Compact ticker.
	 * @return void
	 */
	private function render_card( object $asset, array $atts, bool $compact ): void {
		$direction = (float) $asset->change_percent > 0 ? 'up' : ( (float) $asset->change_percent < 0 ? 'down' : 'flat' );
		?>
		<article class="mrnpe-card" data-mrnpe-asset="<?php echo esc_attr( $asset->symbol ); ?>">
			<div class="mrnpe-card__icon"><?php echo esc_html( mb_substr( $asset->name, 0, 1 ) ); ?></div>
			<div class="mrnpe-card__main">
				<span><?php echo esc_html( $asset->name ); ?></span>
				<small><?php echo esc_html( $asset->symbol ); ?></small>
			</div>
			<div class="mrnpe-card__price">
				<strong data-mrnpe-value><?php echo esc_html( null !== $asset->value ? number_format_i18n( (float) $asset->value, (int) $atts['decimals'] ) : '—' ); ?></strong>
				<small><?php echo esc_html( $asset->quote_currency ); ?></small>
			</div>
			<?php
			if ( 'yes' === $atts['show_change'] ) :
				?>
				<span class="mrnpe-change mrnpe-change--<?php echo esc_attr( $direction ); ?>" data-mrnpe-change><?php echo esc_html( number_format_i18n( abs( (float) $asset->change_percent ), 2 ) . '%' ); ?></span><?php endif; ?>
			<?php
			if ( ! $compact && 'yes' === $atts['show_updated'] ) :
				?>
				<time data-mrnpe-time datetime="<?php echo esc_attr( $asset->updated_at ?: '' ); ?>"><?php echo esc_html( $asset->updated_at ? human_time_diff( strtotime( $asset->updated_at . ' UTC' ), time() ) . ' پیش' : '—' ); ?></time><?php endif; ?>
		</article>
		<?php
	}
}
