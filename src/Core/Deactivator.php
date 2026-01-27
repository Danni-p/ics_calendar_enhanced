<?php
/**
 * Plugin Deactivator
 *
 * @package ICSEnhanced
 */

declare(strict_types=1);

namespace ICSEnhanced\Core;

/**
 * Deactivator Class - Handles plugin deactivation tasks.
 */
final class Deactivator {

    /**
     * Deactivate the plugin.
     */
    public static function deactivate(): void {
        self::clear_cache();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Clear any transients or cached data.
     */
    private static function clear_cache(): void {
        // Delete any plugin transients
        delete_transient( 'ics_enhanced_cache' );

        // Clear any object cache if available
        if ( function_exists( 'wp_cache_flush' ) ) {
            wp_cache_delete( 'ics_enhanced_mappings', 'ics_enhanced' );
        }
    }
}

