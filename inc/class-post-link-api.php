<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

//Create static methods that enqueue scripts, and possibly styles as needed.

class postlink_api {
	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'postlink_intersect';
	}

	public function get_connection_links( $post ) {
		global $wpdb;

		$post_id = get_the_ID();
		$base_url = get_permalink();
		$links = [];

		// Get the link types (ids) in use by this post.
		// Cache the link types associated for each post for 12 hours.
		$link_types = get_transient( 'postlink_' . $post_id );

		if ( false === $link_types ) {
			$link_types = $wpdb->get_col(
				"SELECT * FROM $this->table
				WHERE source_id = $post_id
				GROUP BY link_type_id",
				3
			);
			set_transient( postlink_ . $post_id, $link_types, 12 * HOUR_IN_SECONDS );
		}

		// Based on these ids get the title and a url for each type.
		// Link type titles and slugs are cached for 12 hours.
		foreach ( $link_types as $link_type ) {
			$link_type_details = get_transient( 'postlink_type_' . $link_type );

			if ( false === $link_type_details ) {
				$post = get_post( $link_type, ARRAY_A );
				$link_type_details['slug'] = $post['post_name'];
				$link_type_details['title'] = $post['post_title'];
				set_transient( 'postlink_type_' . $link_type, $link_type_details, 12 * HOUR_IN_SECONDS );
			}

			$url = $base_url . '/' . $link_type_details['slug']. '/';

			$links[] = array(
				'url' => $base_url . '/' . $link_type_details['slug']. '/',
				$link_type_details['title']
			);
		}

		if ( sizeof( $urls === 0 ) ) {
			return;
		}

		echo '<ul>';
		foreach ( $links as $link ) {
			printf(
				'<li><a href="%1$s">$2%s</a></li>',
				esc_attr( $link['url'] ),
				esc_html( $link['title'] )
			);
		}
		echo '</ul>';

		return;
	}

	public function get_endpoint_links( $post, $post_link ) {
		// Use current post by default.
	}

	// Used in admin to populate the form.
	public function get_linked_posts( $post ) {
		global $wpdb;
		$results = array();

		$post = get_post( $post );
		$post_id = isset( $post->ID ) ? $post->ID : 0;

		// Return early if the origin post doesn't exist.
		if ( 0 === $post_id ) {
			return false;
		}

		$rows = $wpdb->get_results( "SELECT * FROM $this->table WHERE source_id = $post_id ORDER BY link_type_id, target_id ASC", ARRAY_A );

		// Group by type and pair
		foreach ( $rows as $row ) {
			$type_id = $row['link_type_id'];

			$pair_id = isset( $row['pair_id'] ) ? $row['pair_id'] : 0;

			if ( 0 === $pair_id ) {
				$rev_type_id = 0;
			} else {
				$rev_type_id = $wpdb->get_row( "SELECT * FROM $this->table WHERE link_id = $pair_id", ARRAY_A )['link_type_id'];
			}

			$results[$type_id][$rev_type_id][] = $row['target_id'];
		}

		return $results;
	}

	public function get_linked_posts_of_type( $post_link ) {
//		if ( isset $post_link ) {
//			$query .= " AND link_type_id = $post_link"
//		}
	}

	public function create_link_type( $title ) {
		$this->verify_auth();
	}

	public function link_posts( $post1, $post2, $post_link, $pair_id = null ) {
		global $wpdb;

		$this->verify_auth();

		$post1 = get_post( $post1 );
		$post1_id = isset( $post1->ID ) ? $post1->ID : 0;

		$post2 = get_post( $post2 );
		$post2_id = isset( $post2->ID ) ? $post2->ID : 0;

		$post_link = get_post( $post_link );
		$post_link_id = isset( $post_link->ID ) ? $post_link->ID : 0;

		// Make sure all values are set before continuing.
		if ( 0 === $post1_id || 0 === $post2_id || 0 === $post_link_id ) {
			return false;
		}

		// Check if this entry already exists.
		$link_id = $wpdb->get_row( "SELECT link_id FROM $this->table WHERE source_id = $post1_id AND target_id = $post2_id AND link_type_id = $post_link_id" );

		if ( isset( $link_id ) ) {
			return false;
		}

		$data = array(
			'source_id' => $post1_id,
			'target_id' => $post2_id,
			'link_type_id' => $post_link_id
		);

		$format = array(
			'%d',
			'%d',
			'%d'
		);

		$wpdb->insert( $this->table, $data, $format );

		$insert_id = $wpdb->insert_id;

		// If we have a pair_id use it to link the 2 connections together.
		if ( isset( $pair_id ) ) {
			$this->pair_links( $insert_id, $pair_id );
		}

		// We're connecting a new post, so delete the transient.
		delete_transient( 'postlink_' . $post1_id );

		return $insert_id;
	}

	public function unlink_posts( $post1, $post2, $post_link ) {
		global $wpdb;

		$this->verify_auth();

		$target_row = $wpdb->get_row(
			"SELECT * FROM $this->table WHERE source_id = $post1 AND target_id = $post2 AND link_type_id = $post_link",
			ARRAY_A
		);

		$pair_id = $target_row['pair_id'];

		$wpdb->delete(
			$this->table,
			array(
				'source_id' => $post1,
				'target_id' => $post2,
				'link_type_id' => $post_link
			)
		);

		// Delete the matching link if it exists.
		if ( isset( $pair_id ) ) {
			$wpdb->delete(
				$this->table,
				array(
					'link_id' => $pair_id
				)
			);
		}
	}

		// Given to link type ids this function pairs them up.
	private function pair_links( $link1_id, $link2_id ) {
		global $wpdb;
		$link1_row = $wpdb->get_results( "SELECT * FROM $this->table WHERE link_id = $link1_id", ARRAY_A );
		$link2_row = $wpdb->get_results( "SELECT * FROM $this->table WHERE link_id = $link2_id", ARRAY_A );

		if ( 0 === sizeof( $link1_row ) || 0 === sizeof( $link2_row ) ) {
			// At least 1 id doesn't match anything.
			return;
		}

		$wpdb->update(
			$this->table,
			array( 'pair_id' => $link2_id ),
			array( 'link_id' => $link1_id )
		);

		$wpdb->update(
			$this->table,
			array( 'pair_id' => $link1_id ),
			array( 'link_id' => $link2_id )
		);
	}

	private function verify_auth() {
		// Verify nonce
		check_admin_referer( 'update_postlinks', 'postlink_update_nonce' );

		// Check user permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'You do not have permission to edit posts.', 'postlink' ) );
		}
	}
}
