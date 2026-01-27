<?php
/**
 * Category Mapper Class
 *
 * @package ICSEnhanced
 */

declare(strict_types=1);

namespace ICSEnhanced\Admin;

use ICSEnhanced\Core\Activator;

/**
 * Category_Mapper Class - Handles category string to image and color mappings.
 */
final class Category_Mapper {

    /**
     * Get all category mappings.
     *
     * Returns mappings in format: category => ['image_id' => int, 'color' => string]
     *
     * @return array<string, array{image_id: int, color: string}> Array of category mappings.
     */
    public static function get_mappings(): array {
        $mappings = get_option( Activator::OPTION_MAPPINGS, [] );
        
        if ( ! is_array( $mappings ) ) {
            return [];
        }

        // Normalize the data structure (handle legacy format)
        $normalized = [];
        foreach ( $mappings as $category => $value ) {
            if ( is_array( $value ) && isset( $value['image_id'] ) ) {
                // New format
                $normalized[ $category ] = [
                    'image_id' => absint( $value['image_id'] ),
                    'color'    => sanitize_hex_color( $value['color'] ?? '' ) ?: '',
                ];
            } else {
                // Legacy format (just image_id as integer)
                $normalized[ $category ] = [
                    'image_id' => absint( $value ),
                    'color'    => '',
                ];
            }
        }

        return $normalized;
    }

    /**
     * Get mappings in legacy format (category => image_id) for backward compatibility.
     *
     * @return array<string, int> Array of category strings mapped to attachment IDs.
     */
    public static function get_image_mappings(): array {
        $mappings = self::get_mappings();
        $image_mappings = [];

        foreach ( $mappings as $category => $data ) {
            if ( ! empty( $data['image_id'] ) ) {
                $image_mappings[ $category ] = $data['image_id'];
            }
        }

        return $image_mappings;
    }

    /**
     * Get color mappings only.
     *
     * @return array<string, string> Array of category strings mapped to hex colors.
     */
    public static function get_color_mappings(): array {
        $mappings = self::get_mappings();
        $color_mappings = [];

        foreach ( $mappings as $category => $data ) {
            if ( ! empty( $data['color'] ) ) {
                $color_mappings[ $category ] = $data['color'];
            }
        }

        return $color_mappings;
    }

    /**
     * Save category mappings.
     *
     * @param array<string, array{image_id: int, color: string}> $mappings Array of category mappings.
     * @return bool Whether the option was updated.
     */
    public static function save_mappings( array $mappings ): bool {
        // Sanitize and validate mappings
        $sanitized = [];
        foreach ( $mappings as $category => $data ) {
            $sanitized_category = sanitize_text_field( trim( (string) $category ) );

            if ( empty( $sanitized_category ) ) {
                continue;
            }

            // Handle both new array format and legacy integer format
            if ( is_array( $data ) ) {
                $image_id = absint( $data['image_id'] ?? 0 );
                $color = sanitize_hex_color( $data['color'] ?? '' ) ?: '';
            } else {
                // Legacy format support
                $image_id = absint( $data );
                $color = '';
            }

            // Only save if we have at least an image or color
            if ( $image_id > 0 || ! empty( $color ) ) {
                $sanitized[ $sanitized_category ] = [
                    'image_id' => $image_id,
                    'color'    => $color,
                ];
            }
        }

        return update_option( Activator::OPTION_MAPPINGS, $sanitized );
    }

    /**
     * Get image attachment ID for a category string.
     *
     * @param string $category Category string to look up.
     * @return int|null Attachment ID or null if not found.
     */
    public static function get_image_for_category( string $category ): ?int {
        $mappings = self::get_mappings();
        $normalized_category = self::normalize_category( $category );

        // First try exact match
        if ( isset( $mappings[ $category ] ) && ! empty( $mappings[ $category ]['image_id'] ) ) {
            return $mappings[ $category ]['image_id'];
        }

        // Try normalized match (case-insensitive)
        foreach ( $mappings as $mapped_category => $data ) {
            if ( self::normalize_category( $mapped_category ) === $normalized_category ) {
                if ( ! empty( $data['image_id'] ) ) {
                    return $data['image_id'];
                }
            }
        }

        return null;
    }

