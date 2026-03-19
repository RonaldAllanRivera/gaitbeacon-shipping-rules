# GaitBeacon Shipping Rules

A lightweight WordPress plugin for WooCommerce that conditionally displays shipping methods based on product shipping classes in the cart.

## Description

This plugin provides a flexible rule-based system to control which shipping methods are displayed to customers based on the shipping class of products in their cart. Configure rules through an intuitive admin interface - no code editing required.

**Key Features:**
- **Visual Admin UI**: Configure rules via WooCommerce Settings
- **Flexible Rules**: Show only or hide specific shipping methods per shipping class
- **Multiple Shipping Classes**: Support for all your shipping classes including products with no class
- **Dynamic Detection**: Automatically detects available shipping methods and classes
- **No Code Required**: All configuration through the WordPress admin

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Installation

1. Upload the `gaitbeacon-shipping-rules` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure WooCommerce is installed and activated
4. Configure your shipping methods and shipping classes in WooCommerce

## Configuration

### Accessing Settings

There are two ways to access the settings page:

**Option 1:** WooCommerce → Settings → Shipping → **GaitBeacon Rules** tab

**Option 2:** Plugins page → Find "GaitBeacon Shipping Rules" → Click **Settings** link

### Initial Setup

After activation, the plugin has **no rules configured by default**. This means:
- All shipping methods will be displayed to customers
- No filtering occurs until you create at least one rule
- You must configure rules through the admin interface to enable filtering

### Creating Rules

Each rule consists of three parts:

1. **Shipping Class**: Select which shipping class this rule applies to
   - Choose from your existing shipping classes
   - Or select "No Shipping Class" for products without a class

2. **Action**: Choose what to do with shipping methods
   - **Show Only**: Display only the selected shipping methods (hide all others)
   - **Hide**: Hide the selected shipping methods (show all others)

3. **Shipping Methods**: Select one or more shipping methods
   - Hold Ctrl/Cmd to select multiple methods
   - All configured shipping methods from all zones are available

### Example Configurations

#### Example 1: Wholesale Products
- **Shipping Class**: wholesale
- **Action**: Show Only
- **Shipping Methods**: Wholesale Flat Rate
- **Result**: Only wholesale shipping appears for wholesale products

#### Example 2: Hide Express for Regular Products
- **Shipping Class**: No Shipping Class
- **Action**: Hide
- **Shipping Methods**: Express Shipping
- **Result**: Express shipping is hidden for regular products

#### Example 3: Multiple Rules
You can create multiple rules that work together:
- Rule 1: wholesale → Show Only → Wholesale Flat Rate
- Rule 2: express → Show Only → Express Shipping
- Rule 3: No Shipping Class → Show Only → Standard Shipping

### Setting Up Shipping Classes

1. Go to **WooCommerce → Settings → Shipping → Shipping Classes**
2. Create your shipping classes (e.g., wholesale, express, bulk)
3. Edit products and assign them to the appropriate shipping class
4. The plugin will automatically detect and list all your shipping classes

## How Rules Work

### Rule Application Logic

The plugin applies rules on **cart and checkout pages** when WooCommerce calculates shipping rates:

1. **Cart Analysis**: Scans all products in cart and identifies their shipping classes (including products with no class)

2. **Rule Matching**: Loops through configured rules and checks if the rule's shipping class matches any product in cart

3. **Action Collection**:
   - **"Show Only" rules**: Adds methods to an allowed list
   - **"Hide" rules**: Adds methods to a blocked list

4. **Filtering Process**:
   - If any "Show Only" rules matched: Remove all methods NOT in the allowed list
   - Then apply "Hide" rules: Remove methods in the blocked list (overrides "Show Only")
   - If no rules matched: Show all available shipping methods (no filtering)

5. **Multiple Shipping Classes**: When cart contains products with different shipping classes:
   - All matching rules are applied
   - "Show Only" methods from all matched rules are combined (union)
   - "Hide" rules always take precedence and remove methods even if they're in "Show Only" list

### Example Scenarios

#### Scenario 1: Single Shipping Class
**Cart**: 2 wholesale products
**Rule**: wholesale → Show Only → Wholesale Flat Rate
**Result**: Only Wholesale Flat Rate is visible

#### Scenario 2: Mixed Cart
**Cart**: 1 wholesale product + 1 regular product
**Rules**: 
- wholesale → Show Only → Wholesale Flat Rate
- No Shipping Class → Show Only → Standard Shipping

**Result**: Both Wholesale Flat Rate AND Standard Shipping are visible

#### Scenario 3: Hide Rule Priority
**Cart**: 1 express product
**Rules**:
- express → Show Only → Express Shipping, Standard Shipping
- express → Hide → Standard Shipping

**Result**: Only Express Shipping is visible (Hide rule removed Standard)

## Technical Details

### File Structure

The plugin follows WordPress best practices with a modular, object-oriented architecture:

```
gaitbeacon-shipping-rules/
├── gaitbeacon-shipping-rules.php    # Main plugin file (90 lines)
├── includes/
│   ├── class-gb-helpers.php         # Helper functions class
│   ├── class-gb-filter.php          # Core filtering logic class
│   └── class-gb-admin.php           # Admin interface class
└── README.md                         # Documentation
```

**Main Plugin File** (`gaitbeacon-shipping-rules.php`)
- Plugin header and constants
- WooCommerce dependency check
- Class autoloading
- Initialization logic

**GB_Helpers Class** (`includes/class-gb-helpers.php`)
- `get_shipping_classes()` - Retrieve all WooCommerce shipping classes
- `get_shipping_methods()` - Retrieve all shipping methods from all zones
- `get_settings()` - Get plugin settings from database

