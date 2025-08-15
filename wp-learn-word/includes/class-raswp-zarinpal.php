<?php
if (!defined('ABSPATH')) { exit; }

class RASWP_Zarinpal {
	public static function raswp_register_routes() {
		add_action('template_redirect', [__CLASS__, 'raswp_maybe_handle_callback']);
	}

	private static function raswp_get_options() {
		return get_option('raswp_settings', []);
	}

	public static function raswp_start_payment() {
		check_ajax_referer('raswp_nonce', 'nonce');
		if (!is_user_logged_in()) { wp_send_json_error(['message' => __('Login required', 'wp-learn-word')]); }
		$user_id = get_current_user_id();
		$options = self::raswp_get_options();
		$merchant_id = $options['zarinpal_merchant_id'] ?? '';
		$amount = intval($options['zarinpal_amount'] ?? 100000);
		$sandbox = !empty($options['zarinpal_sandbox']);
		$callback_url = site_url('?raswp_zarinpal_callback=1');
		$desc = __('WP Learn Word Upgrade', 'wp-learn-word');

		if (!$merchant_id) {
			wp_send_json_error(['message' => __('Zarinpal is not configured.', 'wp-learn-word')]);
		}

		$endpoint = $sandbox ? 'https://sandbox.zarinpal.com/pg/v4/payment/request.json' : 'https://api.zarinpal.com/pg/v4/payment/request.json';

		$body = [
			'merchant_id' => $merchant_id,
			'amount' => $amount,
			'description' => $desc,
			'callback_url' => $callback_url,
			'metadata' => [ 'email' => wp_get_current_user()->user_email ]
		];

		$response = wp_remote_post($endpoint, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body' => wp_json_encode($body),
			'timeout' => 20
		]);

		if (is_wp_error($response)) {
			wp_send_json_error(['message' => $response->get_error_message()]);
		}
		$code = wp_remote_retrieve_response_code($response);
		$data = json_decode(wp_remote_retrieve_body($response), true);

		if ($code !== 200 || empty($data['data']['authority'])) {
			$error_msg = !empty($data['errors']) ? wp_json_encode($data['errors']) : __('Unexpected error', 'wp-learn-word');
			wp_send_json_error(['message' => $error_msg]);
		}

		$authority = sanitize_text_field($data['data']['authority']);

		// Store order
		global $wpdb;
		$table = $wpdb->prefix . 'raswp_orders';
		$now = current_time('mysql');
		$wpdb->insert($table, [
			'user_id' => $user_id,
			'amount' => $amount,
			'autority' => $authority,
			'status' => 'pending',
			'created_at' => $now,
			'updated_at' => $now,
		]);

		$start_url = $sandbox ? 'https://sandbox.zarinpal.com/pg/StartPay/' : 'https://www.zarinpal.com/pg/StartPay/';
		wp_send_json_success(['redirect' => $start_url . $authority]);
	}

	public static function raswp_maybe_handle_callback() {
		if (!isset($_GET['raswp_zarinpal_callback'])) { return; }

		$options = self::raswp_get_options();
		$sandbox = !empty($options['zarinpal_sandbox']);
		$merchant_id = $options['zarinpal_merchant_id'] ?? '';
		$amount = intval($options['zarinpal_amount'] ?? 100000);

		$authority = isset($_GET['Authority']) ? sanitize_text_field($_GET['Authority']) : '';
		$status = isset($_GET['Status']) ? sanitize_text_field($_GET['Status']) : '';

		if (!$authority || strtoupper($status) !== 'OK') {
			wp_die(esc_html__('Payment canceled or failed.', 'wp-learn-word'));
		}

		$endpoint = $sandbox ? 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json' : 'https://api.zarinpal.com/pg/v4/payment/verify.json';

		$body = [
			'merchant_id' => $merchant_id,
			'amount' => $amount,
			'authority' => $authority
		];

		$response = wp_remote_post($endpoint, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body' => wp_json_encode($body),
			'timeout' => 20
		]);
		if (is_wp_error($response)) {
			wp_die(esc_html($response->get_error_message()));
		}
		$data = json_decode(wp_remote_retrieve_body($response), true);
		if (!empty($data['data']) && !empty($data['data']['ref_id'])) {
			$ref_id = sanitize_text_field((string)$data['data']['ref_id']);

			// Mark order and user premium
			global $wpdb;
			$orders = $wpdb->prefix . 'raswp_orders';
			$wpdb->update($orders, [ 'status' => 'paid', 'ref_id' => $ref_id, 'updated_at' => current_time('mysql') ], [ 'authority' => $authority ]);
			$order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$orders} WHERE authority = %s", $authority));
			if ($order && $order->user_id) {
				update_user_meta((int)$order->user_id, 'raswp_is_premium', 1);
			}

			wp_die(esc_html(sprintf(__('Payment successful. RefID: %s', 'wp-learn-word'), $ref_id)), '', ['response' => 200]);
		} else {
			$error = !empty($data['errors']) ? wp_json_encode($data['errors']) : __('Verification failed', 'wp-learn-word');
			wp_die(esc_html($error));
		}
	}
}