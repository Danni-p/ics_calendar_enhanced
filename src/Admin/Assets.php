<?php
/**
 * Admin Assets Class
 *
 * @package ICSEnhanced
 */

declare(strict_types=1);

namespace ICSEnhanced\Admin;

/**
 * Assets Class - Handles admin asset enqueuing.
 */
final class Assets {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets( string $hook ): void {
        // Only load on our settings page
        if ( $hook !== 'settings_page_' . Settings_Page::PAGE_SLUG ) {
            return;
        }

        // Enqueue WordPress media uploader
        wp_enqueue_media();

        // Enqueue WordPress color picker
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        // Enqueue admin CSS
        wp_enqueue_style(
            'ics-enhanced-admin',
            ICS_ENHANCED_PLUGIN_URL . 'assets/css/admin.css',
            [ 'wp-color-picker' ],
            ICS_ENHANCED_VERSION
        );

        // Enqueue admin JavaScript
        wp_enqueue_script(
            'ics-enhanced-admin',
            ICS_ENHANCED_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery', 'wp-util', 'wp-color-picker' ],
            ICS_ENHANCED_VERSION,
            true
        );

        // Localize script with translations and settings
        wp_localize_script(
            'ics-enhanced-admin',
            'icsEnhancedAdmin',
            [
                'strings' => [
                    'selectImage'       => __( 'Select Icon', 'ics-calendar-enhanced' ),
                    'useThisImage'      => __( 'Use This Image', 'ics-calendar-enhanced' ),
                    'removeConfirm'     => __( 'Are you sure you want to remove this mapping?', 'ics-calendar-enhanced' ),
                    'noImageSelected'   => __( 'No icon selected', 'ics-calendar-enhanced' ),
                    'selectButton'      => __( 'Select', 'ics-calendar-enhanced' ),
                ],
                'defaultImage'      => ICS_ENHANCED_PLUGIN_URL . 'assets/images/default-fallback.svg',
                'colorPickerOptions' => [
                    'defaultColor' => '',
                    'palettes'     => [
                        '#e74c3c', // Red
                        '#e67e22', // Orange
                        '#f1c40f', // Yellow
                        '#2ecc71', // Green
                        '#1abc9c', // Teal
                        '#3498db', // Blue
                        '#9b59b6', // Purple
                        '#34495e', // Dark gray
                    ],
                ],
            ]
        );
    }
}
