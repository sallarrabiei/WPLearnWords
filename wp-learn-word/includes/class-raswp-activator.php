<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RASWP_Activator {
	public static function activate() {
		self::create_tables();
		self::add_default_options();
	}

	private static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$progress_table = $wpdb->prefix . 'raswp_user_progress';
		$payments_table = $wpdb->prefix . 'raswp_payments';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql1 = "CREATE TABLE {$progress_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			word_id BIGINT(20) UNSIGNED NOT NULL,
			box TINYINT(3) UNSIGNED NOT NULL DEFAULT 1,
			next_review_at DATETIME NULL,
			correct_count INT(11) NOT NULL DEFAULT 0,
			incorrect_count INT(11) NOT NULL DEFAULT 0,
			last_reviewed DATETIME NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_word (user_id, word_id),
			KEY user_id (user_id),
			KEY word_id (word_id)
		) {$charset_collate};";

		$sql2 = "CREATE TABLE {$payments_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			amount BIGINT(20) UNSIGNED NOT NULL,
			authority VARCHAR(64) NULL,
			ref_id VARCHAR(64) NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY authority (authority)
		) {$charset_collate};";

		dbDelta( $sql1 );
		dbDelta( $sql2 );
	}

	private static function add_default_options() {
		add_option( 'raswp_random_word_count', 10 );
		add_option( 'raswp_free_review_limit', 20 );
		add_option( 'raswp_box_intervals', array( 1, 3, 7, 14, 30 ) );
		add_option( 'raswp_zarinpal_merchant_id', '' );
		add_option( 'raswp_zarinpal_amount', 200000 );
		add_option( 'raswp_zarinpal_sandbox', 1 );
	}
}