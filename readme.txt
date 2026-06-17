=== WPBuoy Endpoint Manager ===
Contributors: martincipriano
Tags: rest api, rest api security, disable rest api, endpoint manager, api security
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

View, search, filter, and disable WordPress REST API endpoints. Reduce your attack surface and log blocked requests — no code required.

== Description ==

Every plugin and theme you install registers REST API endpoints. Most are public by default — including the ones your site never uses.

Unused endpoints are unnecessary exposure. They reveal information about your stack, invite probing, and become liabilities when a vulnerability is discovered in a plugin you forgot to audit.

WPBuoy Endpoint Manager gives you a clear view of every endpoint on your site and a one-click toggle to disable the ones you don't need.

**See your full API surface**
Every REST API endpoint from WordPress core, plugins, and themes in one organized view — grouped by namespace, with a count of how many are currently disabled.

**Block endpoints instantly**
Toggle any endpoint off and it returns a 403. No code, no rules, no guesswork. One click. Requires an active Pro license.

**Preview before you block**
Click the preview icon on any static endpoint to fetch its live REST API response in an inline modal — without leaving the admin. Know exactly what you're disabling before you disable it.

**Search and filter your endpoints**
Find any endpoint instantly with keyboard search (Ctrl/Cmd+F) and result highlighting. Filter by status, route type, method, or namespace to focus on what matters.

**Security logging**
Every blocked request is logged with IP address, endpoint, user agent, and timestamp — so you always know what's being probed. Filter logs by IP, endpoint, or date range. Logs auto-clean after 30 days.

**Clean and accessible**
Built to WordPress admin standards. Fully keyboard-navigable with screen reader support.

= Who it's for =

Agencies hardening client sites. Developers locking down staging environments. Site owners running WooCommerce, membership, or any setup where REST API exposure is a real risk.

= Go further with Pro =

WPBuoy Endpoint Manager Pro adds:

* Endpoint blocking with a configurable response code and message (requires license)
* Dynamic route support with regex pattern matching
* Interactive preview modal for dynamic endpoints (auto-resolves default parameter values)
* Global rate limiting — cap the total number of REST API requests per time window
* Per-endpoint rate limiting — set independent limits on individual routes
* IP Block List — manual blocking, auto-block IPs that exceed rate limits, and an allowlist for trusted IPs
* CSV export of security logs
* Automatic plugin updates
* Priority support

[Learn more about Endpoint Manager Pro](https://wpbuoy.com/plugins/endpoint-manager/)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wpbuoy-endpoint-manager` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Endpoints screen in the WordPress admin menu to configure the plugin
4. Toggle endpoints on/off as needed

== Frequently Asked Questions ==

= Will disabling endpoints break my site? =

Disabling certain endpoints may affect WordPress functionality, plugins, or themes that depend on the REST API. Always test thoroughly after making changes. We recommend testing on a staging site first.

= What exactly happens when I disable an endpoint? =

Blocked endpoints return a `403 Forbidden` response. The endpoint remains registered in WordPress — it's not removed, just inaccessible. You can re-enable it at any time from the admin screen.

= Will this affect the WordPress Block Editor (Gutenberg)? =

The Block Editor relies on several `/wp/v2/` REST API routes. Review those endpoints carefully and test on a staging site before disabling any of them.

= Can I manage endpoints from plugins and themes? =

Yes. The plugin shows all registered static REST API endpoints, including those from plugins and themes.

= Does this plugin work with WordPress multisite? =

Yes, but the plugin must be activated on each site individually. Network activation is not currently supported.

= Will this slow down my site? =

No. The plugin adds a minimal check at the REST API permission layer. There is no impact on front-end performance.

= Can I undo changes? =

Yes. All toggles are reversible — just re-enable any endpoint from the admin screen. If you uninstall the plugin, all settings are removed automatically.

= Do I need a license to use this plugin? =

No. Viewing, searching, filtering, previewing endpoints, and reviewing security logs are all available for free. Endpoint blocking and Pro features require an active license.

== Screenshots ==

1. Manage REST API endpoints — toggle routes on or off with HTTP method badges, restricted indicators, and namespace grouping.
2. Search and filter endpoints across all namespaces with live result highlighting.
3. Preview live API responses in an inline modal — auto-resolves dynamic endpoint parameters.
4. Security logs track every blocked request with IP address, endpoint, status code, and user agent.
5. Set per-endpoint rate limits directly from the endpoint row. (Pro)
6. Rate limiting, auto-block, IP allowlist, and customizable error responses. (Pro)
7. Built-in contextual help with links to the knowledge base, FAQs, and support.

== Changelog ==

= 2.0.1 =
* Fixed: unsaved-changes warning no longer triggers when saving the form
* Fixed: upgrade banner primary CTA button background
* Updated: readme short description trimmed to meet WP.org 150-character limit

= 2.0.0 =
* Added: HTTP method badges on endpoint rows
* Added: security logs page with IP, endpoint, status, and user agent tracking
* Added: contextual help tab with KB-linked sections (Getting Started, Features, Troubleshooting)
* Added: upgrade banner for Pro upsell
* Updated: description, short description, FAQs, and Pro feature list
* Updated: screenshots (7 total, including Pro upsell)
* Removed: admin sidebar (replaced by help tab)

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

= 2.0.0 =
Major update — new security logs, help tab, HTTP method badges, and refreshed WP.org listing with Pro upsell screenshots.

= 1.0.1 =
Renamed to WPBuoy Endpoint Manager.
