<?php
/**
 * Plugin Name:       ICS Calendar Enhanced
 * Plugin URI:        https://example.com/plugins/ics-calendar-enhanced
 * Description:       Extends the ICS Calendar plugin with category icons mapping. Allows users to assign icons to calendar event categories through an intuitive admin interface.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Daniel
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ics-calendar-enhanced
 * Domain Path:       /languages
 */

declare(strict_types=1);

namespace ICSEnhanced;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin version
define( 'ICS_ENHANCED_VERSION', '1.0.0' );

// Plugin paths
define( 'ICS_ENHANCED_PLUGIN_FILE', __FILE__ );
define( 'ICS_ENHANCED_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ICS_ENHANCED_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ICS_ENHANCED_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load simple autoloader
require_once ICS_ENHANCED_PLUGIN_DIR . 'src/autoloader.php';

// Register activation hook
register_activation_hook( __FILE__, [ Core\Activator::class, 'activate' ] );

// Register deactivation hook
register_deactivation_hook( __FILE__, [ Core\Deactivator::class, 'deactivate' ] );

/**
 * Initialize the plugin on plugins_loaded hook.
 * This ensures all plugins are loaded before we check dependencies.
 */
add_action( 'plugins_loaded', static function (): void {
    // Initialize the main plugin class
    Core\Plugin::get_instance();
}, 10 );

