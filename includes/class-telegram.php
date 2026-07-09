<?php
/**
 * Thin Telegram Bot API client built on the WordPress HTTP API.
 *
 * Every response is normalized to the Telegram envelope shape:
 * array( 'ok' => bool, 'result' => mixed, 'description' => string ).
 *
 * @package Branch418\WpTgCf7
 */

namespace Branch418\WpTgCf7;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Telegram {

	const API_BASE = 'https://api.telegram.org/bot';

	/**
	 * Call a Bot API method.
	 *
	 * @param string $token  Bot token.
	 * @param string $method API method name, e.g. `sendMessage`.
	 * @param array  $args   Request parameters.
	 * @return array Normalized Telegram response.
	 */
	public static function request( $token, $method, $args = array() ) {
		if ( empty( $token ) ) {
			return array(
				'ok'          => false,
				'description' => __( 'Bot token is empty.', 'b418-telegram-for-contact-form-7' ),
			);
		}

		$response = wp_remote_post(
			self::API_BASE . $token . '/' . $method,
			array(
				'timeout' => 15,
				'body'    => $args,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'ok'          => false,
				'description' => $response->get_error_message(),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || ! isset( $body['ok'] ) ) {
			return array(
				'ok'          => false,
				'description' => __( 'Unexpected response from the Telegram API.', 'b418-telegram-for-contact-form-7' ),
			);
		}

		if ( empty( $body['description'] ) ) {
			$body['description'] = '';
		}

		return $body;
	}

	/**
	 * Validate a token and fetch the bot profile.
	 *
	 * @param string $token Bot token.
	 * @return array
	 */
	public static function get_me( $token ) {
		return self::request( $token, 'getMe' );
	}

	/**
	 * Send a text message.
	 *
	 * @param string $token   Bot token.
	 * @param array  $chat    Chat item ({chat_id, thread_id}).
	 * @param string $text    Message text (max 4096 chars, truncated).
	 * @param string $parse_mode `none` or `HTML`.
	 * @param array  $context Extra context passed to the send args filter (rule, form…).
	 * @return array
	 */
	public static function send_message( $token, $chat, $text, $parse_mode = 'none', $context = array() ) {
		$args = array(
			'chat_id'                  => $chat['chat_id'],
			'text'                     => mb_substr( $text, 0, 4096 ),
			'disable_web_page_preview' => 'true',
		);

		if ( 'HTML' === $parse_mode ) {
			$args['parse_mode'] = 'HTML';
		}

		if ( ! empty( $chat['thread_id'] ) ) {
			$args['message_thread_id'] = $chat['thread_id'];
		}

		/**
		 * Filter the sendMessage payload before it is dispatched.
		 * Allows Pro to add reply markup, silent delivery, etc.
		 *
		 * @param array $args    sendMessage parameters.
		 * @param array $chat    Chat item.
		 * @param array $context Send context.
		 */
		$args = apply_filters( 'b418_wp_tg_cf7_send_args', $args, $chat, $context );

		return self::request( $token, 'sendMessage', $args );
	}

	/**
	 * Fetch recent updates — used by the admin "detect chats" helper.
	 *
	 * @param string $token Bot token.
	 * @return array
	 */
	public static function get_updates( $token ) {
		return self::request(
			$token,
			'getUpdates',
			array(
				'limit'           => 100,
				'allowed_updates' => wp_json_encode( array( 'message', 'channel_post', 'my_chat_member' ) ),
			)
		);
	}
}
