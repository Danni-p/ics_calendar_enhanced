<?php
/**
 * Frontend Display Class
 *
 * @package ICSEnhanced
 */

declare(strict_types=1);

namespace ICSEnhanced\Frontend;

use ICSEnhanced\Admin\Category_Mapper;
use ICSEnhanced\Core\Activator;
use ICSEnhanced\Includes\Helpers;

/**
 * Display Class - Handles frontend category image display.
 */
final class Display {

    /**
     * Constructor.
     */
    public function __construct() {
        // Hook into ICS Calendar filters if available
        $this->register_ics_calendar_hooks();

        // Register shortcode for manual image display
        add_shortcode( 'ics_category_image', [ $this, 'shortcode_category_image' ] );

        // Add filter for themes to use
        add_filter( 'ics_enhanced_get_category_image', [ $this, 'filter_get_category_image' ], 10, 3 );
    }

    /**
     * Register hooks for ICS Calendar integration.
     *
     * Uses actual ICS Calendar hooks that exist:
     * - r34ics_event_label_html_filter: Filters the event title/label HTML
     * - r34ics_display_calendar_event_item: Filters event data before rendering
     * - r34ics_event_label_html: Action hook to inject content into event label
     * - r34ics_display_calendar_before_wrapper: Action hook to inject content before calendar
     */
    private function register_ics_calendar_hooks(): void {
        // Primary filter: Modify event label HTML to prepend image
        add_filter( 'r34ics_event_label_html_filter', [ $this, 'filter_event_label_html' ], 10, 4 );

        // Secondary filter: Add image data to event item for later use
        add_filter( 'r34ics_display_calendar_event_item', [ $this, 'filter_event_item' ], 10, 3 );

        // Action hooks: Can be used to inject content at specific positions
        add_action( 'r34ics_event_label_html', [ $this, 'action_event_label_html' ], 10, 3 );
        add_action( 'r34ics_event_description_html', [ $this, 'action_event_description_html' ], 10, 4 );

        // Display color legend above calendar
        add_action( 'r34ics_display_calendar_before_wrapper', [ $this, 'render_color_legend' ], 10, 3 );

        // Add "Location:" prefix before location content in event descriptions
        add_filter( 'r34ics_event_description_html_filter', [ $this, 'filter_event_description_location_prefix' ], 10, 5 );
    }

    /**
     * Filter event label HTML to prepend category image.
     *
     * This is the primary method for adding images to events.
     * Uses the r34ics_event_label_html_filter which exists in ICS Calendar.
     *
     * @param string            $title_content Event title/label HTML.
     * @param array             $args          Shortcode arguments.
     * @param array|object      $event         Event data (can be R34ICS_ICal\Event object or array).
     * @param array|null        $classes       CSS classes (may be null).
     * @return string Modified HTML with image prepended.
     */
    public function filter_event_label_html( string $title_content, array $args, $event, ?array $classes ): string {
        // $event is already the processed event_item array with dtstart_date
        if ( ! is_array( $event ) ) {
            return $title_content;
        }

        $category = $this->extract_category_from_event( $event );
        $category = $category ?: '';

        $image_html = Helpers::get_category_image_html( $category, 'thumbnail', [
            'class' => 'ics-enhanced-event-category-image',
        ] );

        // Compute dynamic subtitle based on days until event start (only if enabled in settings)
        $subtitle_text = '';
        if ( (bool) get_option( Activator::OPTION_SHOW_COUNTDOWN_SUBLINE, true ) ) {
            $subtitle_text = $this->get_event_subtitle_text( $event );
        }
        $subtitle_html = '' !== $subtitle_text
            ? '<span class="ics-enhanced-event-subtitle">' . esc_html( $subtitle_text ) . '</span>'
            : '';

        // If no image and no subtitle, return original content unchanged
        if ( empty( $image_html ) && empty( $subtitle_html ) ) {
            return $title_content;
        }

        // Build media HTML (only if we have an image)
        $media_html = ! empty( $image_html )
            ? '<span class="ics-enhanced-event-media">' . $image_html . '</span>'
            : '';

        // Wrap the entire label with optional image + title + optional subtitle.
        // Note: $title_content may contain HTML created by ICS Calendar (e.g. links/spans), so we keep it as-is.
        return '<span class="ics-enhanced-event-wrapper">'
            . $media_html
            . '<span class="ics-enhanced-event-text">'
                . '<span class="ics-enhanced-event-title">' . $title_content . '</span>'
                . $subtitle_html
            . '</span>'
        . '</span>';
    }

