<?php
/**
 * Plugin Name: Content Republish
 * Plugin URI: https://contentimizer.com/content-republish
 * Description: Keep your live posts fresh without having to unpublish it. Clone your posts, make changes to it and republish seamlessly.
 * Version: 1.1.3
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: YiPresser
 * Author URI: https://www.contentimizer.com
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: content-republish
 *
 * @package Content Republish
 */

use Yipresser\ContentRepublish\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'CONTENTREPUBLISH_PATH' ) ) {
	define( 'CONTENTREPUBLISH_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'CONTENTREPUBLISH_URI' ) ) {
	//define( 'CONTENTREPUBLISH_URI', plugin_dir_url( __FILE__) );
	define( 'CONTENTREPUBLISH_URI', plugins_url() . '/content-republish/' ); // to remove for production use
}

if ( ! defined( 'CONTENTREPUBLISH_PLUGIN_BASENAME' ) ) {
	define( 'CONTENTREPUBLISH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

const CONTENTREPUBLISH_VERSION = '1.1.3';

/**
 * @return void'
 */
function contentrepublish_deactivate() {
	if ( as_has_scheduled_action( 'contentrepublish_cleanup' ) ) {
		as_unschedule_all_actions( 'contentrepublish_cleanup' );
	}
	if ( as_has_scheduled_action( 'content_republish_post' ) ) {
		as_unschedule_all_actions( 'content_republish_post' );
	}
}
register_deactivation_hook( __FILE__, 'contentrepublish_deactivate' );


$as_file = CONTENTREPUBLISH_PATH . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
if ( is_readable( $as_file ) ) {
	require_once $as_file;
}

if ( ! class_exists( Bootstrap::class ) ) {
	require_once 'includes/bootstrap.php';
}
add_action( 'plugins_loaded', function() { Bootstrap::get_instance(); } );