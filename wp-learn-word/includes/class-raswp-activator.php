<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RASWP_Activator {
	public static function raswp_activate() {
		self::raswp_create_tables();
	}

	private static function raswp_create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$progress_table = $wpdb->prefix . 'raswp_progress';
		$orders_table   = $wpdb->prefix . 'raswp_orders';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql1 = "CREATE TABLE {$progress_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			word_id BIGINT UNSIGNED NOT NULL,
			book_id BIGINT UNSIGNED NOT NULL,
			leitner_box TINYINT UNSIGNED NOT NULL DEFAULT 1,
			last_reviewed DATETIME NULL,
			times_correct INT UNSIGNED NOT NULL DEFAULT 0,
			times_wrong INT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			UNIQUE KEY user_word (user_id, word_id),
			KEY book_id (book_id),
			KEY last_reviewed (last_reviewed),
			PRIMARY KEY  (id)
		) {$charset_collate};";

		$sql2 = "CREATE TABLE {$orders_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NULL,
			plan_id BIGINT UNSIGNED NULL,
			amount BIGINT UNSIGNED NOT NULL,
			authority VARCHAR(64) NULL,
			ref_id VARCHAR(64) NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			UNIQUE KEY authority (authority),
			PRIMARY KEY  (id)
		) {$charset_collate};";

		dbDelta($sql1);
		dbDelta($sql2);

		// Default options
		$default_options = [
			'words_per_session' => 10,
			'free_words_limit' => 20,
			'require_login' => 1,
			'leitner_intervals' => '1,2,4,8,16',
			'zarinpal_merchant_id' => '',
			'zarinpal_amount' => 100000,
			'zarinpal_sandbox' => 1,
			'callback_page' => 'raswp-zarinpal'
		];
		add_option('raswp_settings', $default_options);
	}
}