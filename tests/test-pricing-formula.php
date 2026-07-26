<?php
/**
 * Zero-dependency formula regression tests.
 */

declare(strict_types=1);

require_once dirname( __DIR__ ) . '/includes/class-pricing-formula.php';

use MRN\Price_Engine\Pricing_Formula;

function assert_close( float $expected, float $actual, string $message ): void {
	if ( abs( $expected - $actual ) > 0.000001 ) {
		fwrite( STDERR, "FAIL: {$message}; expected {$expected}, got {$actual}\n" );
		exit( 1 );
	}
}

$gold = Pricing_Formula::calculate(
	4_500_000,
	array(
		'quantity'        => 1.43,
		'multiplier'      => 1,
		'percentage'      => 7,
		'fixed'           => 250_000,
		'rounding_step'   => 1_000,
		'rounding_mode'   => 'nearest',
		'sale_enabled'    => true,
		'sale_percentage' => 3,
		'sale_fixed'      => 50_000,
	)
);

assert_close( 6_435_000, $gold['material'], 'gold material price' );
assert_close( 7_135_000, $gold['regular'], 'gold regular price rounded' );
assert_close( 6_871_000, (float) $gold['sale'], 'gold sale price rounded' );

assert_close( 1_300, Pricing_Formula::round( 1_251, 100, 'nearest' ), 'nearest rounding' );
assert_close( 1_300, Pricing_Formula::round( 1_201, 100, 'up' ), 'up rounding' );
assert_close( 1_200, Pricing_Formula::round( 1_299, 100, 'down' ), 'down rounding' );

$invalid_sale = Pricing_Formula::calculate(
	100,
	array(
		'quantity'        => 1,
		'multiplier'      => 1,
		'sale_enabled'    => true,
		'sale_percentage' => 0,
	)
);

if ( null !== $invalid_sale['sale'] ) {
	fwrite( STDERR, "FAIL: sale equal to regular must be null\n" );
	exit( 1 );
}

fwrite( STDOUT, "Pricing formula tests passed.\n" );