    /**
     * Filter event item data to add category image information.
     *
     * Uses r34ics_display_calendar_event_item filter to enrich event data
     * with image URL and HTML for potential use in templates or JS.
     *
     * @param array        $event_item Event item array being built.
     * @param array|object $event      Original event data (can be R34ICS_ICal\Event object or array).
     * @param array        $args       Shortcode arguments.
     * @return array Modified event item with image data.
     */
    public function filter_event_item( array $event_item, $event, array $args ): array {
        // Convert event object to array if needed
        $event_array = $this->event_to_array( $event );
        $category = $this->extract_category_from_event( $event_array );

        // Always add image data (using fallback if no category)
        $category_for_image = $category ?: '';
        $event_item['category_image_url'] = Helpers::get_category_image( $category_for_image );
        $event_item['category_image_html'] = Helpers::get_category_image_html( $category_for_image, 'thumbnail', [
            'class' => 'ics-enhanced-event-category-image',
        ] );
        $event_item['category'] = $category;

        return $event_item;
    }

    /**
     * Action hook to inject content into event label area.
     *
     * This action fires inside the event label element, after the title text.
     * Can be used as an alternative method to add images.
     *
     * @param array             $args    Shortcode arguments.
     * @param array|object      $event   Event data (can be R34ICS_ICal\Event object or array).
     * @param array|null        $classes CSS classes (may be null).
     */
    public function action_event_label_html( array $args, $event, ?array $classes ): void {
        // Note: This action fires INSIDE the title element, after the title text
        // The filter method (filter_event_label_html) is preferred as it gives more control
        // This is left as a hook point for future use or alternative approaches
    }

    /**
     * Action hook to inject content into event description area.
     *
     * This action fires inside the description element.
     *
     * @param array             $args     Shortcode arguments.
     * @param array|object      $event    Event data (can be R34ICS_ICal\Event object or array).
     * @param array|null        $classes  CSS classes (may be null).
     * @param bool              $has_desc Whether event has description.
     */
    public function action_event_description_html( array $args, $event, ?array $classes, bool $has_desc ): void {
        // Alternative injection point - can output image in description area if needed
        // Currently not used, but available for customization
    }

