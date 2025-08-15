<?php
if (!defined('ABSPATH')) { exit; }

class RASWP_Admin {
	public static function raswp_register_admin() {
		add_action('admin_menu', [__CLASS__, 'raswp_admin_menu']);
		add_action('admin_init', [__CLASS__, 'raswp_register_settings']);
	}

	public static function raswp_admin_menu() {
		add_menu_page(
			__('WP Learn Word', 'wp-learn-word'),
			__('WP Learn Word', 'wp-learn-word'),
			'manage_options',
			'raswp',
			[__CLASS__, 'raswp_render_dashboard'],
			'dashicons-yes-alt'
		);

		add_submenu_page('raswp', __('Settings', 'wp-learn-word'), __('Settings', 'wp-learn-word'), 'manage_options', 'raswp-settings', [__CLASS__, 'raswp_render_settings']);
		add_submenu_page('raswp', __('CSV Import', 'wp-learn-word'), __('CSV Import', 'wp-learn-word'), 'manage_options', 'raswp-import', [__CLASS__, 'raswp_render_import']);
		add_submenu_page('raswp', __('Books', 'wp-learn-word'), __('Books', 'wp-learn-word'), 'manage_options', 'edit.php?post_type=raswp_book');
		add_submenu_page('raswp', __('Words', 'wp-learn-word'), __('Words', 'wp-learn-word'), 'manage_options', 'edit.php?post_type=raswp_word');
	}

	public static function raswp_register_settings() {
		register_setting('raswp_settings_group', 'raswp_settings', [
			'sanitize_callback' => [__CLASS__, 'raswp_sanitize_settings']
		]);
	}

	public static function raswp_sanitize_settings($input) {
		$output = get_option('raswp_settings', []);
		$output['words_per_session'] = isset($input['words_per_session']) ? max(1, intval($input['words_per_session'])) : 10;
		$output['free_words_limit'] = isset($input['free_words_limit']) ? max(0, intval($input['free_words_limit'])) : 20;
		$output['require_login'] = !empty($input['require_login']) ? 1 : 0;
		$output['leitner_intervals'] = isset($input['leitner_intervals']) ? sanitize_text_field($input['leitner_intervals']) : '1,2,4,8,16';
		$output['zarinpal_merchant_id'] = isset($input['zarinpal_merchant_id']) ? sanitize_text_field($input['zarinpal_merchant_id']) : '';
		$output['zarinpal_amount'] = isset($input['zarinpal_amount']) ? max(1000, intval($input['zarinpal_amount'])) : 100000;
		$output['zarinpal_sandbox'] = !empty($input['zarinpal_sandbox']) ? 1 : 0;
		$output['callback_page'] = isset($input['callback_page']) ? sanitize_title($input['callback_page']) : 'raswp-zarinpal';
		return $output;
	}

