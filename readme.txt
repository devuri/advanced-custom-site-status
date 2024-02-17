=== Advanced Custom Site Status ===
Contributors: icelayer
Tags: health-check, database, wordpress, site-status, monitoring
Requires at least: 5.3.0
Tested up to: 6.4
Requires PHP: 7.3.5
Stable tag: 0.2.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds a custom health check endpoint with rate limiting to your WordPress site, specifically checking database connectivity. Supports a customizable endpoint slug through a constant.

== Description ==

Advanced Custom Site Status is a WordPress plugin that enhances your site's health monitoring capabilities. It introduces a dedicated endpoint for health checks, focusing particularly on database connectivity, ensuring your WordPress installation is always communicating effectively with its database. With built-in rate limiting, the plugin protects your site from potential abuse, making health checks both secure and reliable.

**Features Include:**

- **Custom Health Check Endpoint:** A specific URL is provided to assess the site's health, with a primary focus on database connectivity.
- **Rate Limiting:** Safeguards against abuse by restricting the frequency of health check requests.
- **Customizable Endpoint Slug:** Offers the ability to personalize the endpoint slug via a WordPress-defined constant, allowing for seamless integration into any environment.

== Installation ==

1. Upload the `advanced-custom-site-status` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Optionally, define a custom slug by adding `define('CUSTOM_HEALTH_CHECK_SLUG', 'your-custom-slug');` to your `wp-config.php` file. If not defined, the default slug `health-check-site-status` will be used.

== Frequently Asked Questions ==

= Can I change the endpoint slug for the health check? =

Yes, you can define the `CUSTOM_HEALTH_CHECK_SLUG` constant in your `wp-config.php` to set a custom slug for the health check endpoint.

= Is there rate limiting built into the health check endpoint? =

Yes, to protect your site, the plugin implements rate limiting, restricting the number of health check requests within a specified timeframe.

== Changelog ==

= 0.2.3 =
* Initial release.

== Upgrade Notice ==

= 0.2.3 =
Initial release. Please see the description for full details.
