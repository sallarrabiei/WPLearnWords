<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RASWP_Admin {
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menus' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_raswp_import_csv', array( __CLASS__, 'handle_import_csv' ) );
	}

	public static function add_menus() {
		add_menu_page(
			__( 'WP Learn Word', 'wp-learn-word' ),
			__( 'WP Learn Word', 'wp-learn-word' ),
			'manage_options',
			'raswp_main',
			array( __CLASS__, 'render_welcome_page' ),
			'dashicons-welcome-learn-more',
			65
		);

		add_submenu_page( 'raswp_main', __( 'Settings', 'wp-learn-word' ), __( 'Settings', 'wp-learn-word' ), 'manage_options', 'raswp_settings', array( __CLASS__, 'render_settings_page' ) );
		add_submenu_page( 'raswp_main', __( 'Import CSV', 'wp-learn-word' ), __( 'Import CSV', 'wp-learn-word' ), 'manage_options', 'raswp_import', array( __CLASS__, 'render_import_page' ) );
		add_submenu_page( 'raswp_main', __( 'Books', 'wp-learn-word' ), __( 'Books', 'wp-learn-word' ), 'manage_options', 'edit.php?post_type=raswp_book' );
		add_submenu_page( 'raswp_main', __( 'Words', 'wp-learn-word' ), __( 'Words', 'wp-learn-word' ), 'manage_options', 'edit.php?post_type=raswp_word' );
	}

	public static function register_settings() {
		register_setting( 'raswp_settings_group', 'raswp_random_word_count', array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 10 ) );
		register_setting( 'raswp_settings_group', 'raswp_free_review_limit', array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 20 ) );
		register_setting( 'raswp_settings_group', 'raswp_box_intervals', array( 'type' => 'array', 'sanitize_callback' => array( __CLASS__, 'sanitize_intervals' ), 'default' => array( 1, 3, 7, 14, 30 ) ) );
		register_setting( 'raswp_settings_group', 'raswp_zarinpal_merchant_id', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
		register_setting( 'raswp_settings_group', 'raswp_zarinpal_amount', array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 200000 ) );
		register_setting( 'raswp_settings_group', 'raswp_zarinpal_sandbox', array( 'type' => 'boolean', 'sanitize_callback' => array( __CLASS__, 'sanitize_bool' ), 'default' => 1 ) );
	}

	public static function sanitize_intervals( $value ) {
		if ( is_string( $value ) ) {
			$parts = array_map( 'trim', explode( ',', $value ) );
			$value = array();
			foreach ( $parts as $part ) {
				$value[] = max( 1, (int) $part );
			}
		}
		if ( ! is_array( $value ) ) {
			$value = array( 1, 3, 7, 14, 30 );
		}
		return $value;
	}

	public static function sanitize_bool( $value ) {
		return $value ? 1 : 0;
	}

	public static function render_welcome_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP Learn Word', 'wp-learn-word' ); ?></h1>
			<p><?php esc_html_e( 'This plugin helps students learn English vocabulary using the Leitner System. Use the pages on the left to configure settings, import words, and manage books.', 'wp-learn-word' ); ?></p>
			<p><strong><?php esc_html_e( 'Shortcodes:', 'wp-learn-word' ); ?></strong></p>
			<ul>
				<li><code>[raswp_leitner book="book-slug"]</code> — <?php esc_html_e( 'Display the learning interface. Optionally filter by a specific book slug.', 'wp-learn-word' ); ?></li>
				<li><code>[raswp_upgrade]</code> — <?php esc_html_e( 'Show upgrade button for payment.', 'wp-learn-word' ); ?></li>
			</ul>
		</div>
		<?php
	}

	public static function render_settings_page() {
		$intervals = get_option( 'raswp_box_intervals', array( 1, 3, 7, 14, 30 ) );
		$intervals_str = implode( ',', array_map( 'intval', $intervals ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP Learn Word Settings', 'wp-learn-word' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'raswp_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="raswp_random_word_count"><?php esc_html_e( 'Random words per session', 'wp-learn-word' ); ?></label></th>
						<td><input name="raswp_random_word_count" type="number" id="raswp_random_word_count" value="<?php echo esc_attr( get_option( 'raswp_random_word_count', 10 ) ); ?>" class="small-text" min="1" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="raswp_free_review_limit"><?php esc_html_e( 'Free review limit per user', 'wp-learn-word' ); ?></label></th>
						<td><input name="raswp_free_review_limit" type="number" id="raswp_free_review_limit" value="<?php echo esc_attr( get_option( 'raswp_free_review_limit', 20 ) ); ?>" class="small-text" min="0" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="raswp_box_intervals"><?php esc_html_e( 'Leitner box intervals (days, comma-separated)', 'wp-learn-word' ); ?></label></th>
						<td><input name="raswp_box_intervals" type="text" id="raswp_box_intervals" value="<?php echo esc_attr( $intervals_str ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="raswp_zarinpal_merchant_id"><?php esc_html_e( 'Zarinpal Merchant ID', 'wp-learn-word' ); ?></label></th>
						<td><input name="raswp_zarinpal_merchant_id" type="text" id="raswp_zarinpal_merchant_id" value="<?php echo esc_attr( get_option( 'raswp_zarinpal_merchant_id', '' ) ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="raswp_zarinpal_amount"><?php esc_html_e( 'Upgrade Amount (IRR)', 'wp-learn-word' ); ?></label></th>
						<td><input name="raswp_zarinpal_amount" type="number" id="raswp_zarinpal_amount" value="<?php echo esc_attr( get_option( 'raswp_zarinpal_amount', 200000 ) ); ?>" class="small-text" min="1000" step="1000" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Zarinpal Sandbox', 'wp-learn-word' ); ?></th>
						<td><label><input name="raswp_zarinpal_sandbox" type="checkbox" value="1" <?php checked( 1, (int) get_option( 'raswp_zarinpal_sandbox', 1 ) ); ?> /> <?php esc_html_e( 'Use sandbox environment', 'wp-learn-word' ); ?></label></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public static function render_import_page() {
		$books = get_posts( array(
			'post_type' => 'raswp_book',
			'numberposts' => -1,
			'post_status' => 'publish',
			'orderby' => 'title',
			'order' => 'ASC',
		) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import Words via CSV', 'wp-learn-word' ); ?></h1>
			<?php if ( isset( $_GET['imported'] ) ) : ?>
				<div class="notice notice-success"><p><?php echo esc_html( sprintf( __( 'Imported: %d, Skipped: %d', 'wp-learn-word' ), (int) $_GET['imported'], (int) $_GET['skipped'] ) ); ?></p></div>
			<?php endif; ?>
			<p><?php esc_html_e( 'Upload a CSV file with columns: word, translation, example. All rows will be assigned to the selected book.', 'wp-learn-word' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( 'raswp_import_csv', 'raswp_import_csv_nonce' ); ?>
				<input type="hidden" name="action" value="raswp_import_csv" />
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="raswp_book_id"><?php esc_html_e( 'Book', 'wp-learn-word' ); ?></label></th>
						<td>
							<select name="raswp_book_id" id="raswp_book_id">
								<?php foreach ( $books as $book ) : ?>
									<option value="<?php echo esc_attr( $book->ID ); ?>"><?php echo esc_html( $book->post_title ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Create books under WP Learn Word > Books.', 'wp-learn-word' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="raswp_csv_file"><?php esc_html_e( 'CSV File', 'wp-learn-word' ); ?></label></th>
						<td><input type="file" name="raswp_csv_file" id="raswp_csv_file" accept=".csv" required /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Import', 'wp-learn-word' ) ); ?>
			</form>
		</div>
		<?php
	}

	public static function handle_import_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wp-learn-word' ) );
		}
		if ( ! isset( $_POST['raswp_import_csv_nonce'] ) || ! wp_verify_nonce( $_POST['raswp_import_csv_nonce'], 'raswp_import_csv' ) ) {
			wp_die( esc_html__( 'Invalid nonce', 'wp-learn-word' ) );
		}
		$book_id = isset( $_POST['raswp_book_id'] ) ? (int) $_POST['raswp_book_id'] : 0;
		if ( $book_id <= 0 ) {
			wp_die( esc_html__( 'Please select a book.', 'wp-learn-word' ) );
		}
		if ( empty( $_FILES['raswp_csv_file']['tmp_name'] ) ) {
			wp_die( esc_html__( 'Please upload a CSV file.', 'wp-learn-word' ) );
		}

		$result = \RASWP_Importer::import_from_csv( $_FILES['raswp_csv_file']['tmp_name'], $book_id );

		$redirect = add_query_arg( array(
			'page' => 'raswp_import',
			'imported' => $result['imported'],
			'skipped' => $result['skipped'],
		), admin_url( 'admin.php' ) );

		wp_safe_redirect( $redirect );
		exit;
	}
}