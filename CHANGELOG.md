# Changelog

All notable changes to GaitBeacon Shipping Rules will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-03-19

### Added
- **Visual Admin UI**: Complete settings interface at WooCommerce → Settings → Shipping → GaitBeacon Rules
- **Dynamic Rule System**: Configure unlimited rules without editing code
- **Two Action Types**: 
  - "Show Only" (whitelist): Display only selected shipping methods
  - "Hide" (blacklist): Remove specific shipping methods
- **No Shipping Class Support**: Create rules for products without assigned shipping class
- **Dynamic Detection**: Automatically detects all shipping methods from all zones
- **Dynamic Shipping Classes**: Automatically detects all WooCommerce shipping classes
- **Settings Link**: Quick access link on plugins page
- **Add/Remove Rules**: JavaScript-powered dynamic rule management
- **Multi-Select Methods**: Select multiple shipping methods per rule (Ctrl/Cmd)
- **Modular Architecture**: Object-oriented design with separate classes
  - `GB_Helpers`: Utility functions for data retrieval
  - `GB_Filter`: Core filtering logic
  - `GB_Admin`: Admin interface and settings
- **Security Enhancements**:
  - Nonce verification for form submissions
  - Capability checks (`manage_woocommerce`)
  - Input sanitization with `sanitize_text_field()` and `array_map()`
  - Output escaping with `esc_html_e()`, `esc_attr()`, `esc_attr_e()`
- **Comprehensive Documentation**: Detailed docblocks and inline comments
- **Test Script**: `test-settings.php` for debugging saved settings

### Changed
- **Plugin Architecture**: Refactored from 549-line monolithic file to modular structure
  - Main file: 90 lines (plugin header, initialization)
  - Helper class: 118 lines (data retrieval)
  - Filter class: 206 lines (filtering logic)
  - Admin class: 337 lines (UI and settings)
- **Settings Storage**: All configuration stored in `wp_options` table
- **No Hardcoded Defaults**: Plugin starts with empty rules array
- **WooCommerce Integration**: Proper integration with WooCommerce Settings API
- **Hook Priority**: Uses priority 999 for `woocommerce_package_rates` filter
- **Admin Loading**: Admin classes only load when `is_admin()` is true

### Fixed
- **Multiple Rules Saving**: Fixed JavaScript cloning to properly handle array indices
- **Settings Persistence**: Corrected WooCommerce settings hook integration
- **Section Detection**: Fixed `$current_section` global variable usage
- **Form Submission**: Removed custom form wrapper, now uses WooCommerce's built-in form
- **Field Naming**: Ensured unique field names for each rule row
- **Save Hook**: Changed from `woocommerce_update_options_shipping_gb_shipping_rules` to `woocommerce_settings_save_shipping`

### Improved
- **Code Organization**: Separation of concerns with dedicated classes
- **Maintainability**: Smaller, focused methods with single responsibilities
- **Performance**: Conditional admin class loading, early exit if no rules configured
- **User Experience**: Clear instructions, visual feedback, intuitive interface
- **Error Handling**: Graceful failure if WooCommerce is inactive
- **Debugging**: Error logging for troubleshooting save issues

### Removed
- **Hardcoded Shipping Methods**: No default `flat_rate:2` or `flat_rate:4`
- **Hardcoded Shipping Classes**: No default `wholesale` class
- **Custom Submit Button**: Now uses WooCommerce's "Save changes" button
- **Custom Nonce Field**: Uses WooCommerce's built-in nonce verification

## [1.0.0] - Initial Release

### Added
- Basic shipping method filtering based on shipping class
- Hardcoded rules for wholesale vs single-unit products
- WooCommerce dependency check
- Admin notice if WooCommerce is inactive
- Comprehensive inline documentation
- Security check (`ABSPATH` guard)
- Filter hook: `woocommerce_package_rates` (priority 999)

### Features
- Hardcoded shipping method IDs: `flat_rate:2` and `flat_rate:4`
- Hardcoded shipping class: `wholesale`
- Procedural code structure
- Single file plugin (549 lines)
- No admin interface (code editing required)

---

## Upgrade Notes

### From 1.0.0 to 2.0.0

**Breaking Changes:**
- Hardcoded default rules have been removed
- Plugin will show all shipping methods until you configure rules via admin UI

**Migration Steps:**
1. Backup your site before upgrading
2. After activation, go to WooCommerce → Settings → Shipping → GaitBeacon Rules
3. Configure your rules through the admin interface:
   - For wholesale products: Add rule with "wholesale" shipping class
   - For single-unit products: Add rule with "No Shipping Class" or specific class
4. Test on cart/checkout pages to verify rules work as expected
5. If issues occur, check `/wp-content/debug.log` for error messages

**Benefits:**
- No more code editing required
- Support for unlimited rules and shipping classes
- Visual interface for easier management
- Better code organization and maintainability
- Enhanced security and error handling

---

## Support

For issues, questions, or feature requests, contact GaitBeacon support.

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html
