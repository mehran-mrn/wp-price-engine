<?php
/**
 * Extended provider catalog adapted from the supplied reference plugin.
 *
 * @package MRN_Price_Engine
 */

namespace MRN\Price_Engine\Providers;

use MRN\Price_Engine\Abstract_Provider;
use MRN\Price_Engine\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * One audited adapter class for the extended provider catalog.
 *
 * Public providers use their current JSON endpoints. Keyed and legacy
 * providers remain available to merchants without being called until an
 * asset is assigned and the source is enabled.
 */
final class Extended_Provider extends Abstract_Provider {
	/**
	 * @param string $provider_key Catalog key.
	 */
	public function __construct( private string $provider_key ) {}

	/**
	 * Return one instance for every catalog definition.
	 *
	 * @return array<int, self>
	 */
	public static function instances(): array {
		return array_map(
			static fn( string $key ): self => new self( $key ),
			array_keys( self::definitions() )
		);
	}

	/**
	 * Provider definitions, UI metadata and safe defaults.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		$api_key = array(
			'api_key' => array(
				'label'   => 'کلید API',
				'type'    => 'password',
				'secret'  => true,
				'default' => '',
			),
		);
		$token = array(
			'token' => array(
				'label'   => 'توکن',
				'type'    => 'password',
				'secret'  => true,
				'default' => '',
			),
		);

		return array(
			'abantether'       => self::definition( 'Aban Tether', 'بازار رمزارز ایران؛ نیازمند کلید دسترسی عمومی API.', false, 10, $api_key, 'free-key' ),
			'apimaster'         => self::definition( 'API Master', 'نرخ ارز، طلا و سکه ایران.', false, 15, $api_key, 'paid' ),
			'arzpaya'           => self::definition( 'ArzPaya', 'بازار عمومی رمزارز به تومان؛ بدون کلید.', true, 10, array(), 'public' ),
			'bitpin'            => self::definition( 'Bitpin', 'بازار عمومی رمزارز به تومان؛ بدون کلید.', true, 10, array(), 'public' ),
			'bonbast'           => self::definition(
				'Bonbast',
				'نرخ ارز بازار ایران؛ اتصال حساب‌محور.',
				false,
				15,
				array(
					'username' => array( 'label' => 'نام کاربری', 'type' => 'text', 'secret' => true, 'default' => '' ),
					'password' => array( 'label' => 'رمز عبور', 'type' => 'password', 'secret' => true, 'default' => '' ),
				),
				'paid'
			),
			'coinmarketcap'     => self::definition( 'CoinMarketCap', 'قیمت جهانی رمزارز؛ پلن رایگان نیازمند API Key.', false, 15, $api_key, 'free-key' ),
			'commoditiesapicom' => self::definition( 'Commodities-API', 'کالا، فلز و ارز؛ پلن رایگان نیازمند API Key.', false, 60, $api_key, 'free-key' ),
			'cryptocompare'     => self::definition(
				'CryptoCompare',
				'قیمت گروهی رمزارز؛ کلید اختیاری با سهمیه بیشتر.',
				false,
				15,
				array_merge(
					$api_key,
					array( 'quote' => array( 'label' => 'ارز خروجی', 'type' => 'text', 'default' => 'USD' ) )
				),
				'free-key'
			),
			'exchangerateapi'   => self::definition(
				'ExchangeRate-API',
				'نرخ ارز بین‌المللی؛ پلن رایگان نیازمند کلید.',
				false,
				360,
				array_merge(
					$api_key,
					array( 'base' => array( 'label' => 'ارز مبنا', 'type' => 'text', 'default' => 'USD' ) )
				),
				'free-key'
			),
			'exir'              => self::definition( 'Exir', 'بازار عمومی رمزارز؛ بدون کلید.', true, 10, array(), 'public' ),
			'fixerio'           => self::definition(
				'Fixer',
				'نرخ ارز بین‌المللی؛ پلن رایگان نیازمند کلید.',
				false,
				360,
				array_merge(
					$api_key,
					array( 'base' => array( 'label' => 'ارز مبنا', 'type' => 'text', 'default' => 'EUR' ) )
				),
				'free-key'
			),
			'goldapiio'         => self::definition( 'GoldAPI', 'طلا، نقره، پلاتین و پالادیوم؛ پلن رایگان نیازمند کلید.', false, 30, $api_key, 'free-key' ),
			'hamyarandroid'     => self::definition( 'Hamyar Android', 'رابط قدیمی نرخ ایران؛ برای سازگاری با داده‌های مهاجرتی.', false, 30, array(), 'legacy' ),
			'metalpriceapicom'  => self::definition(
				'MetalpriceAPI',
				'فلزات و ارز؛ پلن رایگان نیازمند کلید.',
				false,
				60,
				array_merge(
					$api_key,
					array( 'base' => array( 'label' => 'ارز مبنا', 'type' => 'text', 'default' => 'USD' ) )
				),
				'free-key'
			),
			'metalsapicom'      => self::definition(
				'Metals-API',
				'فلزات گران‌بها؛ پلن رایگان نیازمند کلید.',
				false,
				60,
				array_merge(
					$api_key,
					array( 'base' => array( 'label' => 'ارز مبنا', 'type' => 'text', 'default' => 'USD' ) )
				),
				'free-key'
			),
			'metalslive'        => self::definition( 'Metals Live', 'رابط عمومی قدیمی فلزات؛ پیش‌فرض خاموش به‌دلیل نبود SLA رسمی.', false, 30, array(), 'legacy' ),
			'nerkhapi'          => self::definition( 'Nerkh-API', 'نرخ ارز و طلای ایران؛ نیازمند کلید.', false, 15, $api_key, 'paid' ),
			'nobitex'           => self::definition( 'Nobitex', 'بازار عمومی رمزارز به ریال؛ بدون کلید.', true, 10, array(), 'public' ),
			'okexio'            => self::definition( 'OK-EX.io', 'رابط قدیمی صرافی؛ برای سازگاری با داده‌های مهاجرتی.', false, 15, array(), 'legacy' ),
			'openexchangerates' => self::definition(
				'Open Exchange Rates',
				'نرخ ارز بین‌المللی؛ پلن رایگان نیازمند App ID.',
				false,
				360,
				array( 'api_key' => array( 'label' => 'App ID', 'type' => 'password', 'secret' => true, 'default' => '' ) ),
				'free-key'
			),
			'parsijoo'          => self::definition( 'Parsijoo', 'رابط قدیمی نرخ ایران؛ پیش‌فرض خاموش.', false, 30, array(), 'legacy' ),
			'persianapi'        => self::definition( 'Persian API / TGJU', 'نرخ‌های بازار ایران؛ نیازمند توکن سرویس.', false, 15, $token, 'paid' ),
			'ramzinex'          => self::definition( 'Ramzinex', 'بازار عمومی رمزارز به ریال؛ بدون کلید.', true, 10, array(), 'public' ),
			'ratesbox'          => self::definition( 'RatesBox', 'طلا، سکه و ارز ایران؛ نیازمند توکن.', false, 15, $token, 'paid' ),
			'sourcearena'       => self::definition( 'Sourcearena', 'نرخ ارز و طلا؛ نیازمند توکن.', false, 15, $token, 'paid' ),
			'sunnyweb'          => self::definition( 'SunnyWeb Old', 'نسخه قدیمی وب‌سرویس نرخ ایران؛ نیازمند توکن.', false, 15, $token, 'paid' ),
			'sunnywebnew'       => self::definition(
				'SunnyWeb New',
				'ارز، طلا، سکه و کالا؛ نیازمند توکن.',
				false,
				15,
				array_merge(
					$token,
					array( 'parameter' => array( 'label' => 'گروه داده', 'type' => 'text', 'default' => 'azad' ) )
				),
				'paid'
			),
			'sunnywebshabakeh'  => self::definition(
				'SunnyWeb Shabakeh',
				'نرخ شبکه صرافی‌ها؛ نیازمند توکن.',
				false,
				15,
				array_merge(
					$token,
					array( 'coin' => array( 'label' => 'ارز', 'type' => 'text', 'default' => 'usd' ) )
				),
				'paid'
			),
			'tabangohar'        => self::definition(
				'Taban Gohar',
				'نرخ طلای طلا‌فروشی؛ اتصال حساب‌محور.',
				false,
				15,
				array(
					'username' => array( 'label' => 'نام کاربری', 'type' => 'text', 'secret' => true, 'default' => '' ),
					'password' => array( 'label' => 'رمز عبور', 'type' => 'password', 'secret' => true, 'default' => '' ),
				),
				'paid'
			),
			'talaclinicfars'    => self::definition( 'Tala Clinic Fars', 'نرخ طلای ایران؛ نیازمند توکن.', false, 15, $token, 'paid' ),
			'talascrap'         => self::definition( 'Tala.ir Scraper', 'استخراج HTML قدیمی؛ برای پایداری و رعایت سهمیه پیش‌فرض خاموش است.', false, 30, array(), 'legacy' ),
			'tetherland'        => self::definition( 'TetherLand', 'نرخ عمومی تتر و دارایی‌های منتخب به تومان؛ بدون کلید.', true, 10, array(), 'public' ),
			'tgjulocal'         => self::definition(
				'TGJU Local',
				'اتصال محلی به خروجی TGJU Studio.',
				false,
				15,
				array( 'url' => array( 'label' => 'نشانی JSON', 'type' => 'url', 'default' => '' ) ),
				'paid'
			),
			'wallexir'          => self::definition( 'Wallex', 'بازارهای عمومی رمزارز به تومان؛ بدون کلید و با endpoint رسمی جدید.', true, 10, array(), 'public' ),
		);
	}

	/**
	 * Settings defaults consumed by the central Settings service.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function default_settings(): array {
		$defaults = array();
		foreach ( self::definitions() as $key => $definition ) {
			$defaults[ $key ] = array(
				'enabled'  => ! empty( $definition['enabled'] ) ? 'yes' : 'no',
				'interval' => (int) $definition['interval'],
			);
			foreach ( $definition['fields'] as $field => $schema ) {
				$defaults[ $key ][ $field ] = $schema['default'] ?? '';
			}
		}
		return $defaults;
	}

	/**
	 * Build a catalog definition.
	 *
	 * @param string                            $label Label.
	 * @param string                            $description Description.
	 * @param bool                              $enabled Default state.
	 * @param int                               $interval Refresh minutes.
	 * @param array<string, array<string,mixed>> $fields Config fields.
	 * @param string                            $tier public, free-key, paid or legacy.
	 * @return array<string, mixed>
	 */
	private static function definition( string $label, string $description, bool $enabled, int $interval, array $fields, string $tier ): array {
		return compact( 'label', 'description', 'enabled', 'interval', 'fields', 'tier' );
	}

