<?php
/**
 * Plugin Name:       MRN Price Engine
 * Plugin URI:        https://github.com/mehran-mrn/wp-price-engine
 * Description:       موتور حرفه‌ای نرخ و قیمت‌گذاری پویا برای طلا، ارز، رمزارز و محصولات ووکامرس.
 * Version:           1.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            MRN
 * Author URI:        https://mrn.dev/
 * Text Domain:       mrn-price-engine
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 *
 * @package MRN_Price_Engine
 */

defined( 'ABSPATH' ) || exit;

define( 'MRNPE_VERSION', '1.1.0' );
define( 'MRNPE_FILE', __FILE__ );
define( 'MRNPE_PATH', plugin_dir_path( __FILE__ ) );
define( 'MRNPE_URL', plugin_dir_url( __FILE__ ) );

require_once MRNPE_PATH . 'includes/class-autoloader.php';

\MRN\Price_Engine\Autoloader::register();

register_activation_hook( __FILE__, array( \MRN\Price_Engine\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \MRN\Price_Engine\Activator::class, 'deactivate' ) );

/**
 * Return the single plugin instance.
 *
 * @return \MRN\Price_Engine\Plugin
 */
function mrn_price_engine(): \MRN\Price_Engine\Plugin {
	return \MRN\Price_Engine\Plugin::instance();
}

add_action( 'plugins_loaded', 'mrn_price_engine', 5 );