    /**
     * Get color for a category string.
     *
     * @param string $category Category string to look up.
     * @return string Hex color or empty string if not found.
     */
    public static function get_color_for_category( string $category ): string {
        $mappings = self::get_mappings();
        $normalized_category = self::normalize_category( $category );

        // First try exact match
        if ( isset( $mappings[ $category ] ) && ! empty( $mappings[ $category ]['color'] ) ) {
            return $mappings[ $category ]['color'];
        }

        // Try normalized match (case-insensitive)
        foreach ( $mappings as $mapped_category => $data ) {
            if ( self::normalize_category( $mapped_category ) === $normalized_category ) {
                if ( ! empty( $data['color'] ) ) {
                    return $data['color'];
                }
            }
        }

        return '';
    }

    /**
     * Normalize category string for comparison.
     *
     * @param string $category Category string.
     * @return string Normalized category string.
     */
    public static function normalize_category( string $category ): string {
        return strtolower( trim( $category ) );
    }

    /**
     * Get the fallback image attachment ID.
     *
     * @return int Attachment ID or 0 if not set.
     */
    public static function get_general_fallback(): int {
        return absint( get_option( Activator::OPTION_GENERAL_FALLBACK, 0 ) );
    }

    /**
     * Save the fallback image.
     *
     * @param int $attachment_id Attachment ID.
     * @return bool Whether the option was updated.
     */
    public static function save_general_fallback( int $attachment_id ): bool {
        return update_option( Activator::OPTION_GENERAL_FALLBACK, absint( $attachment_id ) );
    }

    /**
     * Add a single mapping.
     *
     * @param string $category      Category string.
     * @param int    $attachment_id Attachment ID.
     * @param string $color         Hex color (optional).
     * @return bool Whether the mapping was added.
     */
    public static function add_mapping( string $category, int $attachment_id, string $color = '' ): bool {
        $mappings = self::get_mappings();
        $sanitized_category = sanitize_text_field( trim( $category ) );

        if ( empty( $sanitized_category ) ) {
            return false;
        }

        // Need at least image or color
        if ( $attachment_id <= 0 && empty( $color ) ) {
            return false;
        }

        $mappings[ $sanitized_category ] = [
            'image_id' => absint( $attachment_id ),
            'color'    => sanitize_hex_color( $color ) ?: '',
        ];

        return self::save_mappings( $mappings );
    }

    /**
     * Remove a mapping.
     *
     * @param string $category Category string to remove.
     * @return bool Whether the mapping was removed.
     */
    public static function remove_mapping( string $category ): bool {
        $mappings = self::get_mappings();
        $sanitized_category = sanitize_text_field( trim( $category ) );

        if ( ! isset( $mappings[ $sanitized_category ] ) ) {
            return false;
        }

        unset( $mappings[ $sanitized_category ] );
        return self::save_mappings( $mappings );
    }

    /**
     * Check if a mapping exists for a category.
     *
     * @param string $category Category string.
     * @return bool Whether a mapping exists (image or color).
     */
    public static function has_mapping( string $category ): bool {
        return self::get_image_for_category( $category ) !== null 
            || ! empty( self::get_color_for_category( $category ) );
    }

    /**
     * Validate and sanitize a hex color.
     *
     * @param string $color Color to validate.
     * @return string Sanitized hex color or empty string.
     */
    public static function sanitize_color( string $color ): string {
        $color = trim( $color );
        
        if ( empty( $color ) ) {
            return '';
        }

        // Ensure it starts with #
        if ( strpos( $color, '#' ) !== 0 ) {
            $color = '#' . $color;
        }

        return sanitize_hex_color( $color ) ?: '';
    }
}