	public function key(): string {
		return $this->provider_key;
	}

	public function label(): string {
		return (string) ( self::definitions()[ $this->provider_key ]['label'] ?? $this->provider_key );
	}

	public function description(): string {
		return (string) ( self::definitions()[ $this->provider_key ]['description'] ?? '' );
	}

	/**
	 * UI field schemas.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function fields(): array {
		return (array) ( self::definitions()[ $this->provider_key ]['fields'] ?? array() );
	}

	public function tier(): string {
		return (string) ( self::definitions()[ $this->provider_key ]['tier'] ?? 'paid' );
	}

	/**
	 * Fetch assigned provider keys.
	 *
	 * @param array<int, object> $assets Assets.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	public function fetch( array $assets ): array|\WP_Error {
		$keys = $this->asset_keys( $assets );
		if ( ! $keys ) {
			return array();
		}

		return match ( $this->provider_key ) {
			'arzpaya'           => $this->fetch_arzpaya(),
			'bitpin'            => $this->fetch_bitpin(),
			'exir'              => $this->fetch_exir(),
			'nobitex'           => $this->fetch_nobitex(),
			'ramzinex'          => $this->fetch_ramzinex(),
			'tetherland'        => $this->fetch_tetherland(),
			'wallexir'          => $this->fetch_wallex(),
			'abantether'        => $this->fetch_abantether(),
			'apimaster'         => $this->fetch_apimaster(),
			'coinmarketcap'     => $this->fetch_coinmarketcap( $keys ),
			'commoditiesapicom' => $this->fetch_commodities( $keys ),
			'cryptocompare'     => $this->fetch_cryptocompare( $keys ),
			'exchangerateapi'   => $this->fetch_exchange_rate_api(),
			'fixerio'           => $this->fetch_fixer( $keys ),
			'goldapiio'         => $this->fetch_goldapi( $keys ),
			'metalpriceapicom'  => $this->fetch_metalprice( $keys ),
			'metalsapicom'      => $this->fetch_metals_api( $keys ),
			'nerkhapi'          => $this->fetch_nerkh_api(),
			'openexchangerates' => $this->fetch_open_exchange( $keys ),
			'ratesbox'          => $this->fetch_ratesbox(),
			'sourcearena'       => $this->fetch_sourcearena(),
			'sunnyweb'          => $this->fetch_sunnyweb( 'old' ),
			'sunnywebnew'       => $this->fetch_sunnyweb( 'new' ),
			'sunnywebshabakeh'  => $this->fetch_sunnyweb( 'shabakeh' ),
			'tgjulocal'         => $this->fetch_tgju_local(),
			default             => new \WP_Error(
				'mrnpe_legacy_provider',
				__( 'endpoint این اتصال در نسخه نمونه منسوخ یا مبتنی بر scraping ناامن است؛ منبع فقط برای مهاجرت نگه داشته شده و پیش‌فرض خاموش است.', 'mrn-price-engine' )
			),
		};
	}

	/** @return array<string, array<string, mixed>>|\WP_Error */
	private function fetch_wallex(): array|\WP_Error {
		$response = $this->get_json( 'https://api.wallex.ir/hector/web/v1/markets' );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$markets = $response['result']['markets'] ?? null;
		if ( ! is_array( $markets ) ) {
			return $this->invalid_response();
		}

		$data = array();
		foreach ( $markets as $market ) {
			if ( empty( $market['symbol'] ) || ! is_numeric( $market['price'] ?? null ) ) {
				continue;
			}
			$data[ (string) $market['symbol'] ] = $this->rate_item(
				(float) $market['price'],
				(string) ( $market['fa_base_asset'] ?? $market['base_asset'] ?? $market['symbol'] ),
				(float) ( $market['change_24h'] ?? 0 )
			);
		}
		return $data;
	}

