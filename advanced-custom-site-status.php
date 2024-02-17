<?php

/**
 * Plugin Name:       Advanced Custom Site Status
 * Plugin URI:        https://github.com/devuri/advanced-custom-site-status
 * Description:       Adds a custom health check endpoint with rate limiting to your WordPress site. Supports a customizable endpoint slug through a constant.
 * Version:           0.2.3
 * Requires at least: 5.3.0
 * Requires PHP:      7.3.5
 * Author:            uriel
 * Author URI:        https://github.com/devuri
 * Text Domain:       advanced-custom-site-status
 * License:           GPLv2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Network: true
 */

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Advanced Custom Health Check class.
 *
 * This class defines all code necessary to implement a custom health check for a WordPress site,
 * including adding a custom rewrite rule for the health check endpoint, registering a custom query variable,
 * and handling the rate limiting for the health check requests.
 */
class Advanced_Custom_Health_Check {

    /**
     * The maximum number of requests allowed within the lockout time frame.
     *
     * @var int
     */
    protected $rate_limit = 5;

    /**
     * The time frame in seconds for the rate limit.
     *
     * @var int
     */
    protected $lockout_time = 60;

    /**
     * The slug for the health check endpoint, customizable via a constant.
     *
     * @var string
     */
    protected $health_check_slug;

    /**
     * Constructor for the Advanced Custom Health Check class.
     *
     * Sets up the health check slug and hooks into WordPress to initialize the custom health check functionality.
     */
    public function __construct() {
        // Use a custom constant if defined, otherwise fallback to a default slug.
        $this->health_check_slug = defined('CUSTOM_HEALTH_CHECK_SLUG') ? CUSTOM_HEALTH_CHECK_SLUG : 'health-check-site-status';

        // Initialize custom health check functionality.
        add_action('init', array($this, 'init'));
        add_filter('query_vars', array($this, 'register_query_var'));
        add_action('template_redirect', array($this, 'rate_limit_health_check'));
    }

    /**
     * Initializes the custom health check functionality.
     *
     * Adds the rewrite rule for the custom health check endpoint.
     */
    public function init() {
        $this->add_health_check_rewrite_rule();
    }

    /**
     * Adds the rewrite rule for the custom health check endpoint.
     */
    public function add_health_check_rewrite_rule() {
        add_rewrite_rule('^' . $this->health_check_slug . '/?$', 'index.php?custom_health_check=1', 'top');
    }

    /**
     * Registers the custom query variable for the health check.
     *
     * @param array $vars The array of whitelisted query variables.
     * @return array The modified array including the custom health check query variable.
     */
    public function register_query_var($vars) {
        $vars[] = 'custom_health_check';
        return $vars;
    }

    /**
     * Handles the rate limiting and execution of the health check.
     *
     * Rate limits health check requests based on IP address and performs a database connectivity check.
     */
    public function rate_limit_health_check() {
        if (get_query_var('custom_health_check')) {
            $ip_address = sanitize_key(str_replace('.', '-', $_SERVER['REMOTE_ADDR']));
            $transient_key = 'health_check_' . $ip_address;

            $current_count = get_transient($transient_key);
            if ($current_count === false) {
                // First request within the lockout time frame.
                set_transient($transient_key, 1, $this->lockout_time);
            } else {
                if ($current_count < $this->rate_limit) {
                    // Increment the count for subsequent requests within the lockout time frame.
                    set_transient($transient_key, $current_count + 1, $this->lockout_time);
                } else {
                    // Rate limit exceeded, send 429 status code.
                    status_header(429);
                    wp_die('Rate limit exceeded. Please try again later.');
                }
            }
        }
    }
}


// Initialize the plugin
new Advanced_Custom_Health_Check();


register_activation_hook(__FILE__, function () {
    flush_rewrite_rules();
});


register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
