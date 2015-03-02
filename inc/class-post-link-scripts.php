<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class postlink_scripts {
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( get_called_class(), 'enqueue_scripts' ), 10, 1 );
	}

	public static function enqueue_scripts( $hook ) {
		$screen = get_current_screen();
		$post_types = apply_filters( 'postlink_post_types', array( 'post' ) );

		// Only enqueue when needed.
		if ( 'post' === $screen->base && in_array( $screen->post_type, $post_types ) ) {
			wp_enqueue_script( 'jquery-ui-autocomplete' );
			wp_enqueue_script( 'postlink_form', plugin_dir_url( dirname( __FILE__ ) ) . 'js/postlink.js', array( 'jquery-ui-autocomplete' ), '1.0', true );
			wp_localize_script( 'postlink_form', 'scriptVars', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'post' => __( 'Post', 'postlink' ),
				'linkType' => __( 'Link Type', 'postlink' ),
				'linkBack' => __( 'Link Back', 'postlink' ),
				'delete' => __( 'Delete', 'postlink' ),
			) );
			wp_enqueue_style( 'postlink-styles', plugin_dir_url( dirname( __FILE__ ) ) . 'css/style.css' );
			wp_enqueue_style( 'jQuery-styles', plugin_dir_url( dirname( __FILE__ ) ) . 'css/jquery-ui.theme.min.css' );
		}
	}

//	public static function enqueue_styles() {
//	}
}

postlink_scripts::init();