    /**
     * Render the color legend above the calendar.
     *
     * Displays a horizontal list of category colors with their names.
     * Only renders if at least one color mapping exists.
     *
     * @param string $view     Calendar view type (month, list, week, basic).
     * @param array  $args     Shortcode arguments.
     * @param array  $ics_data Calendar data.
     */
    public function render_color_legend( string $view, array $args, array $ics_data ): void {
        $color_mappings = Category_Mapper::get_color_mappings();

        // Don't render if no color mappings exist
        if ( empty( $color_mappings ) ) {
            return;
        }

        // Use order from admin (same as Category Mappings table; do not ksort).
        ?>
        <div class="ics-enhanced-color-legend">
            <span class="ics-enhanced-legend-title"><?php esc_html_e( 'Legend', 'ics-calendar-enhanced' ); ?></span>
            <ul class="ics-enhanced-legend-list">
                <?php foreach ( $color_mappings as $category => $color ) : ?>
                    <?php
                    $icon_html = '';
                    $image_id  = Category_Mapper::get_image_for_category( $category );
                    if ( $image_id !== null && $image_id > 0 ) {
                        $icon_html = Helpers::get_category_image_html( $category, 'thumbnail', [
                            'class' => 'ics-enhanced-legend-icon',
                        ] );
                    }
                    ?>
                    <li class="ics-enhanced-legend-item">
                        <?php
                        $legend_bg = Helpers::hex_to_rgba( $color, 0.15 );
                        $legend_style = 'border-color: ' . esc_attr( $color ) . ';';
                        if ( $legend_bg !== '' ) {
                            $legend_style .= ' background-color: ' . esc_attr( $legend_bg ) . ';';
                        }
                        ?>
                        <span class="ics-enhanced-legend-color" style="<?php echo $legend_style; ?>">
                            <?php if ( $icon_html !== '' ) : ?>
                                <?php echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML from get_category_image_html with escaped attributes ?>
                            <?php endif; ?>
                        </span>
                        <span class="ics-enhanced-legend-label"><?php echo esc_html( $category ); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Add a localized "Location:" prefix before the content of each .location div in event descriptions.
     *
     * @param string $descloc_content Event description HTML.
     * @param array  $args            Shortcode arguments.
     * @param array  $event           Event data.
     * @param array  $classes         CSS classes.
     * @param bool   $has_desc        Whether event has description.
     * @return string Modified HTML.
     */
    public function filter_event_description_location_prefix( string $descloc_content, array $args, $event, $classes, $has_desc ): string {
        $prefix = '<span class="ics-enhanced-location-prefix">' . esc_html__( 'Location:', 'ics-calendar-enhanced' ) . ' </span>';
        return str_replace( '<div class="location">', '<div class="location">' . $prefix, $descloc_content );
    }

    /**
     * Convert an event object to an array.
     *
     * ICS Calendar may pass R34ICS_ICal\Event objects instead of arrays.
     *
     * @param array|object $event Event data (object or array).
     * @return array Event as array.
     */
    private function event_to_array( $event ): array {
        if ( is_array( $event ) ) {
            return $event;
        }

        if ( is_object( $event ) ) {
            // Try to convert object to array
            // R34ICS_ICal\Event objects have public properties we can access
            $result = [];

            // Try get_object_vars for public properties
            $vars = get_object_vars( $event );
            if ( ! empty( $vars ) ) {
                return $vars;
            }

            // Try casting to array
            $result = (array) $event;
            if ( ! empty( $result ) ) {
                // Clean up keys (remove class name prefix from protected/private properties)
                $cleaned = [];
                foreach ( $result as $key => $value ) {
                    // Remove null bytes and class name prefix from protected/private properties
                    $clean_key = preg_replace( '/^\x00.*\x00/', '', $key );
                    $cleaned[ $clean_key ] = $value;
                }
                return $cleaned;
            }

            // Try to access common properties directly
            $common_props = [ 'categories', 'category', 'CATEGORIES', 'summary', 'description', 'dtstart', 'dtend', 'uid' ];
            foreach ( $common_props as $prop ) {
                if ( isset( $event->$prop ) ) {
                    $result[ $prop ] = $event->$prop;
                }
            }

            return $result;
        }

        return [];
    }

    /**
     * Extract category from event data.
     *
     * ICS Calendar uses 'categories' field which may contain comma-separated values.
     * Now checks all categories to find one with a mapping (image or color).
     *
     * @param array $event Event data.
     * @return string Category string or empty.
     */
    private function extract_category_from_event( array $event ): string {
        // ICS Calendar standard field names
        $category_fields = [
            'categories',      // Standard ICS field used by ICS Calendar
            'category',        // Alternative singular form
            'CATEGORIES',      // Raw ICS format (uppercase)
            'CATEGORY',        // Alternative uppercase
            'event_category',  // WordPress-style field
            'cal_category',    // Calendar-specific field
        ];

        foreach ( $category_fields as $field ) {
            if ( ! empty( $event[ $field ] ) ) {
                $category = $event[ $field ];

                // Handle array of categories
                if ( is_array( $category ) ) {
                    $category_list = array_filter( array_map( 'trim', $category ) );
                } else {
                    // Handle comma-separated categories
                    $category_list = array_filter( array_map( 'trim', explode( ',', (string) $category ) ) );
                }

                if ( empty( $category_list ) ) {
                    continue;
                }

                // Check each category to find one with a mapping (image or color)
                foreach ( $category_list as $cat ) {
                    if ( ! empty( $cat ) && Category_Mapper::has_mapping( $cat ) ) {
                        return $cat; // Return first category with a mapping
                    }
                }

                // If no mapping found, return first category as fallback
                return reset( $category_list );
            }
        }

        return '';
    }

    /**
     * Get the subtitle text based on days until event start.
     *
     * - Future events: "Noch X Tage"
     * - Today: "Findet heute statt"
     * - Past events: empty string
     *
     * @param array $event Event data array (already processed by ICS Calendar).
     * @return string Subtitle text or empty string.
     */
    private function get_event_subtitle_text( array $event ): string {
        // ICS Calendar provides dtstart_date in Ymd format (e.g., "20260601")
        if ( empty( $event['dtstart_date'] ) ) {
            return '';
        }

        $dtstart_date = $event['dtstart_date'];

        // Use site timezone for accurate day comparison
        $tz = wp_timezone();

        // Parse Ymd format (e.g., "20260601") into DateTime
        $event_date = \DateTime::createFromFormat( 'Ymd', $dtstart_date, $tz );
        if ( false === $event_date ) {
            return '';
        }

        // Set to start of day for accurate day comparison
        $event_date->setTime( 0, 0, 0 );

        // Get today at start of day in site timezone
        $today = new \DateTime( 'today', $tz );

        // Calculate day difference
        $diff = $today->diff( $event_date );
        $days = (int) $diff->format( '%r%a' ); // Signed day count

        if ( $days < 0 ) {
            // Event is in the past
            return '';
        }

        if ( 0 === $days ) {
            return __( 'Takes place today', 'ics-calendar-enhanced' );
        }

        /* translators: %d: number of days until the event */
        return sprintf( _n( 'In %d day', 'In %d days', $days, 'ics-calendar-enhanced' ), $days );
    }

    /**
     * Shortcode handler for displaying category images.
     *
     * Usage: [ics_category_image category="Meeting" size="thumbnail"]
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function shortcode_category_image( array $atts ): string {
        $atts = shortcode_atts(
            [
                'category' => '',
                'size'     => 'full',
                'class'    => '',
                'alt'      => '',
            ],
            $atts,
            'ics_category_image'
        );

        if ( empty( $atts['category'] ) ) {
            return '';
        }

        $extra_class = ! empty( $atts['class'] ) ? ' ' . sanitize_html_class( $atts['class'] ) : '';
        
        $attr = [
            'class' => 'ics-enhanced-category-image ics-enhanced-shortcode-image' . $extra_class,
        ];

        if ( ! empty( $atts['alt'] ) ) {
            $attr['alt'] = sanitize_text_field( $atts['alt'] );
        }

        return Helpers::get_category_image_html(
            sanitize_text_field( $atts['category'] ),
            sanitize_text_field( $atts['size'] ),
            $attr
        );
    }

    /**
     * Filter callback for getting category images.
     *
     * @param string $image_url Current image URL.
     * @param string $category  Category string.
     * @param string $size      Image size.
     * @return string Image URL.
     */
    public function filter_get_category_image( string $image_url, string $category, string $size = 'full' ): string {
        return Helpers::get_category_image( $category, $size );
    }

    /**
     * Get image for a category (public static method for external use).
     *
     * @param string $category Category string.
     * @param string $size     Image size.
     * @return string Image URL.
     */
    public static function get_image( string $category, string $size = 'full' ): string {
        return Helpers::get_category_image( $category, $size );
    }

    /**
     * Get image HTML for a category (public static method for external use).
     *
     * @param string $category Category string.
     * @param string $size     Image size.
     * @param array  $attr     Additional attributes.
     * @return string HTML img tag.
     */
    public static function get_image_html( string $category, string $size = 'full', array $attr = [] ): string {
        return Helpers::get_category_image_html( $category, $size, $attr );
    }
}
