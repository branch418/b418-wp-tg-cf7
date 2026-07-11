<?php
/**
 * Intercepts Contact Form 7 submissions and relays them to Telegram
 * according to the configured routing rules.
 *
 * @package Branch418\WpTgCf7
 */

namespace Branch418\WpTgCf7;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Form_Handler {

	public static function init() {
		add_action( 'wpcf7_before_send_mail', array( self::class, 'handle_submission' ), 10, 3 );
	}

	/**
	 * Runs on every valid CF7 submission, before mail is sent.
	 * Never aborts or alters the CF7 flow — delivery problems are only logged.
	 *
	 * @param \WPCF7_ContactForm $contact_form Current form.
	 * @param bool               $abort        Whether mail sending is aborted (unused).
	 * @param \WPCF7_Submission  $submission   Submission instance.
	 */
	public static function handle_submission( $contact_form, $abort = false, $submission = null ) {
		if ( ! $submission && class_exists( '\WPCF7_Submission' ) ) {
			$submission = \WPCF7_Submission::get_instance();
		}

		if ( ! $submission ) {
			return;
		}

		$form_id = $contact_form->id();

		foreach ( Settings::get_items( 'rule' ) as $rule ) {
			if ( empty( $rule['enabled'] ) ) {
				continue;
			}
			if ( ! empty( $rule['form_id'] ) && (int) $rule['form_id'] !== (int) $form_id ) {
				continue;
			}

			/**
			 * Last-chance veto before a rule fires.
			 * Pro uses this for conditional routing.
			 *
			 * @param bool               $send         Whether to process this rule.
			 * @param array              $rule         Routing rule.
			 * @param \WPCF7_ContactForm $contact_form Current form.
			 * @param \WPCF7_Submission  $submission   Submission.
			 */
			if ( ! apply_filters( 'b418_wp_tg_cf7_should_send', true, $rule, $contact_form, $submission ) ) {
				continue;
			}

			self::process_rule( $rule, $contact_form, $submission );
		}
	}

	/**
	 * Resolve bot, template and chats for a rule and dispatch messages.
	 *
	 * @param array              $rule         Routing rule.
	 * @param \WPCF7_ContactForm $contact_form Current form.
	 * @param \WPCF7_Submission  $submission   Submission.
	 */
	private static function process_rule( $rule, $contact_form, $submission ) {
		$bot = Settings::find( 'bot', $rule['bot_id'] );

		if ( ! $bot || empty( $bot['token'] ) ) {
			Logger::log(
				array(
					'form_title' => $contact_form->title(),
					'chat_name'  => '—',
					'ok'         => false,
					'error'      => __( 'The bot assigned to this connection no longer exists.', 'b418-tg-cf7' ),
				)
			);

			return;
		}

		$template   = ! empty( $rule['template_id'] ) ? Settings::find( 'template', $rule['template_id'] ) : null;
		$body       = $template && '' !== trim( $template['body'] ) ? $template['body'] : Template_Engine::default_template();
		$parse_mode = $template ? $template['parse_mode'] : 'none';

		$text = Template_Engine::render( $body, $contact_form, $submission, $parse_mode );

		/**
		 * Filter the final message text before sending.
		 *
		 * @param string             $text         Rendered message.
		 * @param array              $rule         Routing rule.
		 * @param \WPCF7_ContactForm $contact_form Current form.
		 * @param \WPCF7_Submission  $submission   Submission.
		 */
		$text = apply_filters( 'b418_wp_tg_cf7_message_text', $text, $rule, $contact_form, $submission );

		$context = array(
			'rule'       => $rule,
			'form_id'    => $contact_form->id(),
			'form_title' => $contact_form->title(),
		);

		foreach ( (array) $rule['chat_ids'] as $chat_id ) {
			$chat = Settings::find( 'chat', $chat_id );

			if ( ! $chat ) {
				continue;
			}

			$result = Telegram::send_message( $bot['token'], $chat, $text, $parse_mode, $context );

			Logger::log(
				array(
					'form_title' => $contact_form->title(),
					'chat_name'  => $chat['name'],
					'ok'         => ! empty( $result['ok'] ),
					'error'      => empty( $result['ok'] ) ? $result['description'] : '',
				)
			);

			/**
			 * Fires after each Telegram delivery attempt.
			 * Pro hooks here to record delivery history, schedule retries and
			 * send file attachments as follow-up messages.
			 *
			 * @param array             $result     Telegram API response.
			 * @param array             $rule       Routing rule.
			 * @param array             $chat       Chat item.
			 * @param array             $bot        Bot item.
			 * @param \WPCF7_Submission $submission Submission.
			 * @param array             $context    Extra context: rendered text, parse
			 *                                      mode, and source form info — enough
			 *                                      for Pro to record/resend the delivery
			 *                                      without holding a submission reference.
			 */
			do_action(
				'b418_wp_tg_cf7_after_send',
				$result,
				$rule,
				$chat,
				$bot,
				$submission,
				array(
					'text'       => $text,
					'parse_mode' => $parse_mode,
					'form_id'    => $contact_form->id(),
					'form_title' => $contact_form->title(),
				)
			);
		}
	}
}
