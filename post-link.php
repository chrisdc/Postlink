<?php
/**
 * Plugin Name: Post Link
 * Plugin URI:  https://example.com/plugin/
 * Description: Adds support for many-to-many post relationships.
 * Version:     1.0
 * Author:      Christopher Crouch
 * Author URI:  http://www.chrisdc.com
 * License:     GPL2
 * Text Domain: postlink
 */

 /**
  * Copyright 2014  Christopher Crouch  (email : chrisdc@gmail.com)
  *
  * This program is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License, version 2, as
  * published by the Free Software Foundation.
  *
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with this program; if not, write to the Free Software
  * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// consider making a singleton

class postlink {
	public function __construct() {
		define( 'PL_PLUGIN_FILE', __FILE__ );
		define( 'PL_VERSION', '1.0' );
		$this->load_deps();
	}

	public function load_deps() {
		if ( is_admin() ) {
			require_once plugin_dir_path( __FILE__ ) . 'inc/class-post-link-form.php';
			require_once plugin_dir_path( __FILE__ ) . 'inc/class-post-link-scripts.php';
		}
		require_once plugin_dir_path( __FILE__ ) . 'inc/class-post-link-cpt.php';
		require_once plugin_dir_path( __FILE__ ) . 'inc/class-post-link-api.php';
		require_once plugin_dir_path( __FILE__ ) . 'inc/class-post-link-install.php';
		if ( defined( 'DOING_AJAX' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'inc/class-post-link-ajax.php';
		}
	}
}

$PL = new postlink();

// Debugging function
function log_mee( $message ) {
    if ( WP_DEBUG === true ) {
        if ( is_array( $message ) || is_object( $message ) ) {
            error_log( print_r( $message, true ) );
        } else {
            error_log( $message );
        }
    }
}

add_filter( 'postlink_append_info', 'test_functioned', 10, 2 );

function test_functioned( $test, $post ) {
	return esc_html( '(' . $post->post_type . ')' );
}

add_filter( 'postlink_post_types', 'postlink_link_post_types' );

function postlink_link_post_types( $post_types ) {
	$post_types = array( 'post', 'page' );
	return $post_types;
}
