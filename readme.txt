=== REST API Manager ===
Contributors: jmcipriano
Tags: rest api, security, api, disable endpoints, rest api security
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Control which REST API endpoints are accessible on your WordPress site. Enable or disable specific endpoints to enhance security and performance.

== Description ==

REST API Manager gives you complete control over your WordPress REST API endpoints. Easily enable or disable specific endpoints to enhance your site's security and performance.

= Free Features =

* **Manage WordPress Core Endpoints** - Control access to all WordPress core REST API endpoints
* **Static Endpoints Support** - Manage static REST API endpoints
* **Simple Toggle Interface** - Easy-to-use interface for enabling/disabling endpoints
* **Organized by Namespace** - Endpoints grouped by namespace for easy management
* **Security Enhancement** - Reduce your site's attack surface by disabling unused endpoints

= Pro Features =

Upgrade to **REST API Manager Pro** for advanced features:

* **Endpoint Filtering** - Filter endpoints by status, type, and namespace
* **Security Logs** - Track all blocked API requests with detailed logs
* **Export Logs** - Export security logs to CSV for analysis
* **Endpoint Preview** - Preview endpoints with sample data
* **Endpoint Summary** - View statistics and summary of your endpoints
* **All Namespaces** - Manage endpoints from plugins and themes, not just WordPress core
* **Dynamic Endpoints** - Support for dynamic/regex endpoints
* **Priority Support** - Get help when you need it

[Upgrade to Pro](https://wpbuoy.com/plugins/rest-api-manager-pro/)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/rest-api-manager` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings -> API Manager screen to configure the plugin
4. Toggle endpoints on/off as needed

== Frequently Asked Questions ==

= Will disabling endpoints break my site? =

Disabling certain endpoints may affect WordPress functionality, plugins, or themes that depend on the REST API. Always test thoroughly after making changes. We recommend testing on a staging site first.

= What's the difference between free and pro versions? =

The free version allows you to manage WordPress core static endpoints. The Pro version adds advanced features like endpoint filtering, security logs, support for plugin/theme endpoints, dynamic endpoint support, and more.

= Can I manage endpoints from plugins and themes? =

Plugin and theme endpoints are only available in the Pro version. The free version focuses on WordPress core endpoints.

= Does this plugin work with WordPress multisite? =

Yes, but the plugin must be activated on each site individually. Network activation is not currently supported.

== Screenshots ==

1. Main settings page showing WordPress core endpoints organized by namespace
2. Toggle endpoints on/off with a simple switch interface
3. View disabled endpoints count per namespace

== Changelog ==

= 1.0.0 =
* Initial release
* Manage WordPress core REST API endpoints
* Static endpoints support
* Simple toggle interface
* Organized by namespace

== Upgrade Notice ==

= 1.0.0 =
Initial release of REST API Manager.