	public static function raswp_render_dashboard() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html(__('WP Learn Word', 'wp-learn-word')); ?></h1>
			<p><?php echo esc_html(__('Manage books, words, settings, and imports using the menu links.', 'wp-learn-word')); ?></p>
		</div>
		<?php
	}

	public static function raswp_render_settings() {
		$options = get_option('raswp_settings', []);
		?>
		<div class="wrap">
			<h1><?php echo esc_html(__('WP Learn Word Settings', 'wp-learn-word')); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields('raswp_settings_group'); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html(__('Words per session', 'wp-learn-word')); ?></th>
						<td><input type="number" name="raswp_settings[words_per_session]" value="<?php echo esc_attr($options['words_per_session'] ?? 10); ?>" min="1" /></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html(__('Free words limit (before paywall)', 'wp-learn-word')); ?></th>
						<td><input type="number" name="raswp_settings[free_words_limit]" value="<?php echo esc_attr($options['free_words_limit'] ?? 20); ?>" min="0" /></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html(__('Require login', 'wp-learn-word')); ?></th>
						<td><label><input type="checkbox" name="raswp_settings[require_login]" value="1" <?php checked(!empty($options['require_login'])); ?> /> <?php echo esc_html(__('Only logged-in users can study and buy', 'wp-learn-word')); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html(__('Leitner box intervals (days, comma separated)', 'wp-learn-word')); ?></th>
						<td><input type="text" name="raswp_settings[leitner_intervals]" value="<?php echo esc_attr($options['leitner_intervals'] ?? '1,2,4,8,16'); ?>" class="regular-text" /></td>
					</tr>
					<tr><th colspan="2"><h2><?php echo esc_html(__('Zarinpal Settings', 'wp-learn-word')); ?></h2></th></tr>
					<tr>
						<th scope="row"><?php echo esc_html(__('Merchant ID', 'wp-learn-word')); ?></th>
						<td><input type="text" name="raswp_settings[zarinpal_merchant_id]" value="<?php echo esc_attr($options['zarinpal_merchant_id'] ?? ''); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html(__('Amount (IRR)', 'wp-learn-word')); ?></th>
						<td><input type="number" name="raswp_settings[zarinpal_amount]" value="<?php echo esc_attr($options['zarinpal_amount'] ?? 100000); ?>" min="1000" /></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html(__('Sandbox Mode', 'wp-learn-word')); ?></th>
						<td><label><input type="checkbox" name="raswp_settings[zarinpal_sandbox]" value="1" <?php checked(!empty($options['zarinpal_sandbox'])); ?> /> <?php echo esc_html(__('Use Zarinpal sandbox', 'wp-learn-word')); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html(__('Callback slug', 'wp-learn-word')); ?></th>
						<td><input type="text" name="raswp_settings[callback_page]" value="<?php echo esc_attr($options['callback_page'] ?? 'raswp-zarinpal'); ?>" class="regular-text" /> <p class="description"><?php echo esc_html(__('Full callback URL will be like: ', 'wp-learn-word')); ?><?php echo esc_url(site_url('?raswp_zarinpal_callback=1')); ?></p></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public static function raswp_render_import() {
		$notice = '';
		if (!empty($_POST['raswp_import_submit'])) {
			check_admin_referer('raswp_import_csv');
			$book_id = !empty($_POST['raswp_book_id']) ? intval($_POST['raswp_book_id']) : 0;
			$new_book_name = !empty($_POST['raswp_new_book']) ? sanitize_text_field($_POST['raswp_new_book']) : '';

			if ($new_book_name) {
				$book_id = wp_insert_post([
					'post_type' => 'raswp_book',
					'post_status' => 'publish',
					'post_title' => $new_book_name
				]);
			}

			if (!$book_id) {
				$notice = __('Please select or create a book.', 'wp-learn-word');
			} else if (!empty($_FILES['raswp_csv']['tmp_name'])) {
				$handle = fopen($_FILES['raswp_csv']['tmp_name'], 'r');
				if ($handle) {
					$imported = 0;
					while (($row = fgetcsv($handle)) !== false) {
						if (count($row) < 2) { continue; }
						$word = sanitize_text_field($row[0]);
						$translation = sanitize_text_field($row[1]);
						$example = isset($row[2]) ? sanitize_textarea_field($row[2]) : '';
						if (!$word) { continue; }
						$post_id = wp_insert_post([
							'post_type' => 'raswp_word',
							'post_status' => 'publish',
							'post_title' => $word
						]);
						if ($post_id && !is_wp_error($post_id)) {
							update_post_meta($post_id, 'raswp_translation', $translation);
							if ($example) { update_post_meta($post_id, 'raswp_example', $example); }
							update_post_meta($post_id, 'raswp_book_id', $book_id);
							$imported++;
						}
					}
					fclose($handle);
					$notice = sprintf(esc_html__('%d words imported successfully.', 'wp-learn-word'), $imported);
				} else {
					$notice = esc_html__('Unable to open the CSV file.', 'wp-learn-word');
				}
			} else {
				$notice = esc_html__('Please upload a CSV file.', 'wp-learn-word');
			}
		}

		$books = get_posts(['post_type' => 'raswp_book', 'numberposts' => -1, 'post_status' => 'publish']);
		?>
		<div class="wrap">
			<h1><?php echo esc_html(__('CSV Import', 'wp-learn-word')); ?></h1>
			<?php if ($notice): ?><div class="notice notice-info"><p><?php echo esc_html($notice); ?></p></div><?php endif; ?>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field('raswp_import_csv'); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th><?php echo esc_html(__('CSV File', 'wp-learn-word')); ?></th>
						<td><input type="file" name="raswp_csv" accept=".csv" required /></td>
					</tr>
					<tr>
						<th><?php echo esc_html(__('Book', 'wp-learn-word')); ?></th>
						<td>
							<select name="raswp_book_id">
								<option value=""><?php echo esc_html(__('— Select —', 'wp-learn-word')); ?></option>
								<?php foreach ($books as $book): ?>
									<option value="<?php echo esc_attr($book->ID); ?>"><?php echo esc_html(get_the_title($book)); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php echo esc_html(__('Or create new book', 'wp-learn-word')); ?></th>
						<td><input type="text" name="raswp_new_book" placeholder="<?php echo esc_attr(__('New Book Name', 'wp-learn-word')); ?>" class="regular-text" /></td>
					</tr>
				</table>
				<p><button type="submit" name="raswp_import_submit" class="button button-primary" value="1"><?php echo esc_html(__('Import', 'wp-learn-word')); ?></button></p>
			</form>
			<p class="description"><?php echo esc_html(__('CSV columns: word, translation, example(optional). UTF-8 encoding recommended.', 'wp-learn-word')); ?></p>
		</div>
		<?php
	}
}