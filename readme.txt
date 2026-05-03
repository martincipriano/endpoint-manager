=== WPBuoy Endpoint Manager ===
Contributors: martincipriano
Tags: rest api, security, api, disable endpoints, rest api security
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage and block REST API endpoints to enhance your site's security and performance.

== Description ==

WPBuoy Endpoint Manager lets you manage and block REST API endpoints from WordPress core, plugins, and themes. Easily enable or disable specific endpoints to reduce your site's attack surface and improve performance.

= Features =

* **Manage All Static Endpoints** - Control access to REST API endpoints from WordPress core, plugins, and themes
* **Simple Toggle Interface** - Easy-to-use interface for enabling/disabling endpoints
* **Organized by Namespace** - Endpoints grouped by namespace for easy management
* **Security Enhancement** - Reduce your site's attack surface by disabling unused endpoints

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wpbyem` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Endpoints screen in the WordPress admin menu to configure the plugin
4. Toggle endpoints on/off as needed

== Frequently Asked Questions ==

= Will disabling endpoints break my site? =

Disabling certain endpoints may affect WordPress functionality, plugins, or themes that depend on the REST API. Always test thoroughly after making changes. We recommend testing on a staging site first.

= Can I manage endpoints from plugins and themes? =

Yes. The plugin shows all registered static REST API endpoints, including those from plugins and themes.

= Does this plugin work with WordPress multisite? =

Yes, but the plugin must be activated on each site individually. Network activation is not currently supported.

== Screenshots ==

1. Main settings page showing REST API endpoints organized by namespace
2. Toggle endpoints on/off with a simple switch interface
3. View disabled endpoints count per namespace

== Changelog ==

= 1.0.6 =
* Updated: standardized admin sidebar with shared WPBuoy styling

= 1.0.5 =
* Added: endpoint preview button for static routes
* Removed: "Endpoint Preview" from pro upsell (basic preview now free)

= 1.0.4 =
* Updated: standardized admin sidebar styling
* Removed: FAQ accordion widget from sidebar
* Fixed: removed unprefixed global JavaScript functions

= 1.0.3 =
* Added: support for all registered REST API namespaces including plugins and themes
* Fixed: endpoint sanitization uses sanitize_text_field()

= 1.0.2 =
* Fixed: sanitize POST input at point of reading
* Updated: plugin scoped to WordPress core static endpoints
* Updated: sidebar links and added FAQ widget
* Updated: tested up to WordPress 6.9

= 1.0.1 =
* Renamed to WPBuoy Endpoint Manager for clarity and uniqueness.

= 1.0.0 =
* Initial release
* Manage WordPress core REST API endpoints
* Static endpoints support
* Simple toggle interface
* Organized by namespace

== Upgrade Notice ==

= 1.0.1 =
Renamed to WPBuoy Endpoint Manager.
