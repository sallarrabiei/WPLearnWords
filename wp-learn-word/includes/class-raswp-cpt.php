<?php
if (!defined('ABSPATH')) { exit; }

class RASWP_CPT {
	public static function raswp_register_cpts() {
		self::raswp_register_book_cpt();
		self::raswp_register_word_cpt();
		self::raswp_register_meta_boxes();
	}

	private static function raswp_register_book_cpt() {
		register_post_type('raswp_book', [
			'label' => __('Books', 'wp-learn-word'),
			'labels' => [
				'name' => __('Books', 'wp-learn-word'),
				'singular_name' => __('Book', 'wp-learn-word')
			],
			'public' => true,
			'show_in_menu' => 'raswp',
			'menu_icon' => 'dashicons-book-alt',
			'supports' => ['title', 'editor', 'thumbnail'],
			'has_archive' => false,
			'show_in_rest' => true
		]);
	}

	private static function raswp_register_word_cpt() {
		register_post_type('raswp_word', [
			'label' => __('Words', 'wp-learn-word'),
			'labels' => [
				'name' => __('Words', 'wp-learn-word'),
				'singular_name' => __('Word', 'wp-learn-word')
			],
			'public' => true,
			'show_in_menu' => 'raswp',
			'menu_icon' => 'dashicons-welcome-learn-more',
			'supports' => ['title'],
			'has_archive' => false,
			'show_in_rest' => false
		]);
	}

	public static function raswp_register_meta_boxes() {
		add_action('add_meta_boxes', function() {
			add_meta_box('raswp_word_meta', __('Word Details', 'wp-learn-word'), [__CLASS__, 'raswp_render_word_meta'], 'raswp_word', 'normal', 'default');
		});

		add_action('save_post_raswp_word', [__CLASS__, 'raswp_save_word_meta']);
	}

	public static function raswp_render_word_meta($post) {
		wp_nonce_field('raswp_save_word_meta', 'raswp_word_meta_nonce');
		$translation = get_post_meta($post->ID, 'raswp_translation', true);
		$example = get_post_meta($post->ID, 'raswp_example', true);
		$book_id = get_post_meta($post->ID, 'raswp_book_id', true);
		$books = get_posts(['post_type' => 'raswp_book', 'numberposts' => -1, 'post_status' => 'publish']);
		?>
		<p>
			<label for="raswp_translation"><strong><?php echo esc_html(__('Translation', 'wp-learn-word')); ?></strong></label><br/>
			<input type="text" name="raswp_translation" id="raswp_translation" value="<?php echo esc_attr($translation); ?>" class="widefat" />
		</p>
		<p>
			<label for="raswp_example"><strong><?php echo esc_html(__('Example Sentence', 'wp-learn-word')); ?></strong></label><br/>
			<textarea name="raswp_example" id="raswp_example" class="widefat" rows="3"><?php echo esc_textarea($example); ?></textarea>
		</p>
		<p>
			<label for="raswp_book_id"><strong><?php echo esc_html(__('Book', 'wp-learn-word')); ?></strong></label><br/>
			<select name="raswp_book_id" id="raswp_book_id" class="widefat">
				<option value=""><?php echo esc_html(__('— Select —', 'wp-learn-word')); ?></option>
				<?php foreach ($books as $book): ?>
					<option value="<?php echo esc_attr($book->ID); ?>" <?php selected($book_id, $book->ID); ?>><?php echo esc_html(get_the_title($book)); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	public static function raswp_save_word_meta($post_id) {
		if (!isset($_POST['raswp_word_meta_nonce']) || !wp_verify_nonce($_POST['raswp_word_meta_nonce'], 'raswp_save_word_meta')) {
			return;
		}
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		$translation = isset($_POST['raswp_translation']) ? sanitize_text_field($_POST['raswp_translation']) : '';
		$example = isset($_POST['raswp_example']) ? sanitize_textarea_field($_POST['raswp_example']) : '';
		$book_id = isset($_POST['raswp_book_id']) ? intval($_POST['raswp_book_id']) : 0;

		update_post_meta($post_id, 'raswp_translation', $translation);
		update_post_meta($post_id, 'raswp_example', $example);
		if ($book_id) {
			update_post_meta($post_id, 'raswp_book_id', $book_id);
		} else {
			delete_post_meta($post_id, 'raswp_book_id');
		}
	}
}