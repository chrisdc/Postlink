<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class postlink_ajax {
	public static function init() {
		// bind wp_ajax events
		add_action( 'wp_ajax_postlink_findpost', array( get_called_class(), 'search_posts' ) );
		add_action( 'wp_ajax_postlink_findlink', array( get_called_class(), 'search_postlinks' ) );
	}

	public static function search_posts() {
		// Check nonce
		check_ajax_referer( 'update_postlinks', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'You do not have permission to edit posts.', 'postlink' ) );
		}

		$posts = get_posts( array(
			's' => sanitize_text_field( $_REQUEST['text'] ),
			'post_type' => apply_filters( 'postlink_post_types', array( 'post' ) )
		));

		foreach ( $posts as $key => $post ) {
			$results[$key]['label'] = esc_html( $post->post_title );
			$results[$key]['value'] = esc_html( $post->ID );
			$results[$key]['append'] = ' <span class="post-type">' . esc_html( '(' . $post->post_type . ')' ) . '</span>';
		}

		if ( ! isset( $results ) ) {
			return false;
		}

		echo json_encode( $results );
		exit();
	}

	public static function search_postlinks() {
		// Check nonce
//		check_ajax_referer( 'update_postlinks', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'You do not have permission to edit posts.', 'postlink' ) );
		}

		$posts = get_posts( array(
			's' => sanitize_text_field( $_REQUEST['text'] ),
			'post_type' => 'postlink'
		));

		foreach ( $posts as $key => $post ) {
			$results[$key]['label'] = esc_html( $post->post_title );
			$results[$key]['value'] = esc_html( $post->ID );
		}

		if ( ! isset( $results ) ) {
			return false;
		}

		echo json_encode( $results );
		exit();
	}
}

postlink_ajax::init();