	/** @return array<string, array<string, mixed>>|\WP_Error */
	private function fetch_nobitex(): array|\WP_Error {
		$response = $this->get_json( 'https://apiv2.nobitex.ir/market/stats?dstCurrency=rls' );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( 'ok' !== ( $response['status'] ?? '' ) || ! is_array( $response['stats'] ?? null ) ) {
			return $this->invalid_response( (string) ( $response['message'] ?? '' ) );
		}

		$data = array();
		foreach ( $response['stats'] as $key => $market ) {
			if ( ! is_numeric( $market['latest'] ?? null ) ) {
				continue;
			}
			$data[ (string) $key ] = $this->rate_item( (float) $market['latest'], strtoupper( (string) $key ), (float) ( $market['dayChange'] ?? 0 ) );
		}
		return $data;
	}

	/** @return array<string, array<string, mixed>>|\WP_Error */
	private function fetch_bitpin(): array|\WP_Error {
		$response = $this->get_json( 'https://api.bitpin.ir/v1/mkt/markets/' );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( ! is_array( $response['results'] ?? null ) ) {
			return $this->invalid_response();
		}

		$data = array();
		foreach ( $response['results'] as $market ) {
			if ( empty( $market['code'] ) || ! is_numeric( $market['price'] ?? null ) ) {
				continue;
			}
			$data[ (string) $market['code'] ] = $this->rate_item(
				(float) $market['price'],
				(string) ( $market['title_fa'] ?? $market['title'] ?? $market['code'] ),
				(float) ( $market['price_info']['change'] ?? 0 )
			);
		}
		return $data;
	}

