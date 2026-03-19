<?php
/**
 * Shipping Method Filter Class
 *
 * Handles the core filtering logic for shipping methods based on cart shipping classes.
 *
 * @package GaitBeacon_Shipping_Rules
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core filtering functionality for shipping methods.
 */
class GB_Filter {

	/**
	 * Initialize the filter.
	 */
	public static function init() {
		// Hook into WooCommerce shipping rates filter (runs on cart/checkout pages)
		// Priority 999 ensures this runs after other shipping plugins
		add_filter( 'woocommerce_package_rates', array( __CLASS__, 'filter_shipping_methods' ), 999, 2 );
	}

	/**
	 * Filter available shipping methods based on cart product shipping classes.
	 * 
	 * This is the core filtering function that runs on cart and checkout pages.
	 * 
	 * Process:
	 * 1. Retrieves rules from database
	 * 2. Scans all products in cart to identify shipping classes
	 * 3. Matches cart shipping classes against configured rules
	 * 4. Applies "Show Only" rules (whitelist approach)
	 * 5. Applies "Hide" rules (blacklist approach - takes precedence)
	 * 6. Returns filtered shipping methods
	 * 
	 * Rule Logic:
	 * - "Show Only" rules: Only display selected methods for matching shipping class
	 * - "Hide" rules: Remove selected methods for matching shipping class
	 * - Multiple shipping classes in cart: All matching rules are combined
	 * - No rules configured: All shipping methods are shown (no filtering)
	 * 
	 * @param array $rates Available shipping rates for the package.
	 * @param array $package Package information including cart items.
	 * @return array Filtered shipping rates based on configured rules.
	 */
	public static function filter_shipping_methods( $rates, $package ) {
		
		// Get plugin settings from database
		$settings = GB_Helpers::get_settings();
		$rules = isset( $settings['rules'] ) ? $settings['rules'] : array();
		
		// If no rules configured in admin, return all rates unchanged (show all methods)
		if ( empty( $rules ) ) {
			return $rates;
		}
		
		// Collect all unique shipping classes present in the cart
		$cart_shipping_classes = self::get_cart_shipping_classes( $package );
		
		// If cart is empty or has no valid products, return rates unchanged
		if ( empty( $cart_shipping_classes ) ) {
			return $rates;
		}
		
		// Apply rules and get methods to show/hide
		$filter_data = self::apply_rules( $rules, $cart_shipping_classes );
		
		// Apply filtering: First handle "Show Only" rules, then "Hide" rules
		$rates = self::apply_filtering( $rates, $filter_data );
		
		return $rates;
	}

	/**
	 * Get all shipping classes present in the cart.
	 * 
	 * @param array $package Package information including cart items.
	 * @return array Array of shipping class slugs found in cart.
	 */
	private static function get_cart_shipping_classes( $package ) {
		$cart_shipping_classes = array();
		
		if ( empty( $package['contents'] ) ) {
			return $cart_shipping_classes;
		}
		
		foreach ( $package['contents'] as $cart_item ) {
			
			// Get the product object from the cart item
			$product = $cart_item['data'];
			
			// Skip if product object is invalid
			if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
				continue;
			}
			
			// Get the shipping class slug for this product using WooCommerce method
			$shipping_class = $product->get_shipping_class();
			
			// If product has no shipping class, use 'no-class' identifier
			if ( empty( $shipping_class ) ) {
				$shipping_class = 'no-class';
			}
			
			// Add to array if not already present
			if ( ! in_array( $shipping_class, $cart_shipping_classes, true ) ) {
				$cart_shipping_classes[] = $shipping_class;
			}
		}
		
		return $cart_shipping_classes;
	}

	/**
	 * Apply rules and determine which methods to show/hide.
	 * 
	 * @param array $rules Configured rules from settings.
	 * @param array $cart_shipping_classes Shipping classes found in cart.
	 * @return array Array with 'methods_to_show', 'methods_to_hide', and 'has_show_only_rule'.
	 */
	private static function apply_rules( $rules, $cart_shipping_classes ) {
		$methods_to_show = array();
		$methods_to_hide = array();
		$has_show_only_rule = false;
		
		// Loop through all rules and check if they match any shipping class in cart
		foreach ( $rules as $rule ) {
			
			// Skip rules with missing required fields
			if ( empty( $rule['shipping_class'] ) || empty( $rule['methods'] ) ) {
				continue;
			}
			
			// Check if this rule's shipping class matches any product in cart
			if ( ! in_array( $rule['shipping_class'], $cart_shipping_classes, true ) ) {
				continue;
			}
			
			// Get the action and methods for this rule
			$action = isset( $rule['action'] ) ? $rule['action'] : 'show_only';
			$methods = is_array( $rule['methods'] ) ? $rule['methods'] : array();
			
			if ( $action === 'show_only' ) {
				// "Show only" action: add methods to allowed list
				$has_show_only_rule = true;
				$methods_to_show = array_merge( $methods_to_show, $methods );
			} elseif ( $action === 'hide' ) {
				// "Hide" action: add methods to blocked list
				$methods_to_hide = array_merge( $methods_to_hide, $methods );
			}
		}
		
		return array(
			'methods_to_show'    => $methods_to_show,
			'methods_to_hide'    => $methods_to_hide,
			'has_show_only_rule' => $has_show_only_rule,
		);
	}

	/**
	 * Apply filtering to shipping rates based on collected rules.
	 * 
	 * @param array $rates Available shipping rates.
	 * @param array $filter_data Data from apply_rules method.
	 * @return array Filtered shipping rates.
	 */
	private static function apply_filtering( $rates, $filter_data ) {
		$methods_to_show = $filter_data['methods_to_show'];
		$methods_to_hide = $filter_data['methods_to_hide'];
		$has_show_only_rule = $filter_data['has_show_only_rule'];
		
		// Apply "Show Only" rules first
		if ( $has_show_only_rule ) {
			// "Show Only" rules matched: Remove all methods except those explicitly allowed
			foreach ( $rates as $rate_id => $rate ) {
				if ( ! in_array( $rate_id, $methods_to_show, true ) ) {
					unset( $rates[ $rate_id ] );
				}
			}
		}
		
		// Apply "Hide" rules last (they override "Show Only" rules)
		if ( ! empty( $methods_to_hide ) ) {
			foreach ( $rates as $rate_id => $rate ) {
				if ( in_array( $rate_id, $methods_to_hide, true ) ) {
					unset( $rates[ $rate_id ] );
				}
			}
		}
		
		return $rates;
	}
}
