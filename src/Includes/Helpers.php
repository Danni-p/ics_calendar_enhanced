<?php
/**
 * Helper Functions
 *
 * @package ICSEnhanced
 */

declare(strict_types=1);

namespace ICSEnhanced\Includes;

use ICSEnhanced\Admin\Category_Mapper;

/**
 * Helpers Class - Utility functions for the plugin.
 */
final class Helpers {

    /**
     * Get the image URL for a category with fallback logic.
     *
     * Fallback order:
     * 1. Specific category mapping
     * 2. Fallback image (if user has set one)
     * 3. Bundled default image (always available)
     *
     * @param string $category Category string.
     * @param string $size     Image size (default: 'full').
     * @return string Image URL or empty string if no image found.
     */
    public static function get_category_image( string $category, string $size = 'full' ): string {
        // If category is empty, skip mapping check and go straight to fallback
        if ( ! empty( $category ) ) {
            // 1. Try specific category mapping
            $attachment_id = Category_Mapper::get_image_for_category( $category );
            if ( $attachment_id !== null && $attachment_id > 0 ) {
                $url = self::get_image_url( $attachment_id, $size );
                if ( ! empty( $url ) ) {
                    return $url;
                }
            }
        }

        // 2. Try fallback (if user has set one)
        $general_fallback_id = Category_Mapper::get_general_fallback();
        if ( $general_fallback_id > 0 ) {
            $url = self::get_image_url( $general_fallback_id, $size );
            if ( ! empty( $url ) ) {
                return $url;
            }
        }

        // 3. Use bundled default image (always available)
        return self::get_bundled_default_image();
    }

    /**
     * Get the image URL from an attachment ID.
     *
     * @param int    $attachment_id Attachment ID.
     * @param string $size          Image size.
     * @return string Image URL or empty string.
     */
    public static function get_image_url( int $attachment_id, string $size = 'full' ): string {
        if ( $attachment_id <= 0 ) {
            return '';
        }

        $image = wp_get_attachment_image_url( $attachment_id, $size );
        return $image !== false ? $image : '';
    }

    /**
     * Get the image HTML tag for a category.
     *
     * @param string $category Category string.
     * @param string $size     Image size.
     * @param array  $attr     Additional attributes for the img tag.
     * @return string HTML img tag or empty string.
     */
    public static function get_category_image_html( string $category, string $size = 'full', array $attr = [] ): string {
        $url = self::get_category_image( $category, $size );

        if ( empty( $url ) ) {
            return '';
        }

        $default_attr = [
            'src'   => esc_url( $url ),
            'alt'   => esc_attr( sprintf(
                /* translators: %s: Category name */
                __( 'Icon for category: %s', 'ics-calendar-enhanced' ),
                $category
            ) ),
            'class' => 'ics-enhanced-category-image',
        ];

        $attr = wp_parse_args( $attr, $default_attr );

        $html = '<img';
        foreach ( $attr as $name => $value ) {
            $html .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
        }
        $html .= ' />';

        return $html;
    }

    /**
     * Get the bundled default fallback image URL.
     *
     * @return string URL to the bundled default image.
     */
    public static function get_bundled_default_image(): string {
        return ICS_ENHANCED_PLUGIN_URL . 'assets/images/default-fallback.svg';
    }

    /**
     * Convert a hex color to rgba with a given alpha.
     *
     * Matches the opacity used by ICS Calendar date elements (0.15).
     *
     * @param string $hex   Hex color (e.g. "#ff0000" or "#f00").
     * @param float  $alpha Alpha value 0–1 (default 0.15).
     * @return string RGBA color string or empty string if hex invalid.
     */
    public static function hex_to_rgba( string $hex, float $alpha = 0.15 ): string {
        $hex = ltrim( $hex, '#' );
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if ( strlen( $hex ) !== 6 || ! ctype_xdigit( $hex ) ) {
            return '';
        }
        $r = (int) hexdec( substr( $hex, 0, 2 ) );
        $g = (int) hexdec( substr( $hex, 2, 2 ) );
        $b = (int) hexdec( substr( $hex, 4, 2 ) );
        return sprintf( 'rgba(%d, %d, %d, %s)', $r, $g, $b, (string) $alpha );
    }

    /**
     * Sanitize a category string.
     *
     * @param string $category Category string.
     * @return string Sanitized category string.
     */
    public static function sanitize_category( string $category ): string {
        return sanitize_text_field( trim( $category ) );
    }

    /**
     * Check if an attachment exists and is an image.
     *
     * @param int $attachment_id Attachment ID.
     * @return bool Whether the attachment is a valid image.
     */
    public static function is_valid_image_attachment( int $attachment_id ): bool {
        if ( $attachment_id <= 0 ) {
            return false;
        }

        $post = get_post( $attachment_id );
        if ( ! $post || $post->post_type !== 'attachment' ) {
            return false;
        }

        return wp_attachment_is_image( $attachment_id );
    }

    /**
     * Get all available image sizes.
     *
     * @return array<string, array> Array of size names and dimensions.
     */
    public static function get_image_sizes(): array {
        global $_wp_additional_image_sizes;

        $sizes = [];
        $default_sizes = [ 'thumbnail', 'medium', 'medium_large', 'large', 'full' ];

        foreach ( $default_sizes as $size ) {
            if ( $size === 'full' ) {
                $sizes[ $size ] = [
                    'width'  => 0,
                    'height' => 0,
                    'crop'   => false,
                ];
            } else {
                $sizes[ $size ] = [
                    'width'  => (int) get_option( "{$size}_size_w" ),
                    'height' => (int) get_option( "{$size}_size_h" ),
                    'crop'   => (bool) get_option( "{$size}_crop" ),
                ];
            }
        }

        if ( ! empty( $_wp_additional_image_sizes ) ) {
            $sizes = array_merge( $sizes, $_wp_additional_image_sizes );
        }

        return $sizes;
    }
}