	/** @return array<string, array<string, mixed>>|\WP_Error */
	private function fetch_tetherland(): array|\WP_Error {
		$response = $this->get_json( 'https://api.tetherland.com/currencies' );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$currencies = $response['data']['currencies'] ?? null;
		if ( 200 !== (int) ( $response['status'] ?? 0 ) || ! is_array( $currencies ) ) {
			return $this->invalid_response();
		}

		$data = array();
		foreach ( $currencies as $key => $currency ) {
			if ( ! is_numeric( $currency['price'] ?? null ) ) {
				continue;
			}
			$data[ (string) $key ] = $this->rate_item( (float) $currency['price'], strtoupper( (string) $key ), (float) ( $currency['diff24d'] ?? 0 ) );
		}
		return $data;
	}

	/** @return array<string, array<string, mixed>>|\WP_Error */
	private function fetch_exir(): array|\WP_Error {
		$response = $this->get_json( 'https://api.exir.io/v2/tickers' );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = array();
		foreach ( $response as $key => $market ) {
			if ( ! is_array( $market ) || ! is_numeric( $market['last'] ?? null ) ) {
				continue;
			}
			$data[ (string) $key ] = $this->rate_item( (float) $market['last'], strtoupper( (string) $key ) );
		}
		return $data ?: $this->invalid_response();
	}

