# WPBuoy Endpoint Manager

Control which REST API endpoints are accessible on your WordPress site. Enable or disable specific endpoints to enhance security and performance.

## Features

### Free Version

- **Manage WordPress Core Endpoints** - Control access to all WordPress core REST API endpoints
- **Static Endpoint Preview** - Preview any static endpoint and inspect its response without leaving the dashboard
- **Simple Toggle Interface** - Easy-to-use interface for enabling/disabling endpoints
- **Organized by Namespace** - Endpoints grouped by namespace for easy management
- **Security Enhancement** - Reduce your site's attack surface by disabling unused endpoints

### Pro Version

Upgrade to [WPBuoy Endpoint Manager Pro](https://wpbuoy.com/product/endpoint-manager/) for advanced features:

- **All Namespaces** - Manage endpoints from plugins and themes, not just WordPress core
- **Dynamic Endpoints** - Support for dynamic/regex endpoints with a guided fill-in-the-blanks preview modal
- **Endpoint Filtering** - Filter endpoints by status, type, and namespace
- **Security Logs** - Track all blocked API requests with detailed logs
- **Export Logs** - Export security logs to CSV for analysis
- **Endpoint Summary** - View statistics and summary of your endpoints
- **Priority Support** - Get help when you need it

## Installation

1. Upload the plugin ZIP via **WordPress Admin → Plugins → Add New → Upload Plugin**, then activate.
2. Navigate to **Endpoints → Manage Endpoints** to configure endpoint access.
3. Toggle endpoints on/off as needed.

## Usage

1. Go to **Endpoints** in your WordPress admin
2. Browse through the available endpoints organized by namespace
3. Click on a namespace to expand and view its endpoints
4. Use the toggle switch to enable or disable specific endpoints
5. Click **Save Settings** to apply your changes

## Important Warning

Disabling certain endpoints may affect WordPress functionality, plugins, or themes that depend on the REST API. Always test thoroughly after making changes. We recommend testing on a staging site first.

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher

## License

GPL v2 or later

## Support

For support, please visit [WPBuoy Support](https://wpbuoy.com/support)

## Author

Jose Martin Cipriano - [LinkedIn](https://www.linkedin.com/in/jmcipriano)
