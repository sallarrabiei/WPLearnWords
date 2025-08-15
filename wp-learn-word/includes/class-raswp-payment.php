<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RASWP_Payment {
	public static function init() {
		add_action( 'admin_post_raswp_start_payment', array( __CLASS__, 'start_payment' ) );
		add_action( 'admin_post_nopriv_raswp_start_payment', array( __CLASS__, 'require_login_and_redirect' ) );
	}

	public static function require_login_and_redirect() {
		$redirect = isset( $_REQUEST['_raswp_redirect'] ) ? esc_url_raw( wp_unslash( $_REQUEST['_raswp_redirect'] ) ) : home_url( '/' );
		wp_safe_redirect( wp_login_url( $redirect ) );
		exit;
	}

	public static function user_has_premium( $user_id ) {
		return (bool) get_user_meta( $user_id, 'raswp_is_premium', true );
	}

	public static function get_used_count( $user_id ) {
		return (int) get_user_meta( $user_id, 'raswp_reviews_used', true );
	}

	public static function increment_used_count( $user_id ) {
		if ( self::user_has_premium( $user_id ) ) {
			return;
		}
		$used = self::get_used_count( $user_id );
		update_user_meta( $user_id, 'raswp_reviews_used', $used + 1 );
	}

	public static function user_can_review( $user_id ) {
		if ( self::user_has_premium( $user_id ) ) {
			return true;
		}
		$limit = (int) get_option( 'raswp_free_review_limit', 20 );
		$used = self::get_used_count( $user_id );
		return $used < $limit;
	}

	public static function start_payment() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'raswp_start_payment' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'wp-learn-word' ) );
		}
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			self::require_login_and_redirect();
		}

		$amount = (int) get_option( 'raswp_zarinpal_amount', 200000 );
		$merchant_id = trim( (string) get_option( 'raswp_zarinpal_merchant_id', '' ) );
		$sandbox = (int) get_option( 'raswp_zarinpal_sandbox', 1 );
		if ( empty( $merchant_id ) ) {
			wp_die( esc_html__( 'Zarinpal Merchant ID is not set.', 'wp-learn-word' ) );
		}

		$callback_url = add_query_arg( array( 'raswp_zarinpal_callback' => '1' ), home_url( '/' ) );

		global $wpdb;
		$payments_table = $wpdb->prefix . 'raswp_payments';
		$now = gmdate( 'Y-m-d H:i:s' );
		$wpdb->insert( $payments_table, array(
			'user_id' => $user_id,
			'amount' => $amount,
			'status' => 'pending',
			'created_at' => $now,
			'updated_at' => $now,
		), array( '%d', '%d', '%s', '%s', '%s' ) );
		$payment_id = (int) $wpdb->insert_id;

		$api_base = $sandbox ? 'https://sandbox.zarinpal.com/pg/v4/payment' : 'https://api.zarinpal.com/pg/v4/payment';
		$req_url = $api_base . '/request.json';
		$body = array(
			"merchant_id" => $merchant_id,
			"amount" => $amount,
			"callback_url" => $callback_url,
			"description" => get_bloginfo( 'name' ) . ' - ' . __( 'WP Learn Word Upgrade', 'wp-learn-word' ),
			"metadata" => array(),
		);

		$response = wp_remote_post( $req_url, array(
			'timeout' => 30,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body' => wp_json_encode( $body ),
		) );

		if ( is_wp_error( $response ) ) {
			wp_die( esc_html__( 'Payment request failed. Try again later.', 'wp-learn-word' ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $code || empty( $data['data']['authority'] ) ) {
			wp_die( esc_html__( 'Payment request error. Check settings.', 'wp-learn-word' ) );
		}

		$authority = sanitize_text_field( $data['data']['authority'] );
		$wpdb->update( $payments_table, array( 'authority' => $authority, 'updated_at' => $now ), array( 'id' => $payment_id ), array( '%s', '%s' ), array( '%d' ) );

		$start_base = $sandbox ? 'https://sandbox.zarinpal.com/pg/StartPay/' : 'https://www.zarinpal.com/pg/StartPay/';
		$redirect_url = $start_base . $authority;
		wp_safe_redirect( $redirect_url );
		exit;
	}

	public static function handle_callback() {
		$status = isset( $_GET['Status'] ) ? sanitize_text_field( wp_unslash( $_GET['Status'] ) ) : '';
		$authority = isset( $_GET['Authority'] ) ? sanitize_text_field( wp_unslash( $_GET['Authority'] ) ) : '';
		if ( 'OK' !== $status || empty( $authority ) ) {
			self::redirect_with_notice( 'failed' );
		}

		$merchant_id = trim( (string) get_option( 'raswp_zarinpal_merchant_id', '' ) );
		$amount = (int) get_option( 'raswp_zarinpal_amount', 200000 );
		$sandbox = (int) get_option( 'raswp_zarinpal_sandbox', 1 );

		global $wpdb;
		$payments_table = $wpdb->prefix . 'raswp_payments';
		$payment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$payments_table} WHERE authority = %s ORDER BY id DESC LIMIT 1", $authority ) );
		if ( ! $payment ) {
			self::redirect_with_notice( 'failed' );
		}

		$api_base = $sandbox ? 'https://sandbox.zarinpal.com/pg/v4/payment' : 'https://api.zarinpal.com/pg/v4/payment';
		$verify_url = $api_base . '/verify.json';
		$body = array(
			"merchant_id" => $merchant_id,
			"amount" => $amount,
			"authority" => $authority,
		);
		$response = wp_remote_post( $verify_url, array(
			'timeout' => 30,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body' => wp_json_encode( $body ),
		) );
		if ( is_wp_error( $response ) ) {
			self::redirect_with_notice( 'failed' );
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		$now = gmdate( 'Y-m-d H:i:s' );
		if ( ! empty( $data['data']['ref_id'] ) && isset( $data['data']['code'] ) && 100 === (int) $data['data']['code'] ) {
			$ref_id = sanitize_text_field( (string) $data['data']['ref_id'] );
			$wpdb->update( $payments_table, array( 'status' => 'paid', 'ref_id' => $ref_id, 'updated_at' => $now ), array( 'id' => (int) $payment->id ), array( '%s', '%s', '%s' ), array( '%d' ) );
			update_user_meta( (int) $payment->user_id, 'raswp_is_premium', 1 );
			self::redirect_with_notice( 'success' );
		}
		$wpdb->update( $payments_table, array( 'status' => 'failed', 'updated_at' => $now ), array( 'id' => (int) $payment->id ), array( '%s', '%s' ), array( '%d' ) );
		self::redirect_with_notice( 'failed' );
	}

	private static function redirect_with_notice( $status ) {
		$redirect = add_query_arg( array( 'raswp_payment' => $status ), home_url( '/' ) );
		wp_safe_redirect( $redirect );
		exit;
	}
}