	/** @return array<string, array<string, mixed>>|\WP_Error */
	private function fetch_ramzinex(): array|\WP_Error {
		$response = $this->get_json( 'https://publicapi.ramzinex.com/exchange/api/v1.0/exchange/prices' );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( ! is_array( $response['data'] ?? null ) ) {
			return $this->invalid_response();
		}

		$data = array();
		foreach ( $response['data'] as $key => $market ) {
			$name = (string) ( $market['base_currency_symbol']['fa'] ?? $market['base_currency_symbol']['en'] ?? $key );
			$buy  = is_numeric( $market['buy'] ?? null ) ? (float) $market['buy'] : 0.0;
			$sell = is_numeric( $market['sell'] ?? null ) ? (float) $market['sell'] : 0.0;
			if ( $buy > 0 ) {
				$data[ (string) $key . '_buy' ] = $this->rate_item( $buy, $name . ' - خرید' );
			}
			if ( $sell > 0 ) {
				$data[ (string) $key . '_sell' ] = $this->rate_item( $sell, $name . ' - فروش' );
			}
			if ( $buy > 0 || $sell > 0 ) {
				$data[ (string) $key ] = $this->rate_item( $buy > 0 ? $buy : $sell, $name );
			}
		}
		return $data;
	}

	/** @return array<string, array<string, mixed>>|\WP_Error */
	private function fetch_arzpaya(): array|\WP_Error {
		$response = $this->get_json( 'https://api.arzpaya.com/public/ExchangeInfo' );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( ! is_array( $response['Data'] ?? null ) ) {
			return $this->invalid_response();
		}

		$data = array();
		foreach ( $response['Data'] as $market ) {
			if ( ! is_numeric( $market['LastPrice'] ?? null ) || ! isset( $market['ExchangeType'] ) ) {
				continue;
			}
			$key          = (string) $market['ExchangeType'];
			$data[ $key ] = $this->rate_item( (float) $market['LastPrice'], (string) ( $market['Symbol'] ?? $key ) );
		}
		return $data;
	}

