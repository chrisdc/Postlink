<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class postlink_cpt {
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ), 5 );
		add_action( 'init', array( __CLASS__, 'add_endpoint' ), 10 );
		add_filter( 'posts_clauses', array( __CLASS__, 'custom_SQL' ) );
		add_filter( 'template_include', array( __CLASS__, 'switch_template' ) );
		add_filter( 'get_the_archive_title', array( __CLASS__, 'archive_title' ) );
		add_action( 'wp_trash_post', array( __CLASS__, 'auto_delete' ) );
		add_action( 'save_post_postlink', array( __CLASS__, 'remove_transient' ) );
	}

	/**
	 * When a post is deleted automatically delete any links that use it.
	 */
	public static function auto_delete( $post_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'postlink_intersect';

		$wpdb->delete(
			$wpdb->prefix . "postlink_intersect",
			array( 'source_id' => $post_id )
		);
		$wpdb->delete(
			$wpdb->prefix . "postlink_intersect",
			array( 'target_id' => $post_id )
		);
		$wpdb->delete(
			$wpdb->prefix . "postlink_intersect",
			array( 'link_type_id' => $post_id )
		);
	}

	public static function register_cpt() {
		register_post_type( 'postlink',
			array(
			   'labels' => array(
				   'name' => __( 'Post Links', 'postlink' ),
				   'singular_name' => __( 'Post Link', 'postlink' ),
				   'add_new_item' => __( 'Add New Link Type', 'postlink' ),
				   'edit_item' => __( 'Edit Link Type', 'postlink' ),
				   'new_item' => __( 'New link type', 'postlink' ),
				   'view_item' => __( 'View link type', 'postlink' ),
				   'search_items' => __( 'Search link types', 'postlink' ),
				   'not_found' => __( 'No link types found', 'postlink' ),
				   'not_found_in_trash' => __( 'No link types found in trash', 'postlink' ),
			   ),
			   'description' => __( 'Types of connection between different posts', 'postlink' ),
			   'public' => true,
			   'exclude_from_search' => true,
			   'publicly_queryable' => false,
			   'show_in_nav_menus' => false,
			   'show_in_admin_bar' => false,
			   'menu_position' => 25,
			   'menu_icon' => 'dashicons-admin-links',
			   'supports' => array(
				   'title'
			   ),
			   'has_archive' => false,
			   'rewrite' => false
			)
		);
	}

	/**
	 * The postlink CPR doesn't have it's own archive as would normally be the
	 * case. Instead the following methods create a custom rewrite endpoint (find) that
	 * displays the connected posts of a given type.
	 *
	 * Example:
	 *
	 * If we have a post describing an actor at [permalink], and a postlink named
	 * 'appears-in', then we might find the shows this actor appears in at
	 * [permalink]/find/appears-in/
	 */
	public static function add_endpoint() {
		add_rewrite_endpoint( 'find', EP_PERMALINK );
	}

	// Check IF conditions and escaping
	public static function custom_SQL( $clauses ) {
		global $wp_query;
		global $wpdb;

		$table = $wpdb->prefix . "postlink_intersect";
		$q = $wp_query->query_vars;

		if ( isset( $q['find'] ) && isset( $q['name'] ) ) {
			// Get the original post ID based on the slug in the URL.
			$post_args=array(
			  'name' => $q['name'],
			  'post_status' => 'publish',
			  'numberposts' => 1
			);
			$original_post = get_posts($post_args)[0];
			$original_id = $original_post->ID;

			// Get the link type ID based on the slug in the email/
			$link_args=array(
				'name' => $q['find'],
				'post_status' => 'publish',
				'post_type' => 'postlink',
				'numberposts' => 1
			);
			$link_type = get_posts($link_args);
			$link_type_id = $link_type[0]->ID;

			// Save this to the cache for use in the title.
			wp_cache_set( 'postlink_type_id', $link_type_id );

			// JOIN
			$clauses['join'] = "JOIN $table ON $wpdb->posts.ID = $table.target_id";

			// WHERE add type test
			$clauses['where'] = " AND source_id = $original_id AND $wpdb->posts.post_status = 'publish' AND link_type_id = $link_type_id";

			// LIMITS (pagination)
			$page = absint( $q['paged'] );
			if ( ! $page ) {
				$page = 1;
			}

			if ( empty( $q['offset'] ) ) {
				$pgstrt = absint( ( $page - 1 ) * $q['posts_per_page'] ) . ', ';
			} else {
				$q['offset'] = absint( $q['offset'] );
				$pgstrt = $q['offset'] . ', ';
			}

			$clauses['limits'] = 'LIMIT ' . $pgstrt . $q['posts_per_page'];
		}

		return $clauses;
	}

	/**
	 * When querying linked posts use the appropriate archive template.
	 * @param   string $template The default template
	 * @returns string $template The filtered template
	 */
	public static function switch_template( $template ) {
		global $wp_query;

		if ( isset( $wp_query->query_vars['find'] ) ) {
			$template = get_archive_template();
		}

		return $template;
	}

	public static function archive_title( $title ) {
		$link_type_id = wp_cache_get( 'postlink_type_id' );
		$link_type_name = get_the_title( $link_type_id );

		$original_title = get_the_title();
		$title = esc_html( $link_type_name . ' ' . $original_title );

		return $title;
	}

	// If we update a postlink post delete its transient.
	public static function remove_transient( $post_id ) {
		delete_transient( 'postlink_type_' . $post_id );
	}
}

postlink_cpt::init();
