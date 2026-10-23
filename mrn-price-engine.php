<?php
/**
 * Plugin Name:       MRN Gold & Currency Pricing for WooCommerce
 * Plugin URI:        https://mrnwp.ir/mrnnerkh
 * Description:       Gold, coin and US dollar exchange rates for Iranian websites, with Persian rate boards and automatic WooCommerce product pricing.
 * Version:           1.4.1
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            Mehran Marandi
 * Author URI:        https://mehranmarandi.ir
 * Text Domain:       mrn-gold-currency-pricing
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package MRN_Price_Engine
 */

defined( 'ABSPATH' ) || exit;

define( 'MRNPE_VERSION', '1.4.1' );
define( 'MRNPE_FILE', __FILE__ );
define( 'MRNPE_PATH', plugin_dir_path( __FILE__ ) );
define( 'MRNPE_URL', plugin_dir_url( __FILE__ ) );

require_once MRNPE_PATH . 'includes/class-autoloader.php';

\MRNPE\Price_Engine\Autoloader::register();

register_activation_hook( __FILE__, array( \MRNPE\Price_Engine\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \MRNPE\Price_Engine\Activator::class, 'deactivate' ) );

/**
 * Return the single plugin instance.
 *
 * @return \MRNPE\Price_Engine\Plugin
 */
function mrnpe_plugin(): \MRNPE\Price_Engine\Plugin {
	return \MRNPE\Price_Engine\Plugin::instance();
}

add_action( 'plugins_loaded', 'mrnpe_plugin', 5 );
