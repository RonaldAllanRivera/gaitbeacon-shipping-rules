<?php
/**
 * Helper Functions Class
 *
 * Provides utility functions for retrieving shipping classes, methods, and settings.
 *
 * @package GaitBeacon_Shipping_Rules
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper functions for GaitBeacon Shipping Rules.
 */
class GB_Helpers {

	/**
	 * Get all available shipping classes.
	 * 
	 * Retrieves all shipping classes defined in WooCommerce, including
	 * an option for products with no shipping class assigned.
	 * 
	 * @return array Array of shipping classes with slug => name pairs.
	 */
	public static function get_shipping_classes() {
		$shipping_classes = array();
		
		// Add option for products with no shipping class
		$shipping_classes['no-class'] = __( 'No Shipping Class', 'gaitbeacon-shipping-rules' );
		
		// Get all WooCommerce shipping classes
		$terms = get_terms( array(
			'taxonomy'   => 'product_shipping_class',
			'hide_empty' => false,
		) );
		
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$shipping_classes[ $term->slug ] = $term->name;
			}
		}
		
		return $shipping_classes;
	}

	/**
	 * Get all available shipping methods from all shipping zones.
	 * 
	 * Retrieves shipping methods configured in WooCommerce shipping zones.
	 * Returns an array with method_id:instance_id => label format.
	 * 
	 * @return array Array of shipping methods with ID => label pairs.
	 */
	public static function get_shipping_methods() {
		$shipping_methods = array();
		
		// Get all shipping zones
		$zones = WC_Shipping_Zones::get_zones();
		
		// Loop through each zone and get shipping methods
		foreach ( $zones as $zone ) {
			if ( ! empty( $zone['shipping_methods'] ) ) {
				foreach ( $zone['shipping_methods'] as $method ) {
					// Build method ID in format: method_type:instance_id
					$method_id = $method->id . ':' . $method->instance_id;
					$method_title = $method->title . ' (' . $zone['zone_name'] . ')';
					$shipping_methods[ $method_id ] = $method_title;
				}
			}
		}
		
		// Also get methods from "Rest of the World" zone (zone 0)
		$zone_0 = new WC_Shipping_Zone( 0 );
		$methods_0 = $zone_0->get_shipping_methods();
		
		if ( ! empty( $methods_0 ) ) {
			foreach ( $methods_0 as $method ) {
				if ( $method->enabled === 'yes' ) {
					$method_id = $method->id . ':' . $method->instance_id;
					$method_title = $method->title . ' (Rest of the World)';
					$shipping_methods[ $method_id ] = $method_title;
				}
			}
		}
		
		return $shipping_methods;
	}

	/**
	 * Get plugin settings from database.
	 * 
	 * Retrieves saved plugin settings from wp_options table.
	 * Returns empty rules array by default - all configuration is dynamic
	 * and must be set through the admin interface or database.
	 * 
	 * @return array Plugin settings array with 'rules' key.
	 */
	public static function get_settings() {
		$defaults = array(
			'rules' => array(),
		);
		
		$settings = get_option( 'gb_shipping_rules_settings', $defaults );
		
		// Ensure rules array exists
		if ( ! isset( $settings['rules'] ) || ! is_array( $settings['rules'] ) ) {
			$settings['rules'] = array();
		}
		
		return $settings;
	}
}
