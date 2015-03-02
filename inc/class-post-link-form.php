<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Make static and assign in init

class postlink_form {
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ), 10, 1 );
		add_action( 'save_post', array( __CLASS__, 'process_data' ) );
	}

	public static function add_meta_box( $post_type ) {
		if ( in_array( $post_type, apply_filters( 'postlink_post_types', array( 'post' ) ) ) ) {
			add_meta_box(
				'postlink_meta_box',
				__( 'Link posts', 'postlink' ),
				array( __CLASS__, 'render_meta_box' ),
				$post_type
			);
		}
	}

	public static function render_meta_box() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'inc/class-post-link-api.php';
//		$api = new postlink_api();
//		$links = $api->get_linked_posts( get_post() );

//		if ( is_array( $links ) ) {
//			foreach ( $links as $type_id => $type ) {
//				foreach ( $type as $pair_id => $sub_type ) {
//					// create box based on $type_id and $pair_id
//
//					foreach ( $sub_type as $link ) {
//						// Create row
//					}
//				}
//			}
//		}

		require_once plugin_dir_path( __FILE__ ) . '../partials/form-template.php';
	}

	// Takes the imput from the form and sends it to postlink_api
	public static function process_data() {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Verify nonce
		check_admin_referer( 'update_postlinks', 'postlink_update_nonce' );

		// Check user permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'You do not have permission to edit posts.', 'postlink' ) );
		}

		$api = new postlink_api();

		// Handle items to delete here.
		if ( isset( $_REQUEST['delete-links'] ) ) {
			foreach ( $_REQUEST['delete-links'] as $type_id => $type ) {
				foreach ( $type as $post_id ) {
					// Delete the link.
					$api->unlink_posts( $_REQUEST['post_ID'], $post_id, $type_id );
				}
			}
		}

		// Now create the links on the form. API prevents link duplication.
		if ( isset( $_REQUEST['postlinks'] ) ) {
			foreach ( $_REQUEST['postlinks'] as $link_type_id => $group ) {
				foreach ( $group as $rev_link_type_id => $type ) {
					foreach ( $type as $post_to_link ) {
						$link_id = $api->link_posts( $_REQUEST['post_ID'], $post_to_link, $link_type_id, null );
						if ( 0 !== $rev_link_type_id ) {
							$api->link_posts( $post_to_link, $_REQUEST['post_ID'], $rev_link_type_id, $link_id );
						}
					}
				}
			}
		}
	}
}

postlink_form::init();
