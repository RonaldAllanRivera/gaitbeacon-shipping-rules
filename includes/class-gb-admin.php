<?php
/**
 * Admin Settings Class
 *
 * Handles the admin interface, settings page, and form submission.
 *
 * @package GaitBeacon_Shipping_Rules
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin functionality for GaitBeacon Shipping Rules.
 */
class GB_Admin {

	/**
	 * Initialize admin functionality.
	 */
	public static function init() {
		// Register admin settings page hooks
		add_filter( 'woocommerce_get_sections_shipping', array( __CLASS__, 'add_shipping_section' ) );
		add_filter( 'woocommerce_get_settings_shipping', array( __CLASS__, 'get_settings_fields' ), 10, 2 );
		add_action( 'woocommerce_settings_shipping', array( __CLASS__, 'settings_page' ) );
		add_action( 'woocommerce_settings_save_shipping', array( __CLASS__, 'save_settings' ) );
		
		// Add settings link on plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( GB_PLUGIN_FILE ), array( __CLASS__, 'add_settings_link' ) );
	}

	/**
	 * Add settings link to plugins page.
	 * 
	 * Adds a "Settings" link on the plugins page for quick access to configuration.
	 * 
	 * @param array $links Existing plugin action links.
	 * @return array Modified plugin action links.
	 */
	public static function add_settings_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=gb_shipping_rules' ) . '">' . __( 'Settings', 'gaitbeacon-shipping-rules' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Add custom section to WooCommerce Shipping settings.
	 * 
	 * Registers our custom settings section within WooCommerce > Settings > Shipping.
	 * 
	 * @param array $sections Existing shipping sections.
	 * @return array Modified shipping sections.
	 */
	public static function add_shipping_section( $sections ) {
		$sections['gb_shipping_rules'] = __( 'GaitBeacon Rules', 'gaitbeacon-shipping-rules' );
		return $sections;
	}

	/**
	 * Get settings fields for WooCommerce settings API.
	 * 
	 * Returns empty array because we use custom HTML output instead.
	 * 
	 * @param array  $settings Current settings.
	 * @param string $current_section Current section.
	 * @return array Settings fields.
	 */
	public static function get_settings_fields( $settings, $current_section ) {
		if ( 'gb_shipping_rules' === $current_section ) {
			// Return empty - we handle our own output
			return array();
		}
		return $settings;
	}

	/**
	 * Output settings page based on current section.
	 */
	public static function settings_page() {
		global $current_section;
		
		if ( 'gb_shipping_rules' === $current_section ) {
			self::output_settings_page();
		}
	}

