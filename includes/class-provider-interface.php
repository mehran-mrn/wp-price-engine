<?php
/**
 * Rate provider contract.
 *
 * @package MRN_Price_Engine
 */

namespace MRNPE\Price_Engine;

defined( 'ABSPATH' ) || exit;

interface Provider_Interface {
	public function key(): string;

	public function label(): string;

	/**
	 * Fetch every requested provider key in as few external calls as possible.
	 *
	 * @param array<int, object> $assets Assets assigned to this provider.
	 * @return array<string, array<string, mixed>>|\WP_Error
	 */
	public function fetch( array $assets ): array|\WP_Error;
}
