<?php
/**
 * Lightweight plugin autoloader.
 *
 * @package MRN_Price_Engine
 */

namespace MRNPE\Price_Engine;

defined( 'ABSPATH' ) || exit;

final class Autoloader {
	/**
	 * Register the loader.
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register( array( self::class, 'load' ) );
	}

	/**
	 * Load a namespaced class from includes.
	 *
	 * @param string $class Class name.
	 * @return void
	 */
	public static function load( string $class ): void {
		$prefix = __NAMESPACE__ . '\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$parts    = explode( '\\', $relative );
		$class    = array_pop( $parts );
		$filename = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
		$path     = MRNPE_PATH . 'includes/';

		if ( $parts ) {
			$path .= strtolower( implode( '/', $parts ) ) . '/';
		}

		$file = $path . $filename;

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
