<?php
/**
 * Options-backed repositories for bots, chats, templates and routing rules.
 *
 * All collections are stored as arrays of associative arrays in wp_options.
 * Every item is sanitized on write. IDs are generated server-side.
 *
 * Pro extensions can register additional collections via the
 * `b418_wp_tg_cf7_collections` filter.
 *
 * @package Branch418\WpTgCf7
 */

namespace Branch418\WpTgCf7;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	const OPT_PREFIX = 'b418_wp_tg_cf7_';

	/**
	 * Collection definitions: option name + sanitize callback per item type.
	 *
	 * @return array
	 */
	private static function collections() {
		$collections = array(
			'bot'      => array(
				'option'   => self::OPT_PREFIX . 'bots',
				'sanitize' => array( self::class, 'sanitize_bot' ),
			),
			'chat'     => array(
				'option'   => self::OPT_PREFIX . 'chats',
				'sanitize' => array( self::class, 'sanitize_chat' ),
			),
			'template' => array(
				'option'   => self::OPT_PREFIX . 'templates',
				'sanitize' => array( self::class, 'sanitize_template' ),
			),
			'rule'     => array(
				'option'   => self::OPT_PREFIX . 'rules',
				'sanitize' => array( self::class, 'sanitize_rule' ),
			),
		);

		return apply_filters( 'b418_wp_tg_cf7_collections', $collections );
	}

	/**
	 * @param string $type Collection type (bot|chat|template|rule).
	 * @return array List of items.
	 */
	public static function get_items( $type ) {
		$collections = self::collections();
		if ( ! isset( $collections[ $type ] ) ) {
			return array();
		}
		$items = get_option( $collections[ $type ]['option'], array() );

		return is_array( $items ) ? array_values( $items ) : array();
	}

	/**
	 * @param string $type Collection type.
	 * @param string $id   Item id.
	 * @return array|null
	 */
	public static function find( $type, $id ) {
		foreach ( self::get_items( $type ) as $item ) {
			if ( isset( $item['id'] ) && $item['id'] === $id ) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * Insert or update an item. Assigns an id when missing.
	 *
	 * @param string $type Collection type.
	 * @param array  $item Raw item data.
	 * @return array|\WP_Error Sanitized item as stored.
	 */
	public static function upsert( $type, $item ) {
		$collections = self::collections();
		if ( ! isset( $collections[ $type ] ) || ! is_array( $item ) ) {
			return new \WP_Error( 'invalid_type', __( 'Unknown data type.', 'b418-tg-cf7' ) );
		}

		$item = call_user_func( $collections[ $type ]['sanitize'], $item );

		if ( empty( $item['id'] ) ) {
			$item['id'] = $type . '_' . substr( md5( wp_generate_uuid4() ), 0, 12 );
		}

		$items = self::get_items( $type );
		$found = false;

		foreach ( $items as $index => $existing ) {
			if ( $existing['id'] === $item['id'] ) {
				$items[ $index ] = $item;
				$found           = true;
				break;
			}
		}

		if ( ! $found ) {
			$items[] = $item;
		}

		update_option( $collections[ $type ]['option'], $items, false );

		return $item;
	}

	/**
	 * @param string $type Collection type.
	 * @param string $id   Item id.
	 * @return bool
	 */
	public static function delete( $type, $id ) {
		$collections = self::collections();
		if ( ! isset( $collections[ $type ] ) ) {
			return false;
		}

		$items    = self::get_items( $type );
		$filtered = array_values(
			array_filter(
				$items,
				static function ( $item ) use ( $id ) {
					return ! isset( $item['id'] ) || $item['id'] !== $id;
				}
			)
		);

		if ( count( $filtered ) === count( $items ) ) {
			return false;
		}

		update_option( $collections[ $type ]['option'], $filtered, false );

		return true;
	}

	/**
	 * Option names owned by the plugin (used by uninstall).
	 *
	 * @return array
	 */
	public static function option_names() {
		$names = array( self::OPT_PREFIX . 'log' );
		foreach ( self::collections() as $collection ) {
			$names[] = $collection['option'];
		}

		return $names;
	}

	/* ── Sanitizers ─────────────────────────────────────────────── */

	public static function sanitize_bot( $item ) {
		return array(
			'id'       => sanitize_key( $item['id'] ?? '' ),
			'name'     => sanitize_text_field( $item['name'] ?? '' ),
			'token'    => preg_replace( '/[^0-9A-Za-z:_\-]/', '', (string) ( $item['token'] ?? '' ) ),
			'username' => sanitize_text_field( $item['username'] ?? '' ),
		);
	}

	public static function sanitize_chat( $item ) {
		return array(
			'id'        => sanitize_key( $item['id'] ?? '' ),
			'name'      => sanitize_text_field( $item['name'] ?? '' ),
			'chat_id'   => preg_replace( '/[^0-9@A-Za-z_\-]/', '', (string) ( $item['chat_id'] ?? '' ) ),
			'thread_id' => preg_replace( '/[^0-9]/', '', (string) ( $item['thread_id'] ?? '' ) ),
		);
	}

	public static function sanitize_template( $item ) {
		$parse_mode = in_array( $item['parse_mode'] ?? 'none', array( 'none', 'HTML' ), true )
			? ( $item['parse_mode'] ?? 'none' )
			: 'none';

		return array(
			'id'         => sanitize_key( $item['id'] ?? '' ),
			'name'       => sanitize_text_field( $item['name'] ?? '' ),
			'form_id'    => absint( $item['form_id'] ?? 0 ),
			'parse_mode' => $parse_mode,
			'body'       => wp_kses( (string) ( $item['body'] ?? '' ), Template_Engine::allowed_html() ),
		);
	}

	public static function sanitize_rule( $item ) {
		$chat_ids = array();
		if ( ! empty( $item['chat_ids'] ) && is_array( $item['chat_ids'] ) ) {
			$chat_ids = array_values( array_filter( array_map( 'sanitize_key', $item['chat_ids'] ) ) );
		}

		$clean = array(
			'id'          => sanitize_key( $item['id'] ?? '' ),
			'name'        => sanitize_text_field( $item['name'] ?? '' ),
			'form_id'     => absint( $item['form_id'] ?? 0 ),
			'bot_id'      => sanitize_key( $item['bot_id'] ?? '' ),
			'chat_ids'    => $chat_ids,
			'template_id' => sanitize_key( $item['template_id'] ?? '' ),
			'enabled'     => ! empty( $item['enabled'] ),
		);

		/**
		 * Filter the sanitized rule before it is persisted.
		 * Pro uses this to sanitize and merge its own keys (conditions, attachments)
		 * from the raw input item.
		 *
		 * @param array $clean Sanitized rule.
		 * @param array $item  Raw input item.
		 */
		return apply_filters( 'b418_wp_tg_cf7_sanitize_rule', $clean, $item );
	}
}
