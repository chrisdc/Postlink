<?php

// Exit if accessed directly
if( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

// Use standard class and assign in __construct

// Remove link table
// Delete post type links
// Flush rewrite rules

global $wpdb;

// Delete the intersect table.
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'postlink_intersect' );

// Delete options
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'postlink_%';");

// Delete posts
$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'postlink';" );
