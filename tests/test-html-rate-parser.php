<?php
/**
 * Zero-network HTML rate parser regression tests.
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );

require_once dirname( __DIR__ ) . '/includes/class-html-rate-parser.php';

use MRN\Price_Engine\HTML_Rate_Parser;

function assert_rate( float $expected, ?float $actual, string $message ): void {
	if ( null === $actual || abs( $expected - $actual ) > 0.000001 ) {
		fwrite( STDERR, "FAIL: {$message}; expected {$expected}, got " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

assert_rate( 18_215_566, HTML_Rate_Parser::number( '۱۸٬۲۱۵٬۵۶۶' ), 'Persian digits' );
assert_rate( 1_865_000, HTML_Rate_Parser::number( '1,865,000' ), 'Latin separators' );

$milli = <<<'HTML'
<main>
	<p>قیمت ۱ گرم طلای ۱۸ عیار میلی</p>
	<p>181,430,000 ریال</p>
</main>
HTML;
assert_rate( 181_430_000, HTML_Rate_Parser::milli( $milli ), 'Milli widget rate' );

$melligold = <<<'HTML'
<div>
	<div><span>آخرین قیمت</span></div>
	<div><span>۱۸٬۲۱۵٬۵۶۶</span><span>تومان</span></div>
</div>
HTML;
assert_rate( 18_215_566, HTML_Rate_Parser::melligold( $melligold ), 'MelliGold last price' );

$tgju = <<<'HTML'
<table>
	<tr data-market-row="geram18" data-price="178,789,000">
		<th>طلای 18 عیار</th>
		<td class="market-price">178,789,000</td>
	</tr>
	<tr data-market-row="price_eur" data-price="2,125,000">
		<th>یورو</th>
		<td class="market-price">2,125,000</td>
	</tr>
</table>
HTML;
$rates = HTML_Rate_Parser::tgju( $tgju );

assert_rate( 178_789_000, $rates['geram18']['value'] ?? null, 'TGJU gold rate' );
assert_rate( 2_125_000, $rates['price_eur']['value'] ?? null, 'TGJU currency rate' );

if ( 'یورو' !== ( $rates['price_eur']['name'] ?? '' ) ) {
	fwrite( STDERR, "FAIL: TGJU title parsing\n" );
	exit( 1 );
}

fwrite( STDOUT, "HTML rate parser tests passed.\n" );
