<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class postlink_install {
	public function __construct() {
		register_activation_hook( PL_PLUGIN_FILE, array( $this, 'install' ) );
		add_action( 'admin_init', array( $this, 'upgrade_check' ) );
	}

	public function install() {
		$this->init_options();
		$this->create_table();
		$this->register_post_features();
		$this->flush_rewrite();
	}

	public function upgrade_check() {
		$installed_version = get_option( 'postlink_db_version' );

		if ( PL_VERSION !== $installed_version ) {
			$this->create_table();
		}
	}

	private function init_options() {
		// Placeholder for future options.
	}

	private function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . "postlink_intersect";
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			link_id bigint(20) NOT NULL auto_increment,
			pair_id bigint(20),
			source_id bigint(20) NOT NULL,
			target_id bigint(20) NOT NULL,
			link_type_id bigint(20) NOT NULL,
			PRIMARY KEY  (link_id),
			KEY pair_id (pair_id),
			KEY source_id (source_id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		$installed_version = get_option( 'postlink_db_version' );

		if ( ! $installed_version ) {
			add_option( 'postlink_db_version', PL_VERSION );
		} else if ( PL_VERSION !== $installed_version ) {
			if ( version_compare( '1.1', $installed_version ) ) {
				// Alter database (delete cols etc.)
			}
			update_option( 'postlink_db_version', PL_VERSION );
		}
	}

	// Register custom post type and rewrite rule here before flushing permalinks.
	private function register_post_features() {
		require_once plugin_dir_path( __FILE__ ) . 'class-post-link-cpt.php';
		$this->cpt = new postlink_cpt();
		$this->cpt->register_cpt();
		$this->cpt->add_endpoint();
	}

	private function flush_rewrite() {
		flush_rewrite_rules();
	}
}

return new postlink_install();
