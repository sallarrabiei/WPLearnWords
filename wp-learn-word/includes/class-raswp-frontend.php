<?php
if (!defined('ABSPATH')) { exit; }

class RASWP_Frontend {
	public static function raswp_bootstrap_frontend() {
		add_action('wp_enqueue_scripts', [__CLASS__, 'raswp_enqueue_assets']);
		add_action('wp_ajax_raswp_get_words', [__CLASS__, 'raswp_get_words']);
		add_action('wp_ajax_nopriv_raswp_get_words', [__CLASS__, 'raswp_get_words']);
		add_action('wp_ajax_raswp_update_progress', [__CLASS__, 'raswp_update_progress']);
		add_action('wp_ajax_nopriv_raswp_update_progress', [__CLASS__, 'raswp_update_progress']);
		add_action('wp_ajax_raswp_start_payment', ['RASWP_Zarinpal', 'raswp_start_payment']);
	}

	public static function raswp_enqueue_assets() {
		wp_register_style('raswp-frontend', RASWP_PLUGIN_URL . 'assets/css/raswp-frontend.css', [], RASWP_VERSION);
		wp_register_script('raswp-frontend', RASWP_PLUGIN_URL . 'assets/js/raswp-frontend.js', ['jquery'], RASWP_VERSION, true);

		$options = get_option('raswp_settings', []);
		$books = get_posts(['post_type' => 'raswp_book', 'numberposts' => -1, 'post_status' => 'publish', 'fields' => 'ids']);
		$book_list = [];
		foreach ($books as $book_id) {
			$book_list[] = ['id' => $book_id, 'title' => get_the_title($book_id)];
		}

		wp_localize_script('raswp-frontend', 'raswp_data', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('raswp_nonce'),
			'words_per_session' => intval($options['words_per_session'] ?? 10),
			'free_words_limit' => intval($options['free_words_limit'] ?? 20),
			'is_logged_in' => is_user_logged_in() ? 1 : 0,
			'is_premium' => self::raswp_is_premium(get_current_user_id()) ? 1 : 0,
			'books' => $book_list,
			'paywall_msg' => __('You have reached the free limit. Please upgrade to continue.', 'wp-learn-word')
		]);
	}

	public static function raswp_render_shortcode($atts = []) {
		$options = get_option('raswp_settings', []);
		$require_login = !empty($options['require_login']);
		if ($require_login && !is_user_logged_in()) {
			return '<div class="raswp-container"><p>' . esc_html__('Please log in to study.', 'wp-learn-word') . '</p></div>';
		}
		wp_enqueue_style('raswp-frontend');
		wp_enqueue_script('raswp-frontend');
		ob_start();
		?>
		<div class="raswp-container" id="raswp-app">
			<div class="raswp-controls">
				<label><?php echo esc_html(__('Book', 'wp-learn-word')); ?>: 
					<select id="raswp-book-select"></select>
				</label>
				<button id="raswp-start" class="raswp-btn"><?php echo esc_html(__('Start Review', 'wp-learn-word')); ?></button>
			</div>
			<div id="raswp-session" class="raswp-session" style="display:none;">
				<div class="raswp-card">
					<div class="raswp-word" id="raswp-word"></div>
					<div class="raswp-translation" id="raswp-translation" style="display:none;"></div>
					<div class="raswp-example" id="raswp-example" style="display:none;"></div>
				</div>
				<div class="raswp-actions">
					<button id="raswp-show" class="raswp-btn"><?php echo esc_html(__('Show Answer', 'wp-learn-word')); ?></button>
					<button id="raswp-knew" class="raswp-btn success" style="display:none;"><?php echo esc_html(__('I knew it', 'wp-learn-word')); ?></button>
					<button id="raswp-forgot" class="raswp-btn danger" style="display:none;"><?php echo esc_html(__('I forgot', 'wp-learn-word')); ?></button>
				</div>
				<div class="raswp-progress" id="raswp-progress"></div>
			</div>
			<div id="raswp-paywall" class="raswp-paywall" style="display:none;">
				<p><?php echo esc_html(__('You have reached the free limit. Please upgrade to continue.', 'wp-learn-word')); ?></p>
				<button id="raswp-upgrade" class="raswp-btn primary"><?php echo esc_html(__('Pay with Zarinpal', 'wp-learn-word')); ?></button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function raswp_is_premium($user_id) {
		if (!$user_id) { return false; }
		return (bool) get_user_meta($user_id, 'raswp_is_premium', true);
	}

	public static function raswp_get_words() {
		check_ajax_referer('raswp_nonce', 'nonce');
		$options = get_option('raswp_settings', []);
		$words_per_session = intval($options['words_per_session'] ?? 10);
		$user_id = get_current_user_id();
		$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;

		if (!empty($options['require_login']) && !$user_id) {
			wp_send_json_error(['message' => __('Login required', 'wp-learn-word')]);
		}

		// Paywall check
		$free_limit = intval($options['free_words_limit'] ?? 0);
		if ($free_limit > 0 && !self::raswp_is_premium($user_id)) {
			$studied_count = self::raswp_count_user_unique_words($user_id);
			if ($studied_count >= $free_limit) {
				wp_send_json_success(['paywall' => true]);
			}
		}

		$words = self::raswp_pick_due_words($user_id, $book_id, $words_per_session, $options);
		wp_send_json_success(['paywall' => false, 'words' => $words]);
	}

	private static function raswp_count_user_unique_words($user_id) {
		if (!$user_id) { return 0; }
		global $wpdb;
		$table = $wpdb->prefix . 'raswp_progress';
		$count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT word_id) FROM {$table} WHERE user_id = %d", $user_id));
		return $count;
	}

	private static function raswp_pick_due_words($user_id, $book_id, $limit, $options) {
		$intervals = array_map('intval', explode(',', $options['leitner_intervals'] ?? '1,2,4,8,16'));
		$intervals = array_values(array_filter($intervals, function($v){ return $v > 0; }));
		if (empty($intervals)) { $intervals = [1,2,4,8,16]; }

		global $wpdb;
		$progress_table = $wpdb->prefix . 'raswp_progress';

		$now = current_time('timestamp');
		$due_ids = [];

		// Fetch due words from progress
		$where_book = $book_id ? $wpdb->prepare(' AND p.book_id = %d', $book_id) : '';
		$rows = $wpdb->get_results($wpdb->prepare("SELECT p.word_id, p.leitner_box, p.last_reviewed FROM {$progress_table} p WHERE p.user_id = %d {$where_book}", $user_id));
		foreach ($rows as $row) {
			$box_index = max(1, (int)$row->leitner_box);
			$days = $intervals[min($box_index - 1, count($intervals) - 1)];
			$next_time = strtotime("+{$days} days", strtotime($row->last_reviewed ?: '1970-01-01'));
			if ($next_time <= $now) {
				$due_ids[] = (int)$row->word_id;
			}
		}

		$due_ids = array_unique($due_ids);

		// If not enough due, fill with new words in book
		$needed = max(0, $limit - count($due_ids));
		$new_ids = [];
		if ($needed > 0) {
			$exclusion = $user_id ? $wpdb->get_col($wpdb->prepare("SELECT word_id FROM {$progress_table} WHERE user_id = %d", $user_id)) : [];
			$exclusion = array_map('intval', $exclusion);
			$args = [
				'post_type' => 'raswp_word',
				'posts_per_page' => $limit * 3,
				'post_status' => 'publish',
				'fields' => 'ids',
				'orderby' => 'rand',
			];
			if ($book_id) {
				$args['meta_query'] = [[
					'key' => 'raswp_book_id',
					'value' => $book_id,
					'compare' => '='
				]];
			}
			$ids = get_posts($args);
			foreach ($ids as $id) {
				if (!in_array($id, $exclusion, true) && !in_array($id, $due_ids, true)) {
					$new_ids[] = $id;
					if (count($new_ids) >= $needed) { break; }
				}
			}
		}

		$selected_ids = array_slice(array_merge($due_ids, $new_ids), 0, $limit);
		shuffle($selected_ids);

		$result = [];
		foreach ($selected_ids as $wid) {
			$result[] = [
				'id' => $wid,
				'word' => get_the_title($wid),
				'translation' => (string) get_post_meta($wid, 'raswp_translation', true),
				'example' => (string) get_post_meta($wid, 'raswp_example', true)
			];
		}
		return $result;
	}

	public static function raswp_update_progress() {
		check_ajax_referer('raswp_nonce', 'nonce');
		$user_id = get_current_user_id();
		if (!$user_id) { wp_send_json_error(['message' => __('Login required', 'wp-learn-word')]); }
		$word_id = isset($_POST['word_id']) ? intval($_POST['word_id']) : 0;
		$book_id = intval(get_post_meta($word_id, 'raswp_book_id', true));
		$knew = !empty($_POST['knew']);

		global $wpdb;
		$table = $wpdb->prefix . 'raswp_progress';

		$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d AND word_id = %d", $user_id, $word_id));
		$now_mysql = current_time('mysql');
		if ($row) {
			$new_box = $knew ? ((int)$row->leitner_box + 1) : 1;
			$new_box = max(1, min($new_box, 5));
			$wpdb->update($table, [
				'leitner_box' => $new_box,
				'last_reviewed' => $now_mysql,
				'times_correct' => (int)$row->times_correct + ($knew ? 1 : 0),
				'times_wrong' => (int)$row->times_wrong + ($knew ? 0 : 1),
				'updated_at' => $now_mysql,
			], [
				'id' => (int)$row->id
			]);
		} else {
			$wpdb->insert($table, [
				'user_id' => $user_id,
				'word_id' => $word_id,
				'book_id' => $book_id,
				'leitner_box' => $knew ? 2 : 1,
				'last_reviewed' => $now_mysql,
				'times_correct' => $knew ? 1 : 0,
				'times_wrong' => $knew ? 0 : 1,
				'created_at' => $now_mysql,
				'updated_at' => $now_mysql,
			]);
		}
		wp_send_json_success(['ok' => true]);
	}
}