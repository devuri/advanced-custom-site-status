# Advanced Custom Site Status

Advanced Custom Site Status is a WordPress plugin that adds a custom health check endpoint with rate limiting to your WordPress site. This endpoint is specifically designed to check database connectivity, ensuring your WordPress site's database is accessible and responsive. The plugin supports a customizable endpoint slug through a constant, allowing for personalized integration into your WordPress environment.

## Features

- **Custom Health Check Endpoint**: Provides a dedicated URL to check the site's health, focusing on database connectivity.
- **Rate Limiting**: Protects your site from potential abuse by limiting the number of health check requests within a specific time frame.
- **Customizable Endpoint Slug**: Allows the setting of a custom slug for the health check endpoint through a WordPress constant.

## Requirements

- WordPress 5.3.0 or higher
- PHP 7.3.5 or higher

## Installation

### Via Composer

To install the plugin using [Composer](https://getcomposer.org/), run the following command:

```bash
composer require devuri/advanced-custom-site-status
```

Replace `your-vendor` with your actual vendor name on Packagist if you have submitted the plugin there.

### Manual Installation

1. Download the plugin files from the GitHub repository.
2. Upload the `advanced-custom-site-status` directory to your WordPress site's `wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

## Configuration

To customize the endpoint slug, define the `CUSTOM_HEALTH_CHECK_SLUG` constant in your `wp-config.php` file like so:

```php
define('CUSTOM_HEALTH_CHECK_SLUG', 'my-custom-health-endpoint');
```

If the constant is not defined, the plugin will default to using `health-check-site-status` as the endpoint slug.

## Usage

After installation and activation, the health check can be accessed via the configured slug (or the default slug if not configured). For example:

```
https://yourdomain.com/my-custom-health-endpoint
```

This URL will return a JSON response indicating the health status of your WordPress site's database.

## Contributing

Contributions are welcome! Please feel free to submit pull requests or open issues to improve the plugin.

## License

The plugin is licensed under the GPLv2. For more details, see the [License URI](http://www.gnu.org/licenses/gpl-2.0.txt).

