<?php
/**
 * Pure dynamic product price formula.
 *
 * @package MRN_Price_Engine
 */

namespace MRNPE\Price_Engine;

final class Pricing_Formula {
	/**
	 * Calculate regular and optional sale prices.
	 *
	 * Formula:
	 * material = rate × quantity × multiplier
	 * regular  = material + (material × percentage / 100) + fixed
	 * sale     = regular - (regular × sale_percentage / 100) - sale_fixed
	 *
	 * @param float                $rate Local asset rate.
	 * @param array<string, mixed> $config Product configuration.
	 * @return array<string, float|null>
	 */
	public static function calculate( float $rate, array $config ): array {
		$quantity   = max( 0, (float) ( $config['quantity'] ?? 1 ) );
		$multiplier = max( 0, (float) ( $config['multiplier'] ?? 1 ) );
		$percentage = (float) ( $config['percentage'] ?? 0 );
		$fixed      = (float) ( $config['fixed'] ?? 0 );
		$material   = $rate * $quantity * $multiplier;
		$markup     = $material * $percentage / 100;
		$regular    = max( 0, $material + $markup + $fixed );
		$step       = max( 0, (float) ( $config['rounding_step'] ?? 0 ) );
		$mode       = (string) ( $config['rounding_mode'] ?? 'nearest' );
		$regular    = self::round( $regular, $step, $mode );
		$sale       = null;

		if ( ! empty( $config['sale_enabled'] ) ) {
			$sale_percentage = max( 0, min( 100, (float) ( $config['sale_percentage'] ?? 0 ) ) );
			$sale_fixed      = max( 0, (float) ( $config['sale_fixed'] ?? 0 ) );
			$candidate       = $regular - ( $regular * $sale_percentage / 100 ) - $sale_fixed;
			$candidate       = self::round( max( 0, $candidate ), $step, $mode );

			if ( $candidate > 0 && $candidate < $regular ) {
				$sale = $candidate;
			}
		}

		return array(
			'rate'       => $rate,
			'quantity'   => $quantity,
			'multiplier' => $multiplier,
			'material'   => $material,
			'markup'     => $markup,
			'fixed'      => $fixed,
			'regular'    => $regular,
			'sale'       => $sale,
			'current'    => $sale ?? $regular,
		);
	}

	/**
	 * Round to a merchant-selected price step.
	 *
	 * @param float  $value Value to round.
	 * @param float  $step Price step. Zero disables step rounding.
	 * @param string $mode nearest, up or down.
	 * @return float
	 */
	public static function round( float $value, float $step, string $mode ): float {
		if ( $step <= 0 ) {
			return round( $value, 6 );
		}

		$scaled = $value / $step;
		$scaled = match ( $mode ) {
			'up'   => ceil( $scaled ),
			'down' => floor( $scaled ),
			default => round( $scaled ),
		};

		return $scaled * $step;
	}
}
