<?php
/**
 * Plugin Name: GaitBeacon Shipping Rules
 * Plugin URI: https://gaitbeacon.com
 * Description: Conditionally hides WooCommerce shipping methods based on product shipping class in cart. Configure rules via admin settings to control which shipping methods are available for each shipping class.
 * Version: 2.0.0
 * Author: Ronald Allan Rivera
 * Author URI: https://gaitbeacon.com
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gaitbeacon-shipping-rules
 *
 * @package GaitBeacon_Shipping_Rules
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'GB_PLUGIN_VERSION', '2.0.0' );
define( 'GB_PLUGIN_FILE', __FILE__ );
define( 'GB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Check if WooCommerce is active.
 * 
 * This plugin requires WooCommerce to function. If WooCommerce is not active,
 * registers an admin notice hook and returns false.
 * 
 * @return bool True if WooCommerce is active, false otherwise.
 */
function gb_check_woocommerce_active() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'gb_woocommerce_missing_notice' );
		return false;
	}
	return true;
}

/**
 * Display admin notice when WooCommerce is not active.
 * 
 * Shows a dismissible error message in the WordPress admin area informing
 * administrators that WooCommerce must be installed and activated.
 */
function gb_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error is-dismissible">
		<p>
			<strong><?php esc_html_e( 'GaitBeacon Shipping Rules', 'gaitbeacon-shipping-rules' ); ?></strong>
			<?php esc_html_e( ' requires WooCommerce to be installed and activated.', 'gaitbeacon-shipping-rules' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Initialize the plugin functionality.
 * 
 * Runs on 'plugins_loaded' hook after WooCommerce is loaded.
 * Only registers hooks if WooCommerce is active.
 * 
 * Loads required classes and initializes functionality:
 * - GB_Helpers: Utility functions for shipping classes, methods, and settings
 * - GB_Filter: Core filtering logic for cart/checkout pages
 * - GB_Admin: Admin interface and settings page
 */
function gb_init_shipping_rules() {
	if ( ! gb_check_woocommerce_active() ) {
		return;
	}
	
	// Load required classes
	require_once GB_PLUGIN_DIR . 'includes/class-gb-helpers.php';
	require_once GB_PLUGIN_DIR . 'includes/class-gb-filter.php';
	require_once GB_PLUGIN_DIR . 'includes/class-gb-admin.php';
	
	// Initialize filter (frontend functionality)
	GB_Filter::init();
	
	// Initialize admin (backend functionality)
	if ( is_admin() ) {
		GB_Admin::init();
	}
}

// Initialize plugin on plugins_loaded hook to ensure WooCommerce is loaded first
add_action( 'plugins_loaded', 'gb_init_shipping_rules' );