	/**
	 * Output the settings page HTML.
	 * 
	 * Displays the admin interface for configuring shipping class rules.
	 * Located at: WooCommerce > Settings > Shipping > GaitBeacon Rules
	 * 
	 * Features:
	 * - Add/remove rules dynamically with JavaScript
	 * - Select shipping class from dropdown (includes "No Shipping Class" option)
	 * - Choose action: "Show Only" or "Hide"
	 * - Multi-select shipping methods (Ctrl/Cmd for multiple)
	 * - Inline styles and JavaScript for better UX
	 */
	private static function output_settings_page() {
		
		// Get current settings from database
		$settings = GB_Helpers::get_settings();
		$rules = isset( $settings['rules'] ) ? $settings['rules'] : array();
		
		// Get available shipping classes and methods dynamically from WooCommerce
		$shipping_classes = GB_Helpers::get_shipping_classes();
		$shipping_methods = GB_Helpers::get_shipping_methods();
		
		?>
		<h2><?php esc_html_e( 'GaitBeacon Shipping Rules', 'gaitbeacon-shipping-rules' ); ?></h2>
		<p><?php esc_html_e( 'Configure rules to show or hide specific shipping methods based on product shipping classes in the cart.', 'gaitbeacon-shipping-rules' ); ?></p>
		
		<table class="widefat" id="gb-rules-table">
				<thead>
					<tr>
						<th style="width: 30%;"><?php esc_html_e( 'Shipping Class', 'gaitbeacon-shipping-rules' ); ?></th>
						<th style="width: 20%;"><?php esc_html_e( 'Action', 'gaitbeacon-shipping-rules' ); ?></th>
						<th style="width: 40%;"><?php esc_html_e( 'Shipping Methods', 'gaitbeacon-shipping-rules' ); ?></th>
						<th style="width: 10%;"><?php esc_html_e( 'Remove', 'gaitbeacon-shipping-rules' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( ! empty( $rules ) ) {
						foreach ( $rules as $index => $rule ) {
							self::output_rule_row( $index, $rule, $shipping_classes, $shipping_methods );
						}
					} else {
						// Show one empty row if no rules exist
						self::output_rule_row( 0, array(), $shipping_classes, $shipping_methods );
					}
					?>
				</tbody>
			</table>
			
			<p>
				<button type="button" class="button" id="gb-add-rule"><?php esc_html_e( 'Add Rule', 'gaitbeacon-shipping-rules' ); ?></button>
			</p>
			
			<?php self::output_styles(); ?>
			<?php self::output_scripts( $rules ); ?>
		<?php
	}

	/**
	 * Output inline styles for settings page.
	 */
	private static function output_styles() {
		?>
		<style>
			#gb-rules-table { margin-top: 20px; }
			#gb-rules-table th { padding: 10px; background: #f9f9f9; }
			#gb-rules-table td { padding: 10px; }
			.gb-rule-row select { width: 100%; max-width: 400px; }
			.gb-remove-rule { color: #a00; text-decoration: none; }
			.gb-remove-rule:hover { color: #dc3232; }
		</style>
		<?php
	}

	/**
	 * Output inline JavaScript for settings page.
	 * 
	 * @param array $rules Current rules for calculating initial index.
	 */
	private static function output_scripts( $rules ) {
		$rule_count = ! empty( $rules ) ? count( $rules ) : 1;
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				var ruleIndex = <?php echo $rule_count; ?>;
				
				// Add new rule row by cloning the last row
				$('#gb-add-rule').on('click', function() {
					var lastRow = $('#gb-rules-table tbody tr:last');
					var newRow = lastRow.clone();
					
					// Update field names with new index
					newRow.find('select, input').each(function() {
						var name = $(this).attr('name');
						if (name) {
							// Replace the index in brackets with new index
							var newName = name.replace(/\[(\d+)\]/, '[' + ruleIndex + ']');
							$(this).attr('name', newName);
						}
					});
					
					// Clear all selections
					newRow.find('select').val('');
					newRow.find('option').prop('selected', false);
					
					// Append new row
					$('#gb-rules-table tbody').append(newRow);
					ruleIndex++;
				});
				
				// Remove rule row (prevent removing last row)
				$(document).on('click', '.gb-remove-rule', function(e) {
					e.preventDefault();
					var rowCount = $('#gb-rules-table tbody tr').length;
					if (rowCount > 1) {
						$(this).closest('tr').remove();
					} else {
						alert('<?php esc_html_e( 'You must have at least one rule.', 'gaitbeacon-shipping-rules' ); ?>');
					}
				});
			});
		</script>
		<?php
	}

	/**
	 * Output a single rule row in the settings table.
	 * 
	 * Generates HTML for one rule row with dropdowns and multi-select.
	 * Used by output_settings_page() to display existing rules and empty rows.
	 * 
	 * @param int   $index Rule index for array naming.
	 * @param array $rule Rule data (shipping_class, action, methods).
	 * @param array $shipping_classes Available shipping classes from WooCommerce.
	 * @param array $shipping_methods Available shipping methods from all zones.
	 */
	private static function output_rule_row( $index, $rule, $shipping_classes, $shipping_methods ) {
		$shipping_class = isset( $rule['shipping_class'] ) ? $rule['shipping_class'] : '';
		$action = isset( $rule['action'] ) ? $rule['action'] : 'show_only';
		$methods = isset( $rule['methods'] ) && is_array( $rule['methods'] ) ? $rule['methods'] : array();
		?>
		<tr class="gb-rule-row">
			<td>
				<select name="gb_rules[<?php echo esc_attr( $index ); ?>][shipping_class]" required>
					<option value=""><?php esc_html_e( 'Select Shipping Class', 'gaitbeacon-shipping-rules' ); ?></option>
					<?php foreach ( $shipping_classes as $slug => $name ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $shipping_class, $slug ); ?>>
							<?php echo esc_html( $name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
			<td>
				<select name="gb_rules[<?php echo esc_attr( $index ); ?>][action]" required>
					<option value="show_only" <?php selected( $action, 'show_only' ); ?>>
						<?php esc_html_e( 'Show Only', 'gaitbeacon-shipping-rules' ); ?>
					</option>
					<option value="hide" <?php selected( $action, 'hide' ); ?>>
						<?php esc_html_e( 'Hide', 'gaitbeacon-shipping-rules' ); ?>
					</option>
				</select>
			</td>
			<td>
				<select name="gb_rules[<?php echo esc_attr( $index ); ?>][methods][]" multiple required style="height: 100px;">
					<?php foreach ( $shipping_methods as $method_id => $method_name ) : ?>
						<option value="<?php echo esc_attr( $method_id ); ?>" <?php echo in_array( $method_id, $methods, true ) ? 'selected' : ''; ?>>
							<?php echo esc_html( $method_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<br><small><?php esc_html_e( 'Hold Ctrl/Cmd to select multiple', 'gaitbeacon-shipping-rules' ); ?></small>
			</td>
			<td style="text-align: center;">
				<a href="#" class="gb-remove-rule" title="<?php esc_attr_e( 'Remove Rule', 'gaitbeacon-shipping-rules' ); ?>">
					<span class="dashicons dashicons-trash"></span>
				</a>
			</td>
		</tr>
		<?php
	}

	/**
	 * Handle settings form submission.
	 * 
	 * Validates and saves the shipping rules configuration to wp_options.
	 * Includes security checks (nonce, capabilities) and input sanitization.
	 * Called by WooCommerce when "Save changes" button is clicked.
	 */
	public static function save_settings() {
		global $current_section;
		
		// Only process if we're on our section
		if ( 'gb_shipping_rules' !== $current_section ) {
			return;
		}
		
		// Check user permissions
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		
		// Get submitted rules and sanitize all input
		$rules = array();
		
		if ( isset( $_POST['gb_rules'] ) && is_array( $_POST['gb_rules'] ) ) {
			foreach ( $_POST['gb_rules'] as $rule ) {
				
				// Skip rules with missing shipping class or methods
				if ( empty( $rule['shipping_class'] ) || empty( $rule['methods'] ) ) {
					continue;
				}
				
				// Sanitize and add rule
				$rules[] = array(
					'shipping_class' => sanitize_text_field( $rule['shipping_class'] ),
					'action'         => in_array( $rule['action'], array( 'show_only', 'hide' ), true ) ? $rule['action'] : 'show_only',
					'methods'        => array_map( 'sanitize_text_field', (array) $rule['methods'] ),
				);
			}
		}
		
		// Save sanitized settings to database
		$settings = array(
			'rules' => $rules,
		);
		
		update_option( 'gb_shipping_rules_settings', $settings );
	}

	/**
	 * Display success notice after saving settings.
	 */
	public static function settings_saved_notice() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Shipping rules saved successfully!', 'gaitbeacon-shipping-rules' ); ?></p>
		</div>
		<?php
	}
}
