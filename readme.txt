=== WPBuoy Endpoint Manager ===
Contributors: martincipriano
Tags: rest api, security, api, disable endpoints, rest api security
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

See every REST API endpoint your WordPress site exposes — and disable the ones you don't need. Reduce your attack surface in minutes.

== Description ==

Every plugin and theme you install registers REST API endpoints. Most are public by default — including the ones your site never uses.

Unused endpoints are unnecessary exposure. They reveal information about your stack, invite probing, and become liabilities when a vulnerability is discovered in a plugin you forgot to audit.

WPBuoy Endpoint Manager gives you a clear view of every endpoint on your site and a one-click toggle to disable the ones you don't need.

**See your full API surface**
Every endpoint from WordPress core, plugins, and themes in one organized view — grouped by namespace, with a count of how many are currently disabled.

**Block endpoints instantly**
Toggle any endpoint off and it returns a 403. No code, no rules, no guesswork. One click.

**Preview before you block**
Open any endpoint's live response in a new tab before making changes. Know exactly what you're disabling.

**Clean and accessible**
Built to WordPress admin standards. Fully keyboard-navigable with screen reader support.

= Who it's for =

Agencies hardening client sites. Developers locking down staging environments. Site owners running WooCommerce, membership, or any setup where API exposure is a real risk.

= Go further with Pro =

WPBuoy Endpoint Manager Pro adds:

* Advanced search with keyboard shortcut and result highlighting
* Multi-criteria filtering by status, route type, and namespace
* Dynamic route support with regex pattern matching
* Security log — IP address, endpoint, user agent, timestamp
* CSV export and automatic 30-day log cleanup
* License management and automatic updates

[Learn more about Endpoint Manager Pro](https://wpbuoy.com/plugins/endpoint-manager/)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wpbuoy-endpoint-manager` directory, or install the plugin through the WordPress plugins screen directly.
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

= 1.1.4 =
* Updated: WP.org listing copy — rewritten description, short description, and Pro upsell section
* Fixed: incorrect installation directory path in readme.txt

= 1.1.3 =
* Updated: tested up to WordPress 6.9

= 1.1.2 =
* Fixed: private constructor enforces singleton pattern
* Fixed: text domain loaded via init hook to prevent early-load notices
* Fixed: comprehensive uninstall cleanup (options, transients, multisite)
* Fixed: accessibility improvements (screen-reader-text, rel attributes)

= 1.1.1 =
* Updated: sidebar "Upgrade to Pro" features list updated to match current pro feature set.

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
