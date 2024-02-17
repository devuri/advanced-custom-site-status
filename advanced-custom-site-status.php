<?php

/**
 * Plugin Name:       Advanced Custom Site Status
 * Plugin URI:        https://github.com/devuri/wp-basic-plugin
 * Description:       Plugin bootstrap file.
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


class Advanced_Custom_Health_Check {
    protected $rate_limit = 5; // Max number of requests allowed within the time frame
    protected $lockout_time = 60; // Time frame in seconds
    protected $health_check_slug; // Health check slug, customizable via a constant

    public function __construct() {
        // Use a custom constant if defined, otherwise fallback to 'health-check'
        $this->health_check_slug = defined('CUSTOM_HEALTH_CHECK_SLUG') ? CUSTOM_HEALTH_CHECK_SLUG : 'health-check';

        add_action('init', array($this, 'init'));
        add_filter('query_vars', array($this, 'register_query_var'));
        add_action('template_redirect', array($this, 'rate_limit_health_check'));
    }

    public function init() {
        $this->add_health_check_rewrite_rule();
        $this->flush_rules_on_activation();
    }

    public function add_health_check_rewrite_rule() {
        add_rewrite_rule('^' . $this->health_check_slug . '/?$', 'index.php?custom_health_check=1', 'top');
    }

    public function flush_rules_on_activation() {
        // Ensure the rewrite rules are flushed only on plugin activation
        if (get_option('advanced_custom_health_check_flush_rewrite_rules_flag') === false) {
            flush_rewrite_rules();
            update_option('advanced_custom_health_check_flush_rewrite_rules_flag', true);
        }
    }

    public function register_query_var($vars) {
        $vars[] = 'custom_health_check';
        return $vars;
    }

    public function rate_limit_health_check() {
        if (get_query_var('custom_health_check')) {
            $ip_address = sanitize_key(str_replace('.', '-', $_SERVER['REMOTE_ADDR']));
            $transient_key = 'health_check_' . $ip_address;

            $current_count = get_transient($transient_key);
            if ($current_count === false) {
                set_transient($transient_key, 1, $this->lockout_time);
            } else {
                if ($current_count < $this->rate_limit) {
                    set_transient($transient_key, $current_count + 1, $this->lockout_time);
                } else {
                    status_header(429);
                    die('Rate limit exceeded. Please try again later.');
                }
            }

            // Custom health check logic goes here...
            status_header(200);
            echo json_encode(['status' => 'OK', 'message' => 'Site is up and running.']);
            exit;
        }
    }
}

// Initialize the plugin
new Advanced_Custom_Health_Check();

// Hook for plugin activation to flush rewrite rules
register_activation_hook(__FILE__, 'advanced_custom_health_check_activate');
function advanced_custom_health_check_activate() {
    // Set a flag that rewrite rules need flushing
    update_option('advanced_custom_health_check_flush_rewrite_rules_flag', false);
}

// Hook for plugin deactivation to flush rewrite rules
register_deactivation_hook(__FILE__, 'advanced_custom_health_check_deactivate');
function advanced_custom_health_check_deactivate() {
    flush_rewrite_rules();
    delete_option('advanced_custom_health_check_flush_rewrite_rules_flag');
}