**GB_Filter Class** (`includes/class-gb-filter.php`)
- `init()` - Register filter hooks
- `filter_shipping_methods()` - Main filtering function (runs on cart/checkout)
- `get_cart_shipping_classes()` - Extract shipping classes from cart
- `apply_rules()` - Match rules to cart shipping classes
- `apply_filtering()` - Apply show/hide logic to shipping rates

**GB_Admin Class** (`includes/class-gb-admin.php`)
- `init()` - Register admin hooks
- `add_settings_link()` - Add link to plugins page
- `add_shipping_section()` - Add tab to WooCommerce settings
- `output_settings_page()` - Render settings page HTML
- `output_rule_row()` - Render single rule row
- `save_settings()` - Handle form submission
- `settings_saved_notice()` - Display success message

### WordPress/WooCommerce Hooks Used
- `plugins_loaded` - Initialize plugin after WooCommerce loads
- `woocommerce_package_rates` (priority 999) - Filter shipping methods on cart/checkout pages
- `woocommerce_get_sections_shipping` - Add "GaitBeacon Rules" tab to shipping settings
- `woocommerce_settings_shipping` - Display settings page content
- `woocommerce_settings_save_shipping` - Handle form submission
- `plugin_action_links_gaitbeacon-shipping-rules/gaitbeacon-shipping-rules.php` - Add settings link
- `admin_notices` - Display WooCommerce dependency notice or success messages

### Naming Conventions
- **Class names**: `GB_` prefix (e.g., `GB_Helpers`, `GB_Filter`, `GB_Admin`)
- **Global functions**: `gb_` prefix (only 3 in main file for initialization)
- **Constants**: `GB_` prefix (e.g., `GB_PLUGIN_VERSION`, `GB_PLUGIN_DIR`)
- **Methods**: Static methods for utility functions, instance methods where state is needed

### Security
- `ABSPATH` check prevents direct file access
- Nonce verification for form submissions
- Capability checks (`manage_woocommerce` permission required)
- Input sanitization with `sanitize_text_field()` and `array_map()`
- Output escaping with `esc_html_e()`, `esc_attr()`, `esc_attr_e()`
- No external dependencies

### Performance
- Lightweight - single file plugin
- Settings stored in `wp_options` table (one database query per page load)
- Filter runs only when WooCommerce calculates shipping rates (cart/checkout)
- Early exit if no rules are configured
- Priority 999 ensures it runs after other shipping plugins

## Troubleshooting

### Shipping methods not filtering correctly

1. **Check rules configuration**: Go to WooCommerce → Settings → Shipping → GaitBeacon Rules and verify your rules are saved
2. **Verify shipping class assignment**: Edit products and ensure they have the correct shipping class assigned
3. **Check shipping method availability**: Ensure the shipping methods exist and are enabled in your shipping zones
4. **Clear cart cache**: Empty cart and re-add products - WooCommerce may cache shipping calculations
5. **Test shipping zone**: Verify the customer's address matches a shipping zone with configured methods
6. **Check for conflicts**: Temporarily disable other shipping-related plugins to identify conflicts
7. **Check browser console**: Look for JavaScript errors that might prevent the settings page from working

### Admin notice appears

If you see "requires WooCommerce to be installed and activated":
- Install and activate WooCommerce
- Ensure WooCommerce is not network-deactivated (on multisite)

### No shipping methods appear at checkout

1. **No rules configured**: If you haven't created any rules, all shipping methods will show (default behavior)
2. **Rules too restrictive**: Check if your "Show Only" rules are excluding all methods
3. **Shipping zone mismatch**: Verify the customer's address is covered by a shipping zone
4. **Methods disabled**: Ensure shipping methods are enabled in WooCommerce → Settings → Shipping
5. **Conflicting plugins**: Other plugins may be hiding shipping methods
6. **Empty cart**: Shipping methods only appear when cart has products

### Settings page not saving

1. **Required fields**: Ensure all three fields (Shipping Class, Action, Methods) are filled for each rule
2. **Browser compatibility**: Try a different browser if the form doesn't submit
3. **JavaScript errors**: Check browser console for errors
4. **Permissions**: Ensure your user account has `manage_woocommerce` capability

## Changelog

### 2.0.0 (Current)
- **NEW**: Visual admin UI for configuring rules via WooCommerce Settings
- **NEW**: Support for unlimited shipping class rules
- **NEW**: Two action types: "Show Only" (whitelist) and "Hide" (blacklist)
- **NEW**: Support for products with no shipping class assigned
- **NEW**: Dynamic detection of all shipping methods from all zones
- **NEW**: Dynamic detection of all shipping classes from WooCommerce
- **NEW**: Settings link on plugins page for quick access
- **NEW**: Add/remove rules dynamically with JavaScript
- **NEW**: Multi-select shipping methods (Ctrl/Cmd for multiple)
- **NEW**: Modular object-oriented architecture with separate classes
- **IMPROVED**: Fully dynamic - no hardcoded values or defaults
- **IMPROVED**: No code editing required - all configuration via admin UI
- **IMPROVED**: More flexible rule-based system with priority handling
- **IMPROVED**: Better security with nonce verification and capability checks
- **IMPROVED**: Comprehensive inline documentation and docblocks
- **IMPROVED**: Organized file structure following WordPress best practices
- **IMPROVED**: Separation of concerns (Helpers, Filter, Admin classes)
- **IMPROVED**: Main plugin file reduced from 549 to 90 lines

### 1.0.0
- Initial release
- Hardcoded shipping method filtering
- WooCommerce dependency check
- Comprehensive inline documentation

## Support

For issues or questions, contact GaitBeacon support.

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html
