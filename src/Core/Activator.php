<?php
/**
 * Plugin Activator
 *
 * @package ICSEnhanced
 */

declare(strict_types=1);

namespace ICSEnhanced\Core;

/**
 * Activator Class - Handles plugin activation tasks.
 */
final class Activator {

    /**
     * Option keys used by the plugin.
     */
    public const OPTION_VERSION = 'ics_enhanced_version';
    public const OPTION_MAPPINGS = 'ics_enhanced_category_mappings';
    public const OPTION_GENERAL_FALLBACK = 'ics_enhanced_general_fallback';

    /**
     * Data structure version for migration purposes.
     * Version 1: mappings as category => image_id
     * Version 2: mappings as category => ['image_id' => int, 'color' => string]
     */
    public const DATA_VERSION = 2;
    public const OPTION_DATA_VERSION = 'ics_enhanced_data_version';

    /**
     * Activate the plugin.
     */
    public static function activate(): void {
        self::create_default_options();
        self::set_version();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create default plugin options.
     */
    private static function create_default_options(): void {
        // Only set defaults if options don't exist (preserve existing settings on reactivation)
        if ( get_option( self::OPTION_MAPPINGS ) === false ) {
            add_option( self::OPTION_MAPPINGS, [] );
        }

        if ( get_option( self::OPTION_GENERAL_FALLBACK ) === false ) {
            // Fallback starts empty - user can set it in admin
            add_option( self::OPTION_GENERAL_FALLBACK, '' );
        }

        // Set data version for new installs
        if ( get_option( self::OPTION_DATA_VERSION ) === false ) {
            add_option( self::OPTION_DATA_VERSION, self::DATA_VERSION );
        }

        // Migrate old data format if needed
        self::maybe_migrate_data();
    }

    /**
     * Migrate data from old format to new format if needed.
     */
    private static function maybe_migrate_data(): void {
        $current_version = (int) get_option( self::OPTION_DATA_VERSION, 1 );

        if ( $current_version >= self::DATA_VERSION ) {
            return;
        }

        // Migrate from version 1 to version 2
        if ( $current_version < 2 ) {
            $mappings = get_option( self::OPTION_MAPPINGS, [] );
            
            if ( is_array( $mappings ) && ! empty( $mappings ) ) {
                $new_mappings = [];
                
                foreach ( $mappings as $category => $value ) {
                    // Check if already in new format
                    if ( is_array( $value ) && isset( $value['image_id'] ) ) {
                        $new_mappings[ $category ] = $value;
                    } else {
                        // Old format: value is just the image_id
                        $new_mappings[ $category ] = [
                            'image_id' => absint( $value ),
                            'color'    => '',
                        ];
                    }
                }
                
                update_option( self::OPTION_MAPPINGS, $new_mappings );
            }
        }

        // Update data version
        update_option( self::OPTION_DATA_VERSION, self::DATA_VERSION );
    }

    /**
     * Set or update the plugin version option.
     */
    private static function set_version(): void {
        update_option( self::OPTION_VERSION, ICS_ENHANCED_VERSION );
    }
}

