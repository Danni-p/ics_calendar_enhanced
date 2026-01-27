<?php
/**
 * Main Plugin Class
 *
 * @package ICSEnhanced
 */

declare(strict_types=1);

namespace ICSEnhanced\Core;

use ICSEnhanced\Admin\Settings_Page;
use ICSEnhanced\Admin\Assets as Admin_Assets;
use ICSEnhanced\Frontend\Display;
use ICSEnhanced\Frontend\Assets as Frontend_Assets;

/**
 * Plugin Class - Singleton pattern for main plugin initialization.
 */
final class Plugin {

    /**
     * Plugin instance.
     */
    private static ?Plugin $instance = null;

    /**
     * Flag to track if dependency is met.
     */
    private bool $dependency_met = false;

    /**
     * Get plugin instance.
     *
     * @return Plugin
     */
    public static function get_instance(): Plugin {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct() {
        $this->check_dependencies();

        if ( $this->dependency_met ) {
            $this->init();
        }
    }

    /**
     * Check if required dependencies are met.
     */
    private function check_dependencies(): void {
        // Include plugin.php if not loaded (needed for is_plugin_active)
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Check if ICS Calendar plugin is active
        $ics_calendar_active = is_plugin_active( 'ics-calendar/ics-calendar.php' ) 
            || is_plugin_active( 'ics-calendar-pro/ics-calendar-pro.php' )
            || class_exists( 'ICS_Calendar' )
            || class_exists( 'R34ICS' );

        if ( ! $ics_calendar_active ) {
            add_action( 'admin_notices', [ $this, 'display_dependency_notice' ] );
            $this->dependency_met = false;
            return;
        }

        $this->dependency_met = true;
    }

    /**
     * Display admin notice when ICS Calendar is not active.
     */
    public function display_dependency_notice(): void {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'ICS Calendar Enhanced', 'ics-calendar-enhanced' ); ?>:</strong>
                <?php esc_html_e( 'This plugin requires the ICS Calendar plugin to be installed and activated.', 'ics-calendar-enhanced' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Initialize the plugin.
     */
    private function init(): void {
        // Load text domain for translations
        add_action( 'init', [ $this, 'load_textdomain' ] );

        // Initialize admin components
        if ( is_admin() ) {
            $this->init_admin();
        }

        // Initialize frontend components
        $this->init_frontend();
    }

    /**
     * Load plugin text domain for translations.
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'ics-calendar-enhanced',
            false,
            dirname( ICS_ENHANCED_PLUGIN_BASENAME ) . '/languages/'
        );
    }

    /**
     * Initialize admin components.
     */
    private function init_admin(): void {
        new Settings_Page();
        new Admin_Assets();
    }

    /**
     * Initialize frontend components.
     */
    private function init_frontend(): void {
        new Display();
        new Frontend_Assets();
    }

    /**
     * Prevent cloning.
     */
    private function __clone() {}

    /**
     * Prevent unserialization.
     *
     * @throws \Exception Always throws exception.
     */
    public function __wakeup(): void {
        throw new \Exception( 'Cannot unserialize singleton.' );
    }
}

