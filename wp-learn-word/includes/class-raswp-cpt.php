<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RASWP_CPT {
	public static function register() {
		self::register_books_cpt();
		self::register_words_cpt();
	}

	private static function register_books_cpt() {
		$labels = array(
			'name' => __( 'Books', 'wp-learn-word' ),
			'singular_name' => __( 'Book', 'wp-learn-word' ),
		);

		$args = array(
			'labels' => $labels,
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_rest' => false,
			'supports' => array( 'title' ),
			'capability_type' => 'post',
			'has_archive' => false,
			'rewrite' => false,
			'menu_icon' => 'dashicons-book',
		);
		register_post_type( 'raswp_book', $args );
	}

	private static function register_words_cpt() {
		$labels = array(
			'name' => __( 'Words', 'wp-learn-word' ),
			'singular_name' => __( 'Word', 'wp-learn-word' ),
		);

		$args = array(
			'labels' => $labels,
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_rest' => false,
			'supports' => array( 'title' ),
			'capability_type' => 'post',
			'has_archive' => false,
			'rewrite' => false,
			'menu_icon' => 'dashicons-translation',
		);
		register_post_type( 'raswp_word', $args );

		add_action( 'add_meta_boxes', array( __CLASS__, 'add_word_metaboxes' ) );
		add_action( 'save_post_raswp_word', array( __CLASS__, 'save_word_meta' ) );
	}

	public static function add_word_metaboxes() {
		add_meta_box( 'raswp_word_details', __( 'Word Details', 'wp-learn-word' ), array( __CLASS__, 'render_word_details_meta' ), 'raswp_word', 'normal', 'default' );
	}

	public static function render_word_details_meta( $post ) {
		wp_nonce_field( 'raswp_save_word_meta', 'raswp_word_meta_nonce' );
		$translation = get_post_meta( $post->ID, 'raswp_translation', true );
		$example = get_post_meta( $post->ID, 'raswp_example', true );
		$book_id = (int) get_post_meta( $post->ID, 'raswp_book_id', true );

		$books = get_posts( array(
			'post_type' => 'raswp_book',
			'numberposts' => -1,
			'post_status' => 'publish',
			'orderby' => 'title',
			'order' => 'ASC',
		) );
		?>
		<p>
			<label for="raswp_translation"><strong><?php esc_html_e( 'Translation', 'wp-learn-word' ); ?></strong></label><br/>
			<input type="text" id="raswp_translation" name="raswp_translation" class="widefat" value="<?php echo esc_attr( $translation ); ?>" />
		</p>
		<p>
			<label for="raswp_example"><strong><?php esc_html_e( 'Example Sentence', 'wp-learn-word' ); ?></strong></label><br/>
			<textarea id="raswp_example" name="raswp_example" class="widefat" rows="3"><?php echo esc_textarea( $example ); ?></textarea>
		</p>
		<p>
			<label for="raswp_book_id"><strong><?php esc_html_e( 'Book', 'wp-learn-word' ); ?></strong></label><br/>
			<select id="raswp_book_id" name="raswp_book_id" class="widefat">
				<option value="0"><?php esc_html_e( '— Select —', 'wp-learn-word' ); ?></option>
				<?php foreach ( $books as $book ) : ?>
					<option value="<?php echo esc_attr( $book->ID ); ?>" <?php selected( $book_id, $book->ID ); ?>><?php echo esc_html( $book->post_title ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	public static function save_word_meta( $post_id ) {
		if ( ! isset( $_POST['raswp_word_meta_nonce'] ) || ! wp_verify_nonce( $_POST['raswp_word_meta_nonce'], 'raswp_save_word_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$translation = isset( $_POST['raswp_translation'] ) ? sanitize_text_field( wp_unslash( $_POST['raswp_translation'] ) ) : '';
		$example = isset( $_POST['raswp_example'] ) ? sanitize_textarea_field( wp_unslash( $_POST['raswp_example'] ) ) : '';
		$book_id = isset( $_POST['raswp_book_id'] ) ? (int) $_POST['raswp_book_id'] : 0;

		update_post_meta( $post_id, 'raswp_translation', $translation );
		update_post_meta( $post_id, 'raswp_example', $example );
		update_post_meta( $post_id, 'raswp_book_id', $book_id );
	}
}