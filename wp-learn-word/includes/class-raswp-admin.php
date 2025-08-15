<?php
if (!defined('ABSPATH')) { exit; }

class RASWP_Admin {
	public static function raswp_register_admin() {
		add_action('admin_menu', [__CLASS__, 'raswp_admin_menu']);
		add_action('admin_init', [__CLASS__, 'raswp_register_settings']);
	}

	public static function raswp_admin_menu() {
		add_menu_page(
			__('یادگیری واژه‌ها', 'wp-learn-word'),
			__('یادگیری واژه‌ها', 'wp-learn-word'),
			'manage_options',
			'raswp',
			[__CLASS__, 'raswp_render_dashboard'],
			'dashicons-yes-alt'
		);

		add_submenu_page('raswp', __('تنظیمات', 'wp-learn-word'), __('تنظیمات', 'wp-learn-word'), 'manage_options', 'raswp-settings', [__CLASS__, 'raswp_render_settings']);
		add_submenu_page('raswp', __('درون‌ریزی CSV', 'wp-learn-word'), __('درون‌ریزی CSV', 'wp-learn-word'), 'manage_options', 'raswp-import', [__CLASS__, 'raswp_render_import']);
		add_submenu_page('raswp', __('کتاب‌ها', 'wp-learn-word'), __('کتاب‌ها', 'wp-learn-word'), 'manage_options', 'edit.php?post_type=raswp_book');
		add_submenu_page('raswp', __('واژه‌ها', 'wp-learn-word'), __('واژه‌ها', 'wp-learn-word'), 'manage_options', 'edit.php?post_type=raswp_word');
		add_submenu_page('raswp', __('مدیریت کاربران', 'wp-learn-word'), __('مدیریت کاربران', 'wp-learn-word'), 'manage_options', 'raswp-users', [__CLASS__, 'raswp_render_users']);
		add_submenu_page('raswp', __('اشتراک‌ها', 'wp-learn-word'), __('اشتراک‌ها', 'wp-learn-word'), 'manage_options', 'raswp-subscriptions', [__CLASS__, 'raswp_render_subscriptions']);
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
			<h1><?php echo esc_html(__('یادگیری واژه‌ها', 'wp-learn-word')); ?></h1>
			<p><?php echo esc_html(__('از منو می‌توانید واژه‌ها، کتاب‌ها، تنظیمات، درون‌ریزی و مدیریت اشتراک‌ها را انجام دهید.', 'wp-learn-word')); ?></p>
		</div>
		<?php
	}

