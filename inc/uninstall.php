<?php

if( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

global $wpdb;

// Delete the intersect table.
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'postlink_intersect' );

// Delete options
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'postlink_%';");

// Delete posts
$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'postlink';" );
