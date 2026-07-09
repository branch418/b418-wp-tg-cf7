<?php
/**
 * Admin AJAX endpoints for the Vue panel.
 *
 * Every handler verifies the admin nonce and the `manage_options`
 * capability. Actions are registered as `wp_ajax_b418_wp_tg_cf7_{action}`
 * and map 1:1 to `handle_{action}` methods.
 *
 * @package Branch418\WpTgCf7
 */

namespace Branch418\WpTgCf7;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ajax {

	const NONCE_ACTION = 'b418_wp_tg_cf7_nonce_admin';

	public static function init() {
		$actions = array(
			'bootstrap',
			'save_bot',
			'delete_bot',
			'save_chat',
			'delete_chat',
			'test_chat',
			'detect_chats',
			'save_template',
			'delete_template',
			'save_rule',
			'delete_rule',
			'test_rule',
			'clear_log',
		);

		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_b418_wp_tg_cf7_' . $action, array( self::class, 'handle_' . $action ) );
		}
	}

	/* ── Request helpers ────────────────────────────────────────── */

	private static function verify_request() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You are not allowed to manage these settings.', 'b418-wp-tg-cf7' ) ),
				403
			);
		}
	}

	private static function text_param( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in verify_request().
		return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}

	/**
	 * Decode a JSON-encoded object parameter (the admin app sends nested
	 * structures as JSON strings). Values are sanitized by Settings on write.
	 *
	 * @param string $key POST key.
	 * @return array
	 */
	private static function json_param( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- verified in verify_request(); decoded values are sanitized downstream.
		$raw   = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		$value = json_decode( $raw, true );

		return is_array( $value ) ? $value : array();
	}

	/* ── Bootstrap ──────────────────────────────────────────────── */

	public static function handle_bootstrap() {
		self::verify_request();

		$data = array(
			'cf7Active'       => class_exists( '\WPCF7_ContactForm' ),
			'forms'           => self::get_forms(),
			'bots'            => Settings::get_items( 'bot' ),
			'chats'           => Settings::get_items( 'chat' ),
			'templates'       => Settings::get_items( 'template' ),
			'rules'           => Settings::get_items( 'rule' ),
			'log'             => Logger::get(),
			'defaultTemplate' => Template_Engine::default_template(),
			'specialTags'     => Template_Engine::special_tags(),
		);

		/**
		 * Filter the admin bootstrap payload.
		 * Pro appends its own collections and feature flags here.
		 *
		 * @param array $data Bootstrap payload.
		 */
		wp_send_json_success( apply_filters( 'b418_wp_tg_cf7_bootstrap_data', $data ) );
	}

	private static function get_forms() {
		if ( ! class_exists( '\WPCF7_ContactForm' ) ) {
			return array();
		}

		$forms = array();

		foreach ( \WPCF7_ContactForm::find( array( 'posts_per_page' => -1 ) ) as $form ) {
			$tags = array();

			foreach ( $form->scan_form_tags() as $tag ) {
				if ( empty( $tag->name ) || 'submit' === $tag->basetype ) {
					continue;
				}
				$tags[] = array(
					'name' => $tag->name,
					'type' => $tag->basetype,
				);
			}

			$forms[] = array(
				'id'    => $form->id(),
				'title' => $form->title(),
				'tags'  => $tags,
			);
		}

		return $forms;
	}

	/* ── Bots ───────────────────────────────────────────────────── */

	public static function handle_save_bot() {
		self::verify_request();

		$bot = self::json_param( 'bot' );

		if ( empty( $bot['token'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a bot token.', 'b418-wp-tg-cf7' ) ), 400 );
		}

		$me = Telegram::get_me( trim( (string) $bot['token'] ) );

		if ( empty( $me['ok'] ) ) {
			wp_send_json_error(
				array(
					/* translators: %s: Telegram API error message. */
					'message' => sprintf( __( 'Telegram rejected this token: %s', 'b418-wp-tg-cf7' ), $me['description'] ),
				),
				400
			);
		}

		$bot['username'] = $me['result']['username'] ?? '';

		if ( empty( $bot['name'] ) ) {
			$bot['name'] = $me['result']['first_name'] ?? $bot['username'];
		}

		$saved = Settings::upsert( 'bot', $bot );

		if ( is_wp_error( $saved ) ) {
			wp_send_json_error( array( 'message' => $saved->get_error_message() ), 400 );
		}

		wp_send_json_success( array( 'bots' => Settings::get_items( 'bot' ) ) );
	}

	public static function handle_delete_bot() {
		self::verify_request();
		Settings::delete( 'bot', self::text_param( 'id' ) );
		wp_send_json_success( array( 'bots' => Settings::get_items( 'bot' ) ) );
	}

	/* ── Chats ──────────────────────────────────────────────────── */

	public static function handle_save_chat() {
		self::verify_request();

		$chat = self::json_param( 'chat' );

		if ( empty( $chat['chat_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a chat ID.', 'b418-wp-tg-cf7' ) ), 400 );
		}

		$saved = Settings::upsert( 'chat', $chat );

		if ( is_wp_error( $saved ) ) {
			wp_send_json_error( array( 'message' => $saved->get_error_message() ), 400 );
		}

		wp_send_json_success( array( 'chats' => Settings::get_items( 'chat' ) ) );
	}

	public static function handle_delete_chat() {
		self::verify_request();
		Settings::delete( 'chat', self::text_param( 'id' ) );
		wp_send_json_success( array( 'chats' => Settings::get_items( 'chat' ) ) );
	}

	public static function handle_test_chat() {
		self::verify_request();

		$bot  = Settings::find( 'bot', self::text_param( 'bot_id' ) );
		$chat = Settings::sanitize_chat( self::json_param( 'chat' ) );

		if ( ! $bot ) {
			wp_send_json_error( array( 'message' => __( 'Please choose a bot to send the test with.', 'b418-wp-tg-cf7' ) ), 400 );
		}

		if ( empty( $chat['chat_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a chat ID first.', 'b418-wp-tg-cf7' ) ), 400 );
		}

		$text = sprintf(
			/* translators: 1: site title, 2: bot name. */
			__( "✅ Test message from “%1\$s”.\nBot “%2\$s” can deliver to this chat.", 'b418-wp-tg-cf7' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			$bot['name']
		);

		$result = Telegram::send_message( $bot['token'], $chat, $text );

		if ( empty( $result['ok'] ) ) {
			wp_send_json_error( array( 'message' => $result['description'] ), 400 );
		}

		wp_send_json_success( array( 'message' => __( 'Test message delivered!', 'b418-wp-tg-cf7' ) ) );
	}

	public static function handle_detect_chats() {
		self::verify_request();

		$bot = Settings::find( 'bot', self::text_param( 'bot_id' ) );

		if ( ! $bot ) {
			wp_send_json_error( array( 'message' => __( 'Please choose a bot first.', 'b418-wp-tg-cf7' ) ), 400 );
		}

		$updates = Telegram::get_updates( $bot['token'] );

		if ( empty( $updates['ok'] ) ) {
			wp_send_json_error( array( 'message' => $updates['description'] ), 400 );
		}

		$found = array();

		foreach ( (array) $updates['result'] as $update ) {
			$message = $update['message'] ?? $update['channel_post'] ?? $update['my_chat_member'] ?? null;
			$chat    = $message['chat'] ?? null;

			if ( ! $chat || ! isset( $chat['id'] ) ) {
				continue;
			}

			$title = $chat['title']
				?? trim( ( $chat['first_name'] ?? '' ) . ' ' . ( $chat['last_name'] ?? '' ) );

			if ( '' === $title && ! empty( $chat['username'] ) ) {
				$title = '@' . $chat['username'];
			}

			$found[ (string) $chat['id'] ] = array(
				'chat_id' => (string) $chat['id'],
				'title'   => $title,
				'type'    => $chat['type'] ?? '',
			);
		}

		wp_send_json_success( array( 'found' => array_values( $found ) ) );
	}

	/* ── Templates ──────────────────────────────────────────────── */

	public static function handle_save_template() {
		self::verify_request();

		$template = self::json_param( 'template' );

		if ( empty( $template['name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please name the template.', 'b418-wp-tg-cf7' ) ), 400 );
		}

		$saved = Settings::upsert( 'template', $template );

		if ( is_wp_error( $saved ) ) {
			wp_send_json_error( array( 'message' => $saved->get_error_message() ), 400 );
		}

		wp_send_json_success( array( 'templates' => Settings::get_items( 'template' ) ) );
	}

	public static function handle_delete_template() {
		self::verify_request();
		Settings::delete( 'template', self::text_param( 'id' ) );
		wp_send_json_success( array( 'templates' => Settings::get_items( 'template' ) ) );
	}

	/* ── Rules (connections) ────────────────────────────────────── */

	public static function handle_save_rule() {
		self::verify_request();

		$rule = self::json_param( 'rule' );

		if ( empty( $rule['bot_id'] ) || empty( $rule['chat_ids'] ) ) {
			wp_send_json_error( array( 'message' => __( 'A connection needs a bot and at least one chat.', 'b418-wp-tg-cf7' ) ), 400 );
		}

		$saved = Settings::upsert( 'rule', $rule );

		if ( is_wp_error( $saved ) ) {
			wp_send_json_error( array( 'message' => $saved->get_error_message() ), 400 );
		}

		wp_send_json_success( array( 'rules' => Settings::get_items( 'rule' ) ) );
	}

	public static function handle_delete_rule() {
		self::verify_request();
		Settings::delete( 'rule', self::text_param( 'id' ) );
		wp_send_json_success( array( 'rules' => Settings::get_items( 'rule' ) ) );
	}

	public static function handle_test_rule() {
		self::verify_request();

		$rule = Settings::find( 'rule', self::text_param( 'id' ) );

		if ( ! $rule ) {
			wp_send_json_error( array( 'message' => __( 'Connection not found.', 'b418-wp-tg-cf7' ) ), 400 );
		}

		$bot = Settings::find( 'bot', $rule['bot_id'] );

		if ( ! $bot ) {
			wp_send_json_error( array( 'message' => __( 'The bot assigned to this connection no longer exists.', 'b418-wp-tg-cf7' ) ), 400 );
		}

		$form = null;
		if ( ! empty( $rule['form_id'] ) && class_exists( '\WPCF7_ContactForm' ) ) {
			$form = \WPCF7_ContactForm::get_instance( $rule['form_id'] );
		}

		$template   = ! empty( $rule['template_id'] ) ? Settings::find( 'template', $rule['template_id'] ) : null;
		$body       = $template && '' !== trim( $template['body'] ) ? $template['body'] : Template_Engine::default_template();
		$parse_mode = $template ? $template['parse_mode'] : 'none';

		$text = Template_Engine::render_sample( $body, $form, $parse_mode );

		$sent   = 0;
		$errors = array();

		foreach ( (array) $rule['chat_ids'] as $chat_id ) {
			$chat = Settings::find( 'chat', $chat_id );

			if ( ! $chat ) {
				continue;
			}

			$result = Telegram::send_message( $bot['token'], $chat, $text, $parse_mode );

			if ( ! empty( $result['ok'] ) ) {
				$sent++;
			} else {
				$errors[] = $chat['name'] . ': ' . $result['description'];
			}
		}

		if ( $errors ) {
			wp_send_json_error(
				array(
					/* translators: 1: number of delivered messages, 2: error details. */
					'message' => sprintf( __( 'Delivered %1$d message(s), but some failed — %2$s', 'b418-wp-tg-cf7' ), $sent, implode( '; ', $errors ) ),
				),
				400
			);
		}

		wp_send_json_success(
			array(
				/* translators: %d: number of chats the test was delivered to. */
				'message' => sprintf( __( 'Test delivered to %d chat(s).', 'b418-wp-tg-cf7' ), $sent ),
			)
		);
	}

	/* ── Log ────────────────────────────────────────────────────── */

	public static function handle_clear_log() {
		self::verify_request();
		Logger::clear();
		wp_send_json_success( array( 'log' => array() ) );
	}
}
