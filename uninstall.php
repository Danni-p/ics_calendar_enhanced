<?php
/**
 * Uninstall Script
 *
 * This file runs when the plugin is uninstalled (deleted) from WordPress.
 * It removes all plugin data from the database.
 *
 * @package ICSEnhanced
 */

// Exit if not called by WordPress uninstall process
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Clean up plugin data on uninstall.
 */
function ics_enhanced_uninstall(): void {
    // Remove plugin options
    $options_to_delete = [
        'ics_enhanced_version',
        'ics_enhanced_data_version',
        'ics_enhanced_category_mappings',
        'ics_enhanced_general_fallback',
    ];

    foreach ( $options_to_delete as $option ) {
        delete_option( $option );
    }

    // Clean up any transients
    delete_transient( 'ics_enhanced_cache' );

    // Clear any object cache entries
    if ( function_exists( 'wp_cache_delete' ) ) {
        wp_cache_delete( 'ics_enhanced_mappings', 'ics_enhanced' );
    }

    // For multisite: clean up on all sites
    if ( is_multisite() ) {
        ics_enhanced_uninstall_multisite();
    }
}

/**
 * Clean up plugin data on all sites in a multisite network.
 */
function ics_enhanced_uninstall_multisite(): void {
    global $wpdb;

    // Get all site IDs
    $site_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

    foreach ( $site_ids as $site_id ) {
        switch_to_blog( $site_id );

        // Remove options for this site
        delete_option( 'ics_enhanced_version' );
        delete_option( 'ics_enhanced_data_version' );
        delete_option( 'ics_enhanced_category_mappings' );
        delete_option( 'ics_enhanced_general_fallback' );

        // Clean up transients
        delete_transient( 'ics_enhanced_cache' );

        restore_current_blog();
    }
}

// Run the uninstall function
ics_enhanced_uninstall();

