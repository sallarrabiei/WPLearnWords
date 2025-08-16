<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RASWP_Importer {
	public static function import_from_csv( $filepath, $book_id ) {
		$imported = 0;
		$skipped = 0;

		if ( ! file_exists( $filepath ) ) {
			return array( 'imported' => 0, 'skipped' => 0 );
		}

		$handle = fopen( $filepath, 'r' );
		if ( ! $handle ) {
			return array( 'imported' => 0, 'skipped' => 0 );
		}

		while ( ( $data = fgetcsv( $handle ) ) !== false ) {
			if ( count( $data ) < 2 ) {
				$skipped++;
				continue;
			}
			$word = sanitize_text_field( $data[0] );
			$translation = sanitize_text_field( $data[1] );
			$example = isset( $data[2] ) ? sanitize_textarea_field( $data[2] ) : '';

			if ( '' === $word ) {
				$skipped++;
				continue;
			}

			$post_id = wp_insert_post( array(
				'post_type' => 'raswp_word',
				'post_status' => 'publish',
				'post_title' => $word,
			) );

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				$skipped++;
				continue;
			}

			update_post_meta( $post_id, 'raswp_translation', $translation );
			update_post_meta( $post_id, 'raswp_example', $example );
			update_post_meta( $post_id, 'raswp_book_id', (int) $book_id );

			$imported++;
		}
		fclose( $handle );

		return array( 'imported' => $imported, 'skipped' => $skipped );
	}
}