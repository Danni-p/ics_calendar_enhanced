<?php
/**
 * Frontend Assets Class
 *
 * @package ICSEnhanced
 */

declare(strict_types=1);

namespace ICSEnhanced\Frontend;

use ICSEnhanced\Includes\Helpers;
use ICSEnhanced\Admin\Category_Mapper;

/**
 * Assets Class - Handles frontend asset enqueuing.
 */
final class Assets {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueue_assets(): void {
        // Only enqueue if ICS Calendar content is likely present
        // We use a late priority to check after other plugins have set up
        if ( ! $this->should_enqueue() ) {
            return;
        }

        // Enqueue frontend CSS
        wp_enqueue_style(
            'ics-enhanced-public',
            ICS_ENHANCED_PLUGIN_URL . 'assets/css/public.css',
            [],
            ICS_ENHANCED_VERSION
        );

        // Enqueue frontend JavaScript
        wp_enqueue_script(
            'ics-enhanced-public',
            ICS_ENHANCED_PLUGIN_URL . 'assets/js/public.js',
            [],
            ICS_ENHANCED_VERSION,
            true
        );

        // Localize script with data for JavaScript fallback injection
        wp_localize_script(
            'ics-enhanced-public',
            'icsEnhancedData',
            $this->get_localized_data()
        );
    }

    /**
     * Get data to pass to JavaScript.
     *
     * @return array Localized data array.
     */
    private function get_localized_data(): array {
        // Get all category mappings (new format with image_id and color)
        $mappings = Category_Mapper::get_mappings();
        $category_images = [];
        $category_colors = [];

        foreach ( $mappings as $category => $data ) {
            // Handle image mappings
            $image_id = $data['image_id'] ?? 0;
            if ( $image_id > 0 ) {
                $url = Helpers::get_image_url( (int) $image_id, 'thumbnail' );
                if ( ! empty( $url ) ) {
                    $category_images[ $category ] = $url;
                }
            }

            // Handle color mappings
            $color = $data['color'] ?? '';
            if ( ! empty( $color ) ) {
                $category_colors[ $category ] = $color;
            }
        }

        // Get fallback image URL
        $fallback_id = Category_Mapper::get_general_fallback();
        $fallback_url = '';
        if ( $fallback_id > 0 ) {
            $fallback_url = Helpers::get_image_url( $fallback_id, 'thumbnail' );
        }

        // If no user-defined fallback, use bundled default
        if ( empty( $fallback_url ) ) {
            $fallback_url = Helpers::get_bundled_default_image();
        }

        return [
            'fallbackImage'   => $fallback_url,
            'categoryImages'  => $category_images,
            'categoryColors'  => $category_colors,
            'imageClass'      => 'ics-enhanced-event-category-image',
            'wrapperClass'    => 'ics-enhanced-event-wrapper',
            'selectors'       => [
                // ICS Calendar event selectors
                'eventTitle'   => '.r34ics .title, .ics-calendar .title, .r34ics_event .title',
                'eventItem'    => '.r34ics .event, .ics-calendar .event, .r34ics_event',
                'eventDate'    => '.ics-calendar-date',
                'eventWrapper' => '.event[data-categories]',
            ],
            'colorSettings'   => [
                'borderWidth'       => '3px',
                'backgroundOpacity' => 0.15,
            ],
            'debug'           => defined( 'WP_DEBUG' ) && WP_DEBUG,
        ];
    }

    /**
     * Determine if assets should be enqueued.
     *
     * @return bool Whether to enqueue assets.
     */
    private function should_enqueue(): bool {
        // Always enqueue for now - could add smarter detection later
        // based on shortcode presence or widget usage

        // Check for ICS Calendar shortcodes in content
        global $post;
        if ( $post instanceof \WP_Post ) {
            $content = $post->post_content;
            
            // Check for common ICS Calendar shortcodes
            $shortcodes = [ 'ics_calendar', 'ics-calendar', 'ics_category_image' ];
            foreach ( $shortcodes as $shortcode ) {
                if ( has_shortcode( $content, $shortcode ) ) {
                    return true;
                }
            }
        }

        // Check if any ICS Calendar widget is active
        if ( is_active_widget( false, false, 'ics_calendar_widget', true ) ) {
            return true;
        }

        // Allow themes/plugins to force enqueue
        return (bool) apply_filters( 'ics_enhanced_force_enqueue_assets', false );
    }
}
