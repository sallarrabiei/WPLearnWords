<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RASWP_Leitner {
	public static function get_session_words( $user_id, $book_id = 0, $limit = 10 ) {
		$word_ids = self::get_due_word_ids( $user_id, $book_id, $limit * 3 );
		if ( empty( $word_ids ) ) {
			return array();
		}
		shuffle( $word_ids );
		$word_ids = array_slice( $word_ids, 0, $limit );

		$words = array();
		foreach ( $word_ids as $wid ) {
			$post = get_post( $wid );
			if ( ! $post || 'raswp_word' !== $post->post_type || 'publish' !== $post->post_status ) {
				continue;
			}
			$words[] = array(
				'id' => $post->ID,
				'word' => $post->post_title,
				'translation' => (string) get_post_meta( $post->ID, 'raswp_translation', true ),
				'example' => (string) get_post_meta( $post->ID, 'raswp_example', true ),
			);
		}
		return $words;
	}

	public static function get_due_word_ids( $user_id, $book_id = 0, $limit = 30 ) {
		global $wpdb;
		$posts_table = $wpdb->posts;
		$postmeta_table = $wpdb->postmeta;
		$progress_table = $wpdb->prefix . 'raswp_user_progress';

		$params = array( $user_id );
		$join_book = '';
		$where_book = '';
		if ( $book_id > 0 ) {
			$join_book = "LEFT JOIN {$postmeta_table} AS pm ON pm.post_id = p.ID";
			$where_book = "AND pm.meta_key = 'raswp_book_id' AND pm.meta_value = %d";
			$params[] = $book_id;
		}

		$sql = "SELECT DISTINCT p.ID
			FROM {$posts_table} AS p
			{$join_book}
			LEFT JOIN {$progress_table} AS up ON up.word_id = p.ID AND up.user_id = %d
			WHERE p.post_type = 'raswp_word'
			AND p.post_status = 'publish'
			{$where_book}
			AND (
				up.id IS NULL
				OR up.next_review_at IS NULL
				OR up.next_review_at <= %s
			)
			ORDER BY RAND()
			LIMIT %d";

		$params[] = current_time( 'mysql', 1 );
		$params[] = $limit;

		$prepared = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $params ) );
		$ids = $wpdb->get_col( $prepared );
		return array_map( 'intval', $ids );
	}

	public static function record_answer( $user_id, $word_id, $is_correct ) {
		global $wpdb;
		$progress_table = $wpdb->prefix . 'raswp_user_progress';

		$intervals = get_option( 'raswp_box_intervals', array( 1, 3, 7, 14, 30 ) );
		$max_box = max( 1, count( $intervals ) );

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$progress_table} WHERE user_id = %d AND word_id = %d", $user_id, $word_id ) );

		$box = 1;
		$correct = 0;
		$incorrect = 0;
		if ( $row ) {
			$box = (int) $row->box;
			$correct = (int) $row->correct_count;
			$incorrect = (int) $row->incorrect_count;
		}

		if ( $is_correct ) {
			$box = min( $max_box, $box + 1 );
			$correct++;
		} else {
			$box = 1;
			$incorrect++;
		}

		$days = (int) $intervals[ max( 0, $box - 1 ) ];
		$next_review = gmdate( 'Y-m-d H:i:s', time() + ( $days * DAY_IN_SECONDS ) );
		$now = gmdate( 'Y-m-d H:i:s' );

		if ( $row ) {
			$wpdb->update(
				$progress_table,
				array(
					'box' => $box,
					'next_review_at' => $next_review,
					'correct_count' => $correct,
					'incorrect_count' => $incorrect,
					'last_reviewed' => $now,
				),
				array( 'id' => (int) $row->id ),
				array( '%d', '%s', '%d', '%d', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$progress_table,
				array(
					'user_id' => $user_id,
					'word_id' => $word_id,
					'box' => $box,
					'next_review_at' => $next_review,
					'correct_count' => $correct,
					'incorrect_count' => $incorrect,
					'last_reviewed' => $now,
				),
				array( '%d', '%d', '%d', '%s', '%d', '%d', '%s' )
			);
		}
	}
}