# Ability Explorer

A WordPress plugin that discovers, inspects, tests, and documents all abilities registered via the **WordPress 6.9+ Abilities API** from core, plugins, and themes.

## Overview

**Ability Explorer** leverages the **WordPress Abilities API** (part of the AI Building Blocks initiative) to provide a comprehensive interface for exploring and testing registered abilities across your entire WordPress installation.

The plugin uses `wp_get_abilities()` to display **every ability** currently registered on your site, regardless of whether it comes from WordPress core, active plugins, or your theme.

## Requirements

- **WordPress:** 6.9 or higher (including beta and RC versions)
- **Abilities API Plugin:** Must be installed and activated ([GitHub](https://github.com/WordPress/abilities-api))
- **PHP:** 8.0 or higher

**Important:** The Abilities API is a **separate plugin** that must be installed before using Ability Explorer.

## Features

### Automatic Detection
- Detects WordPress 6.9+ and shows admin notice if the Abilities API isn't available
- Validates that the Abilities API is enabled and accessible

### Statistics Dashboard
- Visual overview of all registered abilities
- Breakdown by provider (Core, Plugins, Theme)

### Searchable & Filterable Table
The main interface displays abilities in a comprehensive table with:
- **Name** - Ability display name with description
- **Slug** - Ability identifier (namespace/ability-name)
- **Provider** - Source of the ability (Core, Plugin, or Theme)
- **Actions** - Quick links to view details or test

**Filtering Options:**
- Filter by provider type (Core, Plugins, Theme)
- Search by name, slug, or description
- Sort by any column

### Detail View
For each ability, you can view:
- Complete description
- Provider information
- **Input Schema** - JSON schema for expected input
- **Output Schema** - JSON schema for expected output
- **Raw Data** - Complete ability registration data
- Copy-to-clipboard functionality for all schemas

### Test Runner
Interactive testing interface featuring:
- **JSON Editor** with syntax validation
- **Schema Validation** - Validates input against input schema
- **Live Testing** - Invoke abilities with custom input
- **Result Display** - Shows success/error responses with formatted output
- **Example Input** - Auto-generated from input schema
- Real-time JSON formatting

## Installation

### Requirements

- WordPress 6.9 or higher (the Abilities API is included in WordPress 6.9+)
- PHP 8.0 or higher

### Install Ability Explorer

1. Download the plugin files
2. Upload the `abilitiesexplorer` folder to `/wp-content/plugins/`
3. Activate the plugin:
   - Navigate to **Plugins → Installed Plugins**
   - Find "Ability Explorer"
   - Click **Activate**
4. Access the plugin:
   - Go to **Abilities → Explorer** in the admin menu

### Troubleshooting Installation

If you see an error about the Abilities API not being available:
1. Click "Debug Information" in the error notice to see what's missing
2. Verify the Abilities API is available in WordPress 6.9+
3. Check the [Troubleshooting](#troubleshooting) section below

### Demo Abilities

To verify the Abilities API is working and learn how to create abilities, you can enable demo abilities:

1. Go to **Abilities → Demo Abilities** in the admin menu
2. Click the **"Enable"** button for "Get Site Health Status"
3. The page will reload showing the ability as enabled
4. Go to **Abilities → Explorer** to see it in the abilities list
5. You should now see **"ability-explorer/get-site-health"** in the list
6. Click **Test** to try it with input: `{"include_details": false}`
7. Expected output: Site health information including status, score, and counts

To disable the demo ability, return to **Demo Abilities** and click **"Disable"**.

The demo ability source code is in `/abilities/site-health.php` with detailed comments explaining how to register abilities.

## Usage

### Viewing Abilities

1. Navigate to **Abilities → Explorer** in the admin menu
2. View the statistics dashboard showing ability distribution
3. Browse the complete list of registered abilities
4. Use filters to narrow down by provider (Core, Plugin, Theme)
5. Use the search box to find specific abilities

### Inspecting an Ability

1. From the ability list, click **View** on any ability
2. Review the complete details including:
   - Description and metadata
   - Input/Output schemas
   - Dependencies
   - Raw registration data
3. Click **Copy** on any schema to copy to clipboard
4. Click **Test Ability** to try it out

### Testing an Ability

1. From the ability list or detail view, click **Test**
2. Review the auto-generated example input
3. Modify the JSON input as needed
4. Click **Validate Input** to check against the schema
5. Click **Invoke Ability** to execute
6. View the results in the result panel

The test runner provides:
- Real-time JSON syntax validation
- Schema compliance checking
- Error messages with details
- Success responses with formatted output

## Plugin Architecture

```
abilitiesexplorer/
├── abilitiesexplorer.php         # Main plugin file
├── admin/
│   └── class-admin-page.php     # Admin UI and page rendering
├── includes/
│   ├── class-ability-handler.php # Abilities API interaction
│   └── class-ability-table.php   # WP_List_Table implementation
├── assets/
│   ├── css/
│   │   └── admin.css            # Admin styles
│   └── js/
│       └── admin.js             # Admin JavaScript
└── README.md                     # This file
```

### Key Classes

- **`Ability_Explorer`** - Main plugin class, handles initialization and version checks
- **`Ability_Explorer_Handler`** - Interfaces with the Abilities API, formats and processes ability data
- **`Ability_Explorer_Table`** - Extends `WP_List_Table` for the main abilities listing
- **`Ability_Explorer_Admin_Page`** - Manages admin pages, UI rendering, and AJAX handlers

## Abilities API Integration

The plugin integrates with the WordPress Abilities API using these key functions:

```php
// Check if API is available
if ( class_exists( 'WP_Ability' ) ) {
    // Get all abilities
    $abilities = wp_get_abilities();

    // Get a single ability
    $ability = wp_get_ability( 'namespace/ability-name' );

    // Invoke an ability
    if ( $ability ) {
        $result = $ability->execute( $input_data );

        // Check for errors
        if ( is_wp_error( $result ) ) {
            // Handle error
        }
    }
}
```

**Note:** Ability names follow the `namespace/ability-name` format (e.g., `wordpress/summarize-text`).

## Security

- User capability checks (`manage_options` required)
- Nonce verification for AJAX requests
- Input sanitization and validation
- Output escaping
- JSON schema validation before invocation

## Development

### Adding Features

The plugin is designed to be extensible:

1. **Custom Filters** - Add provider detection logic in `class-ability-handler.php:107`
2. **Additional Columns** - Extend `get_columns()` in `class-ability-table.php:29`
3. **Custom Validation** - Enhance schema validation in `class-ability-handler.php:166`

### Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## Troubleshooting

### "Abilities API not available" notice

**Problem:** The plugin shows an error that the Abilities API is not available.

**Understanding the Issue:**
The Abilities API is a new feature in WordPress 6.9 that may not be fully implemented in early beta releases. The plugin checks for:
- `abilities()` function
- `WP_Abilities` class
- `wp_abilities()` function (alternative naming)

**Solutions:**

1. **Check Debug Information:**
   - Click "Debug Information" in the error notice to see what's available in your WordPress installation
   - This will show your WP version and which API components exist

2. **Bypass the Check (for testing/development):**
   - If you want to test the plugin interface without the API being available, add this to your `wp-config.php`:
   ```php
   define( 'ABILITY_EXPLORER_SKIP_API_CHECK', true );
   ```
   - Note: The plugin won't be able to fetch or invoke abilities without the actual API

3. **Wait for Later Beta/RC Releases:**
   - The Abilities API might be added in later WordPress 6.9 beta/RC releases
   - Check WordPress 6.9 development updates

4. **Verify Prerequisites:**
   - Ensure you're running WordPress 6.9 or higher (including beta/RC versions like 6.9-beta1)
   - Check that your hosting environment supports PHP 8.0+
   - Look for any WordPress constants that might enable the feature

### Abilities not showing up

**Problem:** The table shows no abilities.

**Solutions:**
- Verify that abilities are actually registered on your site
- Check that the abilities are registered before the plugin loads
- Look for JavaScript errors in the browser console

### Test runner errors

**Problem:** Testing an ability returns errors.

**Solutions:**
- Verify the JSON input syntax is valid
- Ensure the input matches the input schema
- Check that you have the required capability to invoke the ability
- Review error messages for specific issues

## FAQ

**Q: Does this plugin register any abilities itself?**
A: No, this plugin is purely for exploration and testing. It does not register any abilities.

**Q: Can I use this in production?**
A: Yes, the plugin is safe for production use. However, be cautious when testing abilities that modify data.

**Q: Will this work with WordPress < 6.9?**
A: No, the plugin requires WordPress 6.9+ with the Abilities API enabled. It works with beta and RC versions (e.g., 6.9-beta1, 6.9-RC1).

**Q: Can I test abilities that require special permissions?**
A: You can only test abilities if you have the WordPress capability required by that ability.

**Q: Does this work with WordPress 6.9 beta versions?**
A: Yes! The plugin automatically detects and works with beta, RC, and alpha versions (e.g., 6.9-beta1, 6.9-RC1). The version check strips pre-release suffixes to ensure compatibility. However, note that the Abilities API itself may not be fully implemented in early beta releases.

**Q: I'm running WP 6.9 beta but getting "Abilities API not available" - what should I do?**
A: This is expected in early beta releases. The Abilities API may not be included yet. Options:
1. Click "Debug Information" in the error notice to see what's available
2. Add `define( 'ABILITY_EXPLORER_SKIP_API_CHECK', true );` to wp-config.php to bypass the check and explore the UI
3. Wait for later beta/RC releases when the API is implemented
4. Monitor WordPress 6.9 development updates for when the Abilities API is added

## Changelog

### 1.0.0
- Initial release
- Ability discovery and listing with search and filters
- Detail view with complete schema display
- Interactive test runner for invoking abilities
- Statistics dashboard showing provider breakdown
- Demo abilities system with Site Health example
- Support for WordPress beta/RC/alpha versions (e.g., 6.9-beta1)
- Enhanced API detection with multiple checks
- Debug information panel for troubleshooting API availability
- Bypass constant for testing/development (ABILITY_EXPLORER_SKIP_API_CHECK)

## License

GPL v2 or later

## Credits

Developed as part of the WordPress AI Building Blocks initiative to explore and document the new Abilities API.

## Support

For issues, feature requests, or contributions:
- GitHub Issues: https://github.com/yourusername/ability-explorer/issues
- WordPress Support Forums: [Link to support forum]

## Links

- [WordPress Abilities API Documentation](https://developer.wordpress.org/)
- [WordPress 6.9 Release Notes](https://wordpress.org/news/)
- [AI Building Blocks Initiative](https://make.wordpress.org/)