	public static function raswp_render_settings() {
		$options = get_option('raswp_settings', []);
		?>
		<div class="wrap">
			<h1><?php echo esc_html(__('تنظیمات افزونه', 'wp-learn-word')); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields('raswp_settings_group'); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html(__('تعداد واژه در هر جلسه', 'wp-learn-word')); ?></th>
						<td><input type="number" name="raswp_settings[words_per_session]" value="<?php echo esc_attr($options['words_per_session'] ?? 10); ?>" min="1" /></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html(__('محدودیت رایگان (تعداد واژه‌های یکتای قابل مرور)', 'wp-learn-word')); ?></th>
						<td><input type="number" name="raswp_settings[free_words_limit]" value="<?php echo esc_attr($options['free_words_limit'] ?? 20); ?>" min="0" /></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html(__('نیاز به ورود', 'wp-learn-word')); ?></th>
						<td><label><input type="checkbox" name="raswp_settings[require_login]" value="1" <?php checked(!empty($options['require_login'])); ?> /> <?php echo esc_html(__('تنها کاربران وارد شده می‌توانند مطالعه و خرید کنند', 'wp-learn-word')); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html(__('فواصل جعبه‌های لایتنر (روز، با کاما جدا شود)', 'wp-learn-word')); ?></th>
						<td><input type="text" name="raswp_settings[leitner_intervals]" value="<?php echo esc_attr($options['leitner_intervals'] ?? '1,2,4,8,16'); ?>" class="regular-text" /></td>
					</tr>
					<tr><th colspan="2"><h2><?php echo esc_html(__('تنظیمات زرین‌پال', 'wp-learn-word')); ?></h2></th></tr>
					<tr>
						<th scope="row"><?php echo esc_html(__('مرچنت کد', 'wp-learn-word')); ?></th>
						<td><input type="text" name="raswp_settings[zarinpal_merchant_id]" value="<?php echo esc_attr($options['zarinpal_merchant_id'] ?? ''); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html(__('مبلغ (ریال)', 'wp-learn-word')); ?></th>
						<td><input type="number" name="raswp_settings[zarinpal_amount]" value="<?php echo esc_attr($options['zarinpal_amount'] ?? 100000); ?>" min="1000" /></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html(__('حالت آزمایشی (Sandbox)', 'wp-learn-word')); ?></th>
						<td><label><input type="checkbox" name="raswp_settings[zarinpal_sandbox]" value="1" <?php checked(!empty($options['zarinpal_sandbox'])); ?> /> <?php echo esc_html(__('استفاده از محیط سندباکس زرین‌پال', 'wp-learn-word')); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html(__('اسلاگ بازگشت', 'wp-learn-word')); ?></th>
						<td><input type="text" name="raswp_settings[callback_page]" value="<?php echo esc_attr($options['callback_page'] ?? 'raswp-zarinpal'); ?>" class="regular-text" /> <p class="description"><?php echo esc_html(__('آدرس بازگشت به شکل زیر خواهد بود: ', 'wp-learn-word')); ?><?php echo esc_url(site_url('?raswp_zarinpal_callback=1')); ?></p></td>
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
				$notice = __('لطفاً یک کتاب انتخاب یا ایجاد کنید.', 'wp-learn-word');
			} else if (!empty($_FILES['raswp_csv']['tmp_name'])) {
				$handle = fopen($_FILES['raswp_csv']['tmp_name'], 'r');
				if ($handle) {
					$imported = 0;
					while (($row = fgetcsv($handle)) !== false) {
						if (count($row) < 2) { continue; }
						$word = sanitize_text_field($row[0]);
						$translation = sanitize_text_field($row[1]);
						$example1 = isset($row[2]) ? sanitize_textarea_field($row[2]) : '';
						$example2 = isset($row[3]) ? sanitize_textarea_field($row[3]) : '';
						if (!$word) { continue; }
						$post_id = wp_insert_post([
							'post_type' => 'raswp_word',
							'post_status' => 'publish',
							'post_title' => $word
						]);
						if ($post_id && !is_wp_error($post_id)) {
							update_post_meta($post_id, 'raswp_translation', $translation);
							if ($example1) { update_post_meta($post_id, 'raswp_example', $example1); }
							if ($example2) { update_post_meta($post_id, 'raswp_example_2', $example2); }
							update_post_meta($post_id, 'raswp_book_id', $book_id);
							$imported++;
						}
					}
					fclose($handle);
					$notice = sprintf(esc_html__('%d واژه با موفقیت درون‌ریزی شد.', 'wp-learn-word'), $imported);
				} else {
					$notice = esc_html__('امکان باز کردن فایل CSV وجود ندارد.', 'wp-learn-word');
				}
			} else {
				$notice = esc_html__('لطفاً یک فایل CSV بارگذاری کنید.', 'wp-learn-word');
			}
		}

		$books = get_posts(['post_type' => 'raswp_book', 'numberposts' => -1, 'post_status' => 'publish']);
		?>
		<div class="wrap">
			<h1><?php echo esc_html(__('درون‌ریزی CSV', 'wp-learn-word')); ?></h1>
			<?php if ($notice): ?><div class="notice notice-info"><p><?php echo esc_html($notice); ?></p></div><?php endif; ?>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field('raswp_import_csv'); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th><?php echo esc_html(__('فایل CSV', 'wp-learn-word')); ?></th>
						<td><input type="file" name="raswp_csv" accept=".csv" required /></td>
					</tr>
					<tr>
						<th><?php echo esc_html(__('کتاب', 'wp-learn-word')); ?></th>
						<td>
							<select name="raswp_book_id">
								<option value=""><?php echo esc_html(__('— انتخاب —', 'wp-learn-word')); ?></option>
								<?php foreach ($books as $book): ?>
									<option value="<?php echo esc_attr($book->ID); ?>"><?php echo esc_html(get_the_title($book)); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php echo esc_html(__('یا ایجاد کتاب جدید', 'wp-learn-word')); ?></th>
						<td><input type="text" name="raswp_new_book" placeholder="<?php echo esc_attr(__('نام کتاب جدید', 'wp-learn-word')); ?>" class="regular-text" /></td>
					</tr>
				</table>
				<p><button type="submit" name="raswp_import_submit" class="button button-primary" value="1"><?php echo esc_html(__('درون‌ریزی', 'wp-learn-word')); ?></button></p>
			</form>
			<p class="description"><?php echo esc_html(__('ستون‌های CSV: واژه، ترجمه، جمله نمونه ۱، جمله نمونه ۲ (اختیاری). کدگذاری UTF-8 توصیه می‌شود.', 'wp-learn-word')); ?></p>
		</div>
		<?php
	}

	public static function raswp_render_users() {
		$notice = '';
		if (!empty($_POST['raswp_user_update'])) {
			check_admin_referer('raswp_update_user');
			$user_id = intval($_POST['user_id'] ?? 0);
			$is_premium = !empty($_POST['is_premium']) ? 1 : 0;
			$expires = sanitize_text_field($_POST['expires'] ?? '');
			$reset_used = !empty($_POST['reset_used']);
			if ($user_id) {
				update_user_meta($user_id, 'raswp_is_premium', $is_premium);
				if ($expires) { update_user_meta($user_id, 'raswp_premium_expires', $expires); } else { delete_user_meta($user_id, 'raswp_premium_expires'); }
				if ($reset_used) { update_user_meta($user_id, 'raswp_reviews_used', 0); }
				$notice = __('اطلاعات کاربر ذخیره شد.', 'wp-learn-word');
			}
		}

		$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
		$args = [ 'number' => 50 ];
		if ($search) {
			$args['search'] = '*' . $search . '*';
			$args['search_columns'] = ['user_login', 'user_email', 'display_name'];
		}
		$users = get_users($args);
		?>
		<div class="wrap">
			<h1><?php echo esc_html(__('مدیریت کاربران', 'wp-learn-word')); ?></h1>
			<form method="get" style="margin-bottom:10px;">
				<input type="hidden" name="page" value="raswp-users" />
				<input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr(__('جستجوی کاربر (نام کاربری/ایمیل/نام)', 'wp-learn-word')); ?>" />
				<button class="button"><?php echo esc_html(__('جستجو', 'wp-learn-word')); ?></button>
			</form>
			<?php if ($notice): ?><div class="notice notice-success"><p><?php echo esc_html($notice); ?></p></div><?php endif; ?>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php echo esc_html(__('کاربر', 'wp-learn-word')); ?></th>
						<th><?php echo esc_html(__('ایمیل', 'wp-learn-word')); ?></th>
						<th><?php echo esc_html(__('وضعیت اشتراک', 'wp-learn-word')); ?></th>
						<th><?php echo esc_html(__('تاریخ انقضا', 'wp-learn-word')); ?></th>
						<th><?php echo esc_html(__('اقدامات', 'wp-learn-word')); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($users as $u): $uid = $u->ID; $is_p = (bool) get_user_meta($uid, 'raswp_is_premium', true); $exp = get_user_meta($uid, 'raswp_premium_expires', true); ?>
					<tr>
						<td><?php echo esc_html($u->display_name . ' (' . $u->user_login . ')'); ?></td>
						<td><?php echo esc_html($u->user_email); ?></td>
						<td><?php echo $is_p ? '<span style="color:#0a0;">' . esc_html(__('پریمیوم', 'wp-learn-word')) . '</span>' : '<span style="color:#a00;">' . esc_html(__('عادی', 'wp-learn-word')) . '</span>'; ?></td>
						<td><?php echo esc_html($exp ?: '—'); ?></td>
						<td>
							<form method="post" style="display:flex;gap:8px;align-items:center;">
								<?php wp_nonce_field('raswp_update_user'); ?>
								<input type="hidden" name="raswp_user_update" value="1" />
								<input type="hidden" name="user_id" value="<?php echo esc_attr($uid); ?>" />
								<label><input type="checkbox" name="is_premium" value="1" <?php checked($is_p); ?> /> <?php echo esc_html(__('پریمیوم', 'wp-learn-word')); ?></label>
								<input type="date" name="expires" value="<?php echo esc_attr($exp); ?>" />
								<label><input type="checkbox" name="reset_used" value="1" /> <?php echo esc_html(__('ریست شمارنده رایگان', 'wp-learn-word')); ?></label>
								<button class="button button-primary"><?php echo esc_html(__('ذخیره', 'wp-learn-word')); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function raswp_render_subscriptions() {
		$notice = '';
		if (!empty($_POST['raswp_grant_sub'])) {
			check_admin_referer('raswp_grant_sub');
			$identifier = sanitize_text_field($_POST['user_identifier'] ?? '');
			$expires = sanitize_text_field($_POST['expires'] ?? '');
			$user = get_user_by('email', $identifier);
			if (!$user) { $user = get_user_by('login', $identifier); }
			if ($user) {
				update_user_meta($user->ID, 'raswp_is_premium', 1);
				if ($expires) { update_user_meta($user->ID, 'raswp_premium_expires', $expires); } else { delete_user_meta($user->ID, 'raswp_premium_expires'); }
				$notice = __('اشتراک برای کاربر فعال شد.', 'wp-learn-word');
			} else {
				$notice = __('کاربر یافت نشد.', 'wp-learn-word');
			}
		}

		global $wpdb; $orders_table = $wpdb->prefix . 'raswp_orders';
		$orders = $wpdb->get_results("SELECT * FROM {$orders_table} ORDER BY id DESC LIMIT 100");
		?>
		<div class="wrap">
			<h1><?php echo esc_html(__('اشتراک‌ها', 'wp-learn-word')); ?></h1>
			<?php if ($notice): ?><div class="notice notice-info"><p><?php echo esc_html($notice); ?></p></div><?php endif; ?>
			<h2><?php echo esc_html(__('اعطای اشتراک دستی', 'wp-learn-word')); ?></h2>
			<form method="post" style="margin-bottom:16px;display:flex;gap:8px;align-items:center;">
				<?php wp_nonce_field('raswp_grant_sub'); ?>
				<input type="hidden" name="raswp_grant_sub" value="1" />
				<input type="text" name="user_identifier" placeholder="<?php echo esc_attr(__('ایمیل یا نام کاربری', 'wp-learn-word')); ?>" class="regular-text" />
				<label><?php echo esc_html(__('انقضا (اختیاری)', 'wp-learn-word')); ?>: <input type="date" name="expires" /></label>
				<button class="button button-primary"><?php echo esc_html(__('اعطا', 'wp-learn-word')); ?></button>
			</form>

			<h2><?php echo esc_html(__('آخرین سفارش‌ها', 'wp-learn-word')); ?></h2>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php echo esc_html(__('شناسه', 'wp-learn-word')); ?></th>
						<th><?php echo esc_html(__('کاربر', 'wp-learn-word')); ?></th>
						<th><?php echo esc_html(__('مبلغ', 'wp-learn-word')); ?></th>
						<th><?php echo esc_html(__('کد Authority', 'wp-learn-word')); ?></th>
						<th><?php echo esc_html(__('کد پیگیری (RefID)', 'wp-learn-word')); ?></th>
						<th><?php echo esc_html(__('وضعیت', 'wp-learn-word')); ?></th>
						<th><?php echo esc_html(__('تاریخ', 'wp-learn-word')); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ($orders) foreach ($orders as $o): ?>
					<tr>
						<td><?php echo esc_html($o->id); ?></td>
						<td><?php echo esc_html($o->user_id); ?></td>
						<td><?php echo esc_html(number_format_i18n((int)$o->amount)); ?></td>
						<td><?php echo esc_html($o->authority ?: '—'); ?></td>
						<td><?php echo esc_html($o->ref_id ?: '—'); ?></td>
						<td><?php echo esc_html($o->status); ?></td>
						<td><?php echo esc_html($o->created_at); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}