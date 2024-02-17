<?php

/**
 * Plugin Name:       Advanced Custom Site Status
 * Plugin URI:        https://github.com/devuri/advanced-custom-site-status
 * Description:       Adds a custom health check endpoint with rate limiting to your WordPress site. Supports a customizable endpoint slug through a constant.
 * Version:           0.2.6
 * Requires at least: 5.3.0
 * Requires PHP:      7.3.5
 * Author:            uriel
 * Author URI:        https://github.com/devuri
 * Text Domain:       advanced-custom-site-status
 * License:           GPLv2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Network: true.
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
class Advanced_Custom_Health_Check
{
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
    public function __construct( ?string $health_check_slug = null )
    {
        $this->set_health_check_slug( $health_check_slug );
        $this->register_hooks();

        // settings
        add_action('plugins_loaded', function (): void {
            new Health_Check_Slug_Settings( $this->health_check_slug );
        });
    }


    /**
     * Initializes the custom health check functionality.
     *
     * Adds the rewrite rule for the custom health check endpoint.
     */
    public function init(): void
    {
        $this->add_health_check_rewrite_rule();
    }

    /**
     * Adds the rewrite rule for the custom health check endpoint.
     */
    public function add_health_check_rewrite_rule(): void
    {
        add_rewrite_rule('^' . $this->health_check_slug . '/?$', 'index.php?add_custom_health_check=1', 'top');
    }

    /**
     * Registers the custom query variable for the health check.
     *
     * @param array $vars The array of whitelisted query variables.
     *
     * @return array The modified array including the custom health check query variable.
     */
    public function register_query_var($vars)
    {
        $vars[] = 'add_custom_health_check';

        return $vars;
    }

    /**
     * Handles the rate limiting and execution of the health check.
     *
     * Rate limits health check requests based on IP address and performs a database connectivity check.
     */
    public function rate_limit_health_check(): void
    {
        if (get_query_var('add_custom_health_check')) {
            $ip_address = sanitize_key(str_replace('.', '-', $_SERVER['REMOTE_ADDR']));
            $transient_key = 'health_check_' . $ip_address;

            $current_count = get_transient($transient_key);
            if (false === $current_count) {
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

            // the connectivity check is okay.
            status_header(200);
            echo json_encode(['status' => 'OK', 'message' => 'Site is up and running.']);
            exit;
        }
    }

    protected function get_health_check_slug( bool $encoded_slug =  true ): string
    {
        if ( true ===  $encoded_slug ) {
            return md5( $this->health_check_slug );
        }

        return $this->health_check_slug;
    }

    private function set_health_check_slug( ?string $health_check_slug ): void
    {
        if (\defined('CUSTOM_HEALTH_CHECK_SLUG') && ! empty(CUSTOM_HEALTH_CHECK_SLUG)) {
            $this->health_check_slug = CUSTOM_HEALTH_CHECK_SLUG;
        } elseif ( ! empty($health_check_slug)) {
            $this->health_check_slug = sanitize_key($health_check_slug);
        } else {
            $this->health_check_slug = 'health-check-site-status';
        }

        if (get_option('ahc_healthcheck_slug_hash')) {
            $this->health_check_slug = md5($this->health_check_slug);
        }
    }

    private function register_hooks(): void
    {
        add_action('init', [$this, 'init']);
        add_filter('query_vars', [$this, 'register_query_var']);
        add_action('template_redirect', [$this, 'rate_limit_health_check']);
    }
}


class Health_Check_Slug_Settings
{
    protected $healthcheck_slug;

    public function __construct( ?string $healthcheck_slug = null )
    {
        $this->healthcheck_slug = $healthcheck_slug;
        add_action('admin_menu', [$this, 'add_settings_submenu']);
        add_action('admin_init', [$this, 'initialize_settings']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_action_links']);
    }

    public function add_action_links($links)
    {
        $settings_link = '<a href="options-general.php?page=health-check-slug-settings">Settings</a>';
        array_unshift($links, $settings_link);

        return $links;
    }

    public function add_settings_submenu(): void
    {
        add_submenu_page(
            'options-general.php', // Parent slug
            'Health Check Slug Settings', // Page title
            'Health Check Slug', // Menu title
            'manage_options', // Capability
            'health-check-slug-settings', // Menu slug
            [$this, 'settings_page_content'] // Callback function for the page content
        );
    }

    public function settings_page_content(): void
    {
        ?>
        <div class="wrap">
            <h2>Health Check Slug Settings</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('health-check-slug-settings-group');
        do_settings_sections('health-check-slug-settings');
        echo "Health Check Slug: " . home_url("/{$this->healthcheck_slug}");
        echo '<p/>To apply changes to the URL, it\'s necessary to refresh the rewrite rules.<br/> This can be accomplished effortlessly by navigating to the Permalinks settings in your WordPress dashboard <br/> and clicking the "Save Changes" button, even if you don\'t make any modifications to the permalink structure itself.';
        submit_button();
        ?>
            </form>
        </div>
        <?php
    }

    public function initialize_settings(): void
    {
        register_setting(
            'health-check-slug-settings-group',
            'ahc_healthcheck_slug',
            ['sanitize_callback' => 'sanitize_text_field']
        );

        register_setting(
            'health-check-slug-settings-group',
            'ahc_healthcheck_slug_hash',
            ['sanitize_callback' => 'sanitize_text_field']
        );

        add_settings_section(
            'health-check-slug-settings-section',
            'Health Check Settings',
            null,
            'health-check-slug-settings'
        );

        add_settings_field(
            'health-check-slug',
            'Health Check Slug',
            [$this, 'healthcheck_slug_field_callback'],
            'health-check-slug-settings',
            'health-check-slug-settings-section'
        );

        add_settings_field(
            'health-check-slug-hash',
            'Apply MD5 Hash',
            [$this, 'healthcheck_slug_hash_field_callback'],
            'health-check-slug-settings',
            'health-check-slug-settings-section'
        );
    }

    public function healthcheck_slug_field_callback(): void
    {
        $ahc_healthcheck_slug = sanitize_key(get_option('ahc_healthcheck_slug'));
        echo '<input type="text" id="ahc_healthcheck_slug" name="ahc_healthcheck_slug" value="' . esc_attr($ahc_healthcheck_slug) . '" />';
    }

    public function healthcheck_slug_hash_field_callback(): void
    {
        $ahc_healthcheck_slug_hash = get_option('ahc_healthcheck_slug_hash');
        echo '<input type="checkbox" id="ahc_healthcheck_slug_hash" name="ahc_healthcheck_slug_hash" value="1" ' . checked(1, $ahc_healthcheck_slug_hash, false) . '/>';
    }
}

// Initialize the plugin
new Advanced_Custom_Health_Check( get_option('ahc_healthcheck_slug', null ) );

register_activation_hook(__FILE__, function (): void {
    flush_rewrite_rules();
});


register_deactivation_hook(__FILE__, function (): void {
    flush_rewrite_rules();
});
