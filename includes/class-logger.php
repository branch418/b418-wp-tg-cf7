<?php
/**
 * Lightweight activity log stored as a capped ring buffer in wp_options.
 *
 * @package Branch418\WpTgCf7
 */

namespace Branch418\WpTgCf7;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Logger {

	const OPTION = 'b418_wp_tg_cf7_log';

	/**
	 * Append a log entry (newest first).
	 *
	 * @param array $entry {form_title, chat_name, ok, error}.
	 */
	public static function log( $entry ) {
		$entry = array(
			'time'       => time(),
			'form_title' => sanitize_text_field( $entry['form_title'] ?? '' ),
			'chat_name'  => sanitize_text_field( $entry['chat_name'] ?? '' ),
			'ok'         => ! empty( $entry['ok'] ),
			'error'      => sanitize_text_field( $entry['error'] ?? '' ),
		);

		$log = self::get();
		array_unshift( $log, $entry );

		/**
		 * Filter how many log entries are retained.
		 *
		 * @param int $max Maximum entries kept.
		 */
		$max = (int) apply_filters( 'b418_wp_tg_cf7_log_max_entries', 50 );

		update_option( self::OPTION, array_slice( $log, 0, max( 1, $max ) ), false );
	}

	/**
	 * @return array Log entries, newest first.
	 */
	public static function get() {
		$log = get_option( self::OPTION, array() );

		return is_array( $log ) ? $log : array();
	}

	public static function clear() {
		delete_option( self::OPTION );
	}
}
