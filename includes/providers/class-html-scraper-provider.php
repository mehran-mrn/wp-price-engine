<?php
/**
 * Rate discovery from public HTML pages.
 *
 * @package MRN_Price_Engine
 */

namespace MRN\Price_Engine\Providers;

use MRN\Price_Engine\Abstract_Provider;
use MRN\Price_Engine\HTML_Rate_Parser;

defined( 'ABSPATH' ) || exit;

final class HTML_Scraper_Provider extends Abstract_Provider {
	private int $requests = 0;

	public function __construct( private string $provider_key ) {}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		return array(
			'milli_scraper'     => array(
				'label'       => 'Milli Gold — HTML',
				'description' => 'کشف قیمت یک گرم طلای ۱۸ عیار از widget عمومی Milli؛ خروجی ریال.',
				'enabled'     => true,
				'interval'    => 30,
			),
			'melligold_scraper' => array(
				'label'       => 'MelliGold — HTML',
				'description' => 'کشف قیمت طلا و سکه از صفحه‌های عمومی ملی‌گلد؛ خروجی تومان.',
				'enabled'     => true,
				'interval'    => 30,
			),
			'tgju_scraper'      => array(
				'label'       => 'TGJU — HTML',
				'description' => 'بیش از ۱۰۰ نرخ طلا، سکه، ارز و بازار جهانی با یک دریافت گروهی؛ خروجی داخلی ریال.',
				'enabled'     => true,
				'interval'    => 30,
			),
		);
	}

	/**
	 * @return array<int, self>
	 */
	public static function instances(): array {
		return array_map(
			static fn( string $key ): self => new self( $key ),
			array_keys( self::definitions() )
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function default_settings(): array {
		$settings = array();
		foreach ( self::definitions() as $key => $definition ) {
			$settings[ $key ] = array(
				'enabled'  => $definition['enabled'] ? 'yes' : 'no',
				'interval' => (int) $definition['interval'],
			);
		}
		return $settings;
	}

	public function key(): string {
		return $this->provider_key;
	}

	public function label(): string {
		return (string) self::definitions()[ $this->provider_key ]['label'];
	}

	public function description(): string {
		return (string) self::definitions()[ $this->provider_key ]['description'];
	}

	public function tier(): string {
		return 'scraper';
	}

	/**
	 * Generic field schema consumed by the administration UI.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function fields(): array {
		return array();
	}

	/**
	 * Number of source-page fetches initiated by the latest refresh.
	 */
	public function request_count(): int {
		return $this->requests;
	}

	/**
	 * @param array<int, object> $assets Assigned rate assets.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	public function fetch( array $assets ): array|\WP_Error {
		$this->requests = 0;
		$keys           = $this->asset_keys( $assets );
		if ( ! $keys ) {
			return array();
		}

		return match ( $this->provider_key ) {
			'milli_scraper'     => $this->fetch_milli( $keys ),
			'melligold_scraper' => $this->fetch_melligold( $keys ),
			'tgju_scraper'      => $this->fetch_tgju( $keys ),
			default             => new \WP_Error( 'mrnpe_scraper_unknown', __( 'منبع scraping ناشناخته است.', 'mrn-price-engine' ) ),
		};
	}

	/**
	 * @param array<int, string> $keys Requested provider keys.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	private function fetch_milli( array $keys ): array|\WP_Error {
		if ( ! in_array( 'gold_18k', $keys, true ) ) {
			return array();
		}

		$url  = 'https://milli.gold/external/milli-gold-widget';
		$html = $this->page( $url );
		if ( is_wp_error( $html ) ) {
			return $html;
		}

		$value = HTML_Rate_Parser::milli( $html );
		if ( null === $value || $value <= 0 ) {
			return $this->invalid_response( 'Milli' );
		}

		return array(
			'gold_18k' => $this->rate_item( $value, 'طلای ۱۸ عیار Milli', $url ),
		);
	}

	/**
	 * @param array<int, string> $keys Requested page slugs.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	private function fetch_melligold( array $keys ): array|\WP_Error {
		$allowed = array(
			'18-karat-gold'    => 'طلای ۱۸ عیار ملی‌گلد',
			'24-karat-gold'    => 'طلای ۲۴ عیار ملی‌گلد',
			'bahar-azadi-coin' => 'سکه بهار آزادی ملی‌گلد',
		);
		$rates   = array();
		$errors  = array();

		foreach ( array_unique( $keys ) as $slug ) {
			if ( ! isset( $allowed[ $slug ] ) ) {
				continue;
			}

			$url  = 'https://melligold.com/price/' . rawurlencode( $slug );
			$html = $this->page( $url );
			if ( is_wp_error( $html ) ) {
				$errors[] = $html->get_error_message();
				continue;
			}

			$value = HTML_Rate_Parser::melligold( $html );
			if ( null === $value || $value <= 0 ) {
				$errors[] = sprintf( 'Parser failed for %s.', $slug );
				continue;
			}

			$rates[ $slug ] = $this->rate_item( $value, $allowed[ $slug ], $url );
		}

		if ( ! $rates ) {
			return $this->invalid_response( 'MelliGold', implode( ' ', $errors ) );
		}

		return $rates;
	}

	/**
	 * @param array<int, string> $keys Requested TGJU symbols.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	private function fetch_tgju( array $keys ): array|\WP_Error {
		$url  = 'https://www.tgju.org/';
		$html = $this->page( $url );
		if ( is_wp_error( $html ) ) {
			return $html;
		}

		$discovered = HTML_Rate_Parser::tgju( $html );
		$rates      = array();
		foreach ( $keys as $key ) {
			$normalized = strtolower( $key );
			if ( isset( $discovered[ $normalized ] ) ) {
				$rates[ $key ] = $this->rate_item(
					$discovered[ $normalized ]['value'],
					$discovered[ $normalized ]['name'],
					$url
				);
			}
		}

		return $rates ?: $this->invalid_response( 'TGJU' );
	}

	/**
	 * Fetch one page and track the source-page request count.
	 *
	 * @return string|\WP_Error
	 */
	private function page( string $url ): string|\WP_Error {
		++$this->requests;
		return $this->get_html( $url );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function rate_item( float $value, string $name, string $url ): array {
		return array(
			'value'      => $value,
			'name'       => sanitize_text_field( $name ),
			'source_url' => $url,
			'method'     => 'html-scraping',
			'scraped_at' => time(),
		);
	}

	private function invalid_response( string $source, string $detail = '' ): \WP_Error {
		return new \WP_Error(
			'mrnpe_scraper_parse_failed',
			sprintf(
				/* translators: 1: source label, 2: optional parser detail. */
				__( 'ساختار HTML منبع %1$s قابل تشخیص نبود. نرخ قبلی حفظ شد. %2$s', 'mrn-price-engine' ),
				$source,
				$detail
			)
		);
	}
}
