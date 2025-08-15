<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RASWP_Ajax {
	public static function init() {
		add_action( 'wp_ajax_raswp_get_session_words', array( __CLASS__, 'get_session_words' ) );
		add_action( 'wp_ajax_nopriv_raswp_get_session_words', array( __CLASS__, 'get_session_words' ) );

		add_action( 'wp_ajax_raswp_submit_answer', array( __CLASS__, 'submit_answer' ) );
	}

	private static function verify_nonce() {
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'raswp_ajax_nonce' ) ) {
			wp_send_json_error( array( 'code' => 'bad_nonce', 'message' => __( 'Security check failed.', 'wp-learn-word' ) ) );
		}
	}

	public static function get_session_words() {
		self::verify_nonce();

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( array( 'code' => 'not_logged_in', 'message' => __( 'Please log in to continue.', 'wp-learn-word' ) ) );
		}

		$limit = max( 1, (int) get_option( 'raswp_random_word_count', 10 ) );
		$book_id = isset( $_REQUEST['book_id'] ) ? (int) $_REQUEST['book_id'] : 0;
		$book_slug = isset( $_REQUEST['book'] ) ? sanitize_title( wp_unslash( $_REQUEST['book'] ) ) : '';
		if ( $book_id <= 0 && $book_slug ) {
			$book = get_page_by_path( $book_slug, OBJECT, 'raswp_book' );
			if ( $book ) {
				$book_id = (int) $book->ID;
			}
		}

		if ( ! RASWP_Payment::user_can_review( $user_id ) ) {
			wp_send_json_error( array( 'code' => 'need_upgrade', 'message' => __( 'Free limit reached. Please upgrade to continue.', 'wp-learn-word' ) ) );
		}

		$words = RASWP_Leitner::get_session_words( $user_id, $book_id, $limit );

		wp_send_json_success( array(
			'words' => $words,
			'stats' => array(
				'used' => RASWP_Payment::get_used_count( $user_id ),
				'limit' => (int) get_option( 'raswp_free_review_limit', 20 ),
				'is_premium' => RASWP_Payment::user_has_premium( $user_id ),
			),
		) );
	}

	public static function submit_answer() {
		self::verify_nonce();

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( array( 'code' => 'not_logged_in', 'message' => __( 'Please log in.', 'wp-learn-word' ) ) );
		}

		if ( ! RASWP_Payment::user_can_review( $user_id ) ) {
			wp_send_json_error( array( 'code' => 'need_upgrade', 'message' => __( 'Free limit reached. Please upgrade to continue.', 'wp-learn-word' ) ) );
		}

		$word_id = isset( $_POST['word_id'] ) ? (int) $_POST['word_id'] : 0;
		$is_correct = isset( $_POST['is_correct'] ) ? (int) $_POST['is_correct'] : 0;
		if ( $word_id <= 0 ) {
			wp_send_json_error( array( 'code' => 'bad_request', 'message' => __( 'Invalid word.', 'wp-learn-word' ) ) );
		}

		RASWP_Leitner::record_answer( $user_id, $word_id, (bool) $is_correct );
		RASWP_Payment::increment_used_count( $user_id );

		wp_send_json_success( array(
			'stats' => array(
				'used' => RASWP_Payment::get_used_count( $user_id ),
				'limit' => (int) get_option( 'raswp_free_review_limit', 20 ),
				'is_premium' => RASWP_Payment::user_has_premium( $user_id ),
			),
		) );
	}
}