	/** @return array<string, array<string, mixed>>|\WP_Error */
	private function fetch_abantether(): array|\WP_Error {
		$key = $this->required( 'api_key' );
		if ( is_wp_error( $key ) ) {
			return $key;
		}
		$response = $this->get_json(
			'https://abantether.com/api/v1/otc/coin-price/?format=json',
			array( 'Authorization' => 'Token ' . $key )
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = array();
		foreach ( $response as $symbol => $item ) {
			if ( is_array( $item ) && is_numeric( $item['irtPriceBuy'] ?? null ) ) {
				$data[ (string) $symbol ] = $this->rate_item( (float) $item['irtPriceBuy'], strtoupper( (string) $symbol ) );
			}
		}
		return $data ?: $this->invalid_response( (string) ( $response['message'] ?? '' ) );
	}

	/** @return array<string, array<string, mixed>>|\WP_Error */
	private function fetch_apimaster(): array|\WP_Error {
		$key = $this->required( 'api_key' );
		if ( is_wp_error( $key ) ) {
			return $key;
		}
		$response = $this->get_json( 'https://api.apimaster.ir/nerkh/v2/list', array( 'Authorization' => $key ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = array();
		foreach ( (array) ( $response['data'] ?? array() ) as $item ) {
			if ( ! empty( $item['slug'] ) && is_numeric( $item['price'] ?? null ) ) {
				$data[ (string) $item['slug'] ] = $this->rate_item( (float) $item['price'], (string) ( $item['name'] ?? $item['slug'] ) );
			}
		}
		return $data ?: $this->invalid_response( (string) ( $response['message'] ?? '' ) );
	}

	/**
	 * @param array<int, string> $keys Symbols.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	private function fetch_coinmarketcap( array $keys ): array|\WP_Error {
		$key = $this->required( 'api_key' );
		if ( is_wp_error( $key ) ) {
			return $key;
		}
		$url      = add_query_arg( array( 'symbol' => implode( ',', $keys ), 'skip_invalid' => 'true' ), 'https://pro-api.coinmarketcap.com/v2/cryptocurrency/quotes/latest' );
		$response = $this->get_json( $url, array( 'X-CMC_PRO_API_KEY' => $key ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = array();
		foreach ( (array) ( $response['data'] ?? array() ) as $symbol => $items ) {
			$item  = is_array( $items ) && isset( $items[0] ) ? $items[0] : $items;
			$quote = is_array( $item ) ? ( $item['quote']['USD'] ?? null ) : null;
			if ( is_array( $quote ) && is_numeric( $quote['price'] ?? null ) ) {
				$data[ (string) $symbol ] = $this->rate_item( (float) $quote['price'], (string) ( $item['name'] ?? $symbol ), (float) ( $quote['percent_change_24h'] ?? 0 ) );
			}
		}
		return $data ?: $this->invalid_response( (string) ( $response['status']['error_message'] ?? '' ) );
	}

	/**
	 * @param array<int, string> $keys Symbols.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	private function fetch_cryptocompare( array $keys ): array|\WP_Error {
		$config = Settings::provider( $this->key() );
		$quote  = strtoupper( (string) ( $config['quote'] ?? 'USD' ) );
		$url    = add_query_arg(
			array(
				'fsyms'       => implode( ',', $keys ),
				'tsyms'       => $quote,
				'extraParams' => 'MRNPriceEngine',
			),
			'https://min-api.cryptocompare.com/data/pricemulti'
		);
		$headers = empty( $config['api_key'] ) ? array() : array( 'authorization' => 'Apikey ' . $config['api_key'] );
		$response = $this->get_json( $url, $headers );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = array();
		foreach ( $response as $symbol => $quotes ) {
			if ( is_array( $quotes ) && is_numeric( $quotes[ $quote ] ?? null ) ) {
				$data[ (string) $symbol ] = $this->rate_item( (float) $quotes[ $quote ], (string) $symbol );
			}
		}
		return $data ?: $this->invalid_response( (string) ( $response['Message'] ?? '' ) );
	}

	/** @return array<string, array<string, mixed>>|\WP_Error */
	private function fetch_exchange_rate_api(): array|\WP_Error {
		$key = $this->required( 'api_key' );
		if ( is_wp_error( $key ) ) {
			return $key;
		}
		$config   = Settings::provider( $this->key() );
		$base     = strtoupper( (string) ( $config['base'] ?? 'USD' ) );
		$response = $this->get_json( 'https://v6.exchangerate-api.com/v6/' . rawurlencode( $key ) . '/latest/' . rawurlencode( $base ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		return $this->flat_rates( (array) ( $response['conversion_rates'] ?? array() ) );
	}

	/**
	 * @param array<int, string> $keys Symbols.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	private function fetch_fixer( array $keys ): array|\WP_Error {
		$key = $this->required( 'api_key' );
		if ( is_wp_error( $key ) ) {
			return $key;
		}
		$config = Settings::provider( $this->key() );
		$url    = add_query_arg(
			array(
				'base'    => strtoupper( (string) ( $config['base'] ?? 'EUR' ) ),
				'symbols' => implode( ',', $keys ),
			),
			'https://api.apilayer.com/fixer/latest'
		);
		$response = $this->get_json( $url, array( 'apikey' => $key ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		return $this->flat_rates( (array) ( $response['rates'] ?? array() ) );
	}

	/**
	 * @param array<int, string> $keys Symbols.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	private function fetch_commodities( array $keys ): array|\WP_Error {
		$key = $this->required( 'api_key' );
		if ( is_wp_error( $key ) ) {
			return $key;
		}
		$url      = add_query_arg( array( 'access_key' => $key, 'base' => 'USD', 'symbols' => implode( ',', $keys ) ), 'https://commodities-api.com/api/latest' );
		$response = $this->get_json( $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$rates = array();
		foreach ( (array) ( $response['rates'] ?? array() ) as $symbol => $rate ) {
			if ( is_numeric( $rate ) && (float) $rate > 0 ) {
				$rates[ $symbol ] = 1 / (float) $rate;
			}
		}
		return $this->flat_rates( $rates );
	}

	/**
	 * @param array<int, string> $keys Symbols.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	private function fetch_metalprice( array $keys ): array|\WP_Error {
		$key = $this->required( 'api_key' );
		if ( is_wp_error( $key ) ) {
			return $key;
		}
		$config = Settings::provider( $this->key() );
		$url    = add_query_arg(
			array(
				'api_key'    => $key,
				'base'       => strtoupper( (string) ( $config['base'] ?? 'USD' ) ),
				'currencies' => implode( ',', $keys ),
			),
			'https://api.metalpriceapi.com/v1/latest'
		);
		$response = $this->get_json( $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		return $this->flat_rates( (array) ( $response['rates'] ?? array() ) );
	}

	/**
	 * @param array<int, string> $keys Symbols.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	private function fetch_metals_api( array $keys ): array|\WP_Error {
		$key = $this->required( 'api_key' );
		if ( is_wp_error( $key ) ) {
			return $key;
		}
		$config = Settings::provider( $this->key() );
		$url    = add_query_arg(
			array(
				'access_key' => $key,
				'base'       => strtoupper( (string) ( $config['base'] ?? 'USD' ) ),
				'symbols'    => implode( ',', $keys ),
			),
			'https://metals-api.com/api/latest'
		);
		$response = $this->get_json( $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$rates = array();
		foreach ( (array) ( $response['rates'] ?? array() ) as $symbol => $rate ) {
			if ( is_numeric( $rate ) && (float) $rate > 0 ) {
				$rates[ $symbol ] = 1 / (float) $rate;
			}
		}
		return $this->flat_rates( $rates );
	}

	/**
	 * GoldAPI uses one endpoint per metal; only assigned metals are requested.
	 *
	 * @param array<int, string> $keys Provider keys.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	private function fetch_goldapi( array $keys ): array|\WP_Error {
		$key = $this->required( 'api_key' );
		if ( is_wp_error( $key ) ) {
			return $key;
		}
		$data   = array();
		$metals = array_unique( array_map( static fn( string $item ): string => strtoupper( strtok( $item, '_' ) ), $keys ) );
		foreach ( $metals as $metal ) {
			if ( ! in_array( $metal, array( 'XAU', 'XAG', 'XPT', 'XPD' ), true ) ) {
				continue;
			}
			$response = $this->get_json( 'https://www.goldapi.io/api/' . $metal . '/USD', array( 'x-access-token' => $key ) );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			$map = array(
				$metal             => 'price',
				$metal . '_GRAM_24K' => 'price_gram_24k',
				$metal . '_GRAM_22K' => 'price_gram_22k',
				$metal . '_GRAM_21K' => 'price_gram_21k',
				$metal . '_GRAM_20K' => 'price_gram_20k',
				$metal . '_GRAM_18K' => 'price_gram_18k',
			);
			foreach ( $map as $provider_key => $field ) {
				if ( is_numeric( $response[ $field ] ?? null ) ) {
					$data[ $provider_key ] = $this->rate_item( (float) $response[ $field ], $provider_key );
				}
			}
		}
		return $data ?: $this->invalid_response();
	}

	/**
	 * @param array<int, string> $keys Symbols.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	private function fetch_open_exchange( array $keys ): array|\WP_Error {
		$key = $this->required( 'api_key' );
		if ( is_wp_error( $key ) ) {
			return $key;
		}
		$url      = add_query_arg( array( 'app_id' => $key, 'symbols' => implode( ',', $keys ) ), 'https://openexchangerates.org/api/latest.json' );
		$response = $this->get_json( $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		return $this->flat_rates( (array) ( $response['rates'] ?? array() ) );
	}

	/** @return array<string, array<string, mixed>>|\WP_Error */
	private function fetch_nerkh_api(): array|\WP_Error {
		$key = $this->required( 'api_key' );
		if ( is_wp_error( $key ) ) {
			return $key;
		}
		$data = array();
		foreach ( array( 'currency', 'gold' ) as $endpoint ) {
			$response = $this->get_json( 'https://nerkh-api.ir/api/' . rawurlencode( $key ) . '/' . $endpoint . '/' );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			foreach ( (array) ( $response['prices'] ?? $response['data']['prices'] ?? array() ) as $symbol => $item ) {
				$value = is_array( $item ) ? ( $item['current'] ?? null ) : $item;
				if ( is_numeric( $value ) ) {
					$data[ (string) $symbol ] = $this->rate_item( (float) $value, (string) $symbol );
				}
			}
		}
		return $data ?: $this->invalid_response();
	}

	/** @return array<string, array<string, mixed>>|\WP_Error */
	private function fetch_ratesbox(): array|\WP_Error {
		$token = $this->required( 'token' );
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		$response = $this->get_json( add_query_arg( 'token', $token, 'https://api.ratebox.ir/apijson.php' ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		return $this->normalize_items( $response['data'] ?? $response, 'id', 'price', 'name' );
	}

	/** @return array<string, array<string, mixed>>|\WP_Error */
	private function fetch_sourcearena(): array|\WP_Error {
		$token = $this->required( 'token' );
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		$response = $this->get_json( add_query_arg( 'token', $token, 'https://sourcearena.ir/api/' ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		return $this->normalize_items( $response['data'] ?? $response, 'slug', 'price', 'name' );
	}

	/**
	 * @param string $variant old, new or shabakeh.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	private function fetch_sunnyweb( string $variant ): array|\WP_Error {
		$token = $this->required( 'token' );
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		$config = Settings::provider( $this->key() );
		$url    = match ( $variant ) {
			'new'       => 'https://api.sunnyweb.ir/api/new/' . rawurlencode( $token ) . '/' . rawurlencode( (string) ( $config['parameter'] ?? 'azad' ) ),
			'shabakeh'  => 'https://api.sunnyweb.ir/api/shabakeh/' . rawurlencode( $token ) . '/' . rawurlencode( (string) ( $config['coin'] ?? 'usd' ) ),
			default     => 'https://api.sunnyweb.ir/api/' . rawurlencode( $token ),
		};
		$response = $this->get_json( $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		return $this->normalize_items( $response, 'name', 'price', 'persianName' );
	}

	/** @return array<string, array<string, mixed>>|\WP_Error */
	private function fetch_tgju_local(): array|\WP_Error {
		$config = Settings::provider( $this->key() );
		$url    = esc_url_raw( (string) ( $config['url'] ?? '' ), array( 'https' ) );
		if ( ! $url ) {
			return new \WP_Error( 'mrnpe_provider_config', __( 'نشانی HTTPS خروجی TGJU وارد نشده است.', 'mrn-price-engine' ) );
		}
		$response = $this->get_json( $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		return $this->normalize_items( $response['data'] ?? $response, 'symbol', 'price', 'name' );
	}

	/**
	 * Normalize a list of generic objects.
	 *
	 * @param mixed  $items Items.
	 * @param string $key_field Key field.
	 * @param string $value_field Value field.
	 * @param string $name_field Name field.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	private function normalize_items( mixed $items, string $key_field, string $value_field, string $name_field ): array|\WP_Error {
		if ( ! is_array( $items ) ) {
			return $this->invalid_response();
		}
		$data = array();
		foreach ( $items as $fallback => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$key   = (string) ( $item[ $key_field ] ?? $fallback );
			$value = $item[ $value_field ] ?? $item['current'] ?? $item['value'] ?? null;
			if ( '' !== $key && is_numeric( $value ) ) {
				$data[ $key ] = $this->rate_item( (float) $value, (string) ( $item[ $name_field ] ?? $key ) );
			}
		}
		return $data ?: $this->invalid_response();
	}

	/**
	 * Normalize a flat symbol => numeric map.
	 *
	 * @param array<mixed> $rates Rates.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	private function flat_rates( array $rates ): array|\WP_Error {
		$data = array();
		foreach ( $rates as $symbol => $value ) {
			if ( is_numeric( $value ) && (float) $value > 0 ) {
				$data[ (string) $symbol ] = $this->rate_item( (float) $value, (string) $symbol );
			}
		}
		return $data ?: $this->invalid_response();
	}

	/**
	 * Rate payload understood by Provider_Manager.
	 *
	 * @param float  $value Value.
	 * @param string $name Label.
	 * @param float  $change Optional change.
	 * @return array<string, mixed>
	 */
	private function rate_item( float $value, string $name, float $change = 0.0 ): array {
		return array(
			'value'          => $value,
			'name'           => sanitize_text_field( $name ),
			'change_percent' => $change,
			'fetched_at'     => time(),
		);
	}

	/**
	 * Read a required provider option.
	 *
	 * @param string $field Field.
	 * @return string|\WP_Error
	 */
	private function required( string $field ): string|\WP_Error {
		$config = Settings::provider( $this->key() );
		$value  = trim( (string) ( $config[ $field ] ?? '' ) );
		if ( '' === $value ) {
			return new \WP_Error(
				'mrnpe_provider_config',
				sprintf(
					/* translators: %s: provider label. */
					__( 'اطلاعات دسترسی منبع %s کامل نیست.', 'mrn-price-engine' ),
					$this->label()
				)
			);
		}
		return $value;
	}

	/**
	 * Consistent invalid response error.
	 *
	 * @param string $message Optional upstream message.
	 * @return \WP_Error
	 */
	private function invalid_response( string $message = '' ): \WP_Error {
		return new \WP_Error(
			'mrnpe_provider_response',
			$message ?: sprintf(
				/* translators: %s: provider label. */
				__( 'ساختار پاسخ %s معتبر یا سازگار نیست.', 'mrn-price-engine' ),
				$this->label()
			)
		);
	}
}
