<?php
/**
 * Local cross-currency conversion graph.
 *
 * @package MRN_Price_Engine
 */

namespace MRNPE\Price_Engine;

defined( 'ABSPATH' ) || exit;

/**
 * Converts locally cached rates into the WooCommerce store currency.
 *
 * No network request is ever performed here. Currency pairs already stored in
 * the plugin form a small directed graph, so conversions such as
 * BTC/USD -> USD/USDT -> USDT/IRT can be resolved locally.
 */
final class Currency_Converter {
	/** @var array<string, array<int, array<string, mixed>>>|null */
	private ?array $cached_graph = null;

	public function __construct( private Asset_Repository $assets ) {}

	/**
	 * Convert an amount between currencies.
	 *
	 * @param float  $amount Amount in the source currency.
	 * @param string $from Source currency.
	 * @param string $to Target currency.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function convert( float $amount, string $from, string $to ): array|\WP_Error {
		$conversion = $this->rate( $from, $to );
		if ( is_wp_error( $conversion ) ) {
			return $conversion;
		}

		$conversion['value'] = $amount * (float) $conversion['rate'];
		return $conversion;
	}

	/**
	 * Resolve a conversion rate using a bounded breadth-first search.
	 *
	 * @param string $from Source currency.
	 * @param string $to Target currency.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function rate( string $from, string $to ): array|\WP_Error {
		$from = $this->normalize_code( $from );
		$to   = $this->normalize_code( $to );

		if ( ! $from || ! $to ) {
			return new \WP_Error( 'mrnpe_currency_invalid', __( 'کد ارز مبدأ یا مقصد معتبر نیست.', 'mrn-gold-currency-pricing' ) );
		}

		if ( $from === $to ) {
			return array(
				'rate'   => 1.0,
				'from'   => $from,
				'to'     => $to,
				'path'   => array( $from ),
				'assets' => array(),
			);
		}

		$graph   = $this->graph();
		$queue   = array(
			array(
				'code'   => $from,
				'rate'   => 1.0,
				'path'   => array( $from ),
				'assets' => array(),
				'hops'   => 0,
			),
		);
		$visited = array( $from => true );

		while ( $queue ) {
			$current = array_shift( $queue );
			if ( $current['hops'] >= 4 || empty( $graph[ $current['code'] ] ) ) {
				continue;
			}

			foreach ( $graph[ $current['code'] ] as $edge ) {
				$next = $edge['to'];
				if ( isset( $visited[ $next ] ) ) {
					continue;
				}

				$rate   = (float) $current['rate'] * (float) $edge['rate'];
				$path   = array_merge( $current['path'], array( $next ) );
				$assets = $current['assets'];
				if ( ! empty( $edge['asset'] ) ) {
					$assets[ (int) $edge['asset']->id ] = $edge['asset'];
				}

				if ( $next === $to ) {
					return array(
						'rate'   => $rate,
						'from'   => $from,
						'to'     => $to,
						'path'   => $path,
						'assets' => array_values( $assets ),
					);
				}

				$visited[ $next ] = true;
				$queue[]          = array(
					'code'   => $next,
					'rate'   => $rate,
					'path'   => $path,
					'assets' => $assets,
					'hops'   => (int) $current['hops'] + 1,
				);
			}
		}

		return new \WP_Error(
			'mrnpe_conversion_missing',
			sprintf(
				/* translators: 1: source currency, 2: store currency. */
				__( 'برای تبدیل %1$s به %2$s نرخ تبدیل محلی معتبری پیدا نشد.', 'mrn-gold-currency-pricing' ),
				$from,
				$to
			),
			array(
				'from' => $from,
				'to'   => $to,
			)
		);
	}

	/**
	 * Build directed edges from cached pair assets and safe unit aliases.
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private function graph(): array {
		if ( null !== $this->cached_graph ) {
			return $this->cached_graph;
		}

		$graph = array();

		// Iranian stores commonly use the public USDT/Toman market as the
		// transparent bridge for USD-denominated digital assets.
		$this->add_edge( $graph, 'USD', 'USDT', 1.0, null );
		$this->add_edge( $graph, 'USDT', 'USD', 1.0, null );
		$this->add_edge( $graph, 'IRR', 'IRT', 0.1, null );
		$this->add_edge( $graph, 'IRT', 'IRR', 10.0, null );
		$this->add_edge( $graph, 'TMN', 'IRT', 1.0, null );
		$this->add_edge( $graph, 'IRT', 'TMN', 1.0, null );

		foreach ( $this->assets->all() as $asset ) {
			$value = null !== $asset->value ? (float) $asset->value : 0.0;
			if ( $value <= 0 || ! preg_match( '/^([A-Z0-9]+)_([A-Z0-9]+)$/', strtoupper( (string) $asset->symbol ), $matches ) ) {
				continue;
			}

			$base  = $this->normalize_code( $matches[1] );
			$quote = $this->normalize_code( (string) $asset->quote_currency );
			if ( ! $base || ! $quote || $matches[2] !== $quote ) {
				continue;
			}

			$this->add_edge( $graph, $base, $quote, $value, $asset );
			$this->add_edge( $graph, $quote, $base, 1 / $value, $asset );
		}

		$this->cached_graph = $graph;
		return $this->cached_graph;
	}

	/**
	 * Add one graph edge.
	 *
	 * @param array<string, array<int, array<string, mixed>>> $graph Graph.
	 * @param string                                          $from Source code.
	 * @param string                                          $to Target code.
	 * @param float                                           $rate Conversion rate.
	 * @param object|null                                     $asset Backing local asset.
	 * @return void
	 */
	private function add_edge( array &$graph, string $from, string $to, float $rate, ?object $asset ): void {
		if ( $rate <= 0 || ! is_finite( $rate ) ) {
			return;
		}

		$graph[ $from ][] = array(
			'to'    => $to,
			'rate'  => $rate,
			'asset' => $asset,
		);
	}

	/**
	 * Normalize an ISO-like currency or asset code.
	 *
	 * @param string $code Raw code.
	 * @return string
	 */
	private function normalize_code( string $code ): string {
		return preg_replace( '/[^A-Z0-9]/', '', strtoupper( trim( $code ) ) ) ?: '';
	}
}
