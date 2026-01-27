<?php
/**
 * Simple PSR-4 Autoloader
 *
 * @package ICSEnhanced
 */

declare(strict_types=1);

namespace ICSEnhanced;

/**
 * Simple autoloader for ICS Calendar Enhanced plugin.
 *
 * @param string $class Fully qualified class name.
 */
spl_autoload_register( static function ( string $class ): void {
    // Only handle our namespace
    $prefix = 'ICSEnhanced\\';
    $prefix_len = strlen( $prefix );

    if ( strncmp( $prefix, $class, $prefix_len ) !== 0 ) {
        return;
    }

    // Get relative class name
    $relative_class = substr( $class, $prefix_len );

    // Convert namespace separators to directory separators
    $file = str_replace( '\\', '/', $relative_class );

    // Build file path
    $file_path = ICS_ENHANCED_PLUGIN_DIR . 'src/' . $file . '.php';

    // Load file if it exists
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
} );

