<?php
/**
 * Renders message templates.
 *
 * Templates use CF7-style mail tags: `[field-name]` for form fields plus a
 * set of special tags (`[_site_title]`, `[_form_title]`, `[all_fields]`, …).
 * Values are HTML-escaped when the template is sent with `parse_mode=HTML`
 * so raw user input can never break Telegram markup.
 *
 * @package Branch418\WpTgCf7
 */

namespace Branch418\WpTgCf7;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Template_Engine {

	/**
	 * HTML tags Telegram accepts in messages — also the wp_kses whitelist
	 * used when saving templates.
	 *
	 * @return array
	 */
	public static function allowed_html() {
		return array(
			'b'          => array(),
			'strong'     => array(),
			'i'          => array(),
			'em'         => array(),
			'u'          => array(),
			'ins'        => array(),
			's'          => array(),
			'strike'     => array(),
			'del'        => array(),
			'a'          => array( 'href' => true ),
			'code'       => array(),
			'pre'        => array(),
			'blockquote' => array(),
			'tg-spoiler' => array(),
		);
	}

	/**
	 * Built-in fallback template used when a rule has no template assigned.
	 *
	 * @return string
	 */
	public static function default_template() {
		$body = __( "📩 New form submission\n\nForm: [_form_title]\nSite: [_site_title]\nDate: [_date] [_time]\n\n[all_fields]", 'b418-telegram-for-contact-form-7' );

		/**
		 * Filter the built-in default template body.
		 *
		 * @param string $body Template body.
		 */
		return apply_filters( 'b418_wp_tg_cf7_default_template', $body );
	}

	/**
	 * Special (non-field) tags available in every template.
	 *
	 * @return array Tag name => description (used by the admin tag palette).
	 */
	public static function special_tags() {
		return array(
			'all_fields'  => __( 'All submitted fields as a list', 'b418-telegram-for-contact-form-7' ),
			'_form_title' => __( 'Form title', 'b418-telegram-for-contact-form-7' ),
			'_site_title' => __( 'Site title', 'b418-telegram-for-contact-form-7' ),
			'_site_url'   => __( 'Site URL', 'b418-telegram-for-contact-form-7' ),
			'_page_url'   => __( 'Page the form was submitted from', 'b418-telegram-for-contact-form-7' ),
			'_date'       => __( 'Submission date', 'b418-telegram-for-contact-form-7' ),
			'_time'       => __( 'Submission time', 'b418-telegram-for-contact-form-7' ),
			'_user_ip'    => __( 'Visitor IP address', 'b418-telegram-for-contact-form-7' ),
			'_user_agent' => __( 'Visitor browser (user agent)', 'b418-telegram-for-contact-form-7' ),
		);
	}

	/**
	 * Render a template against a real CF7 submission.
	 *
	 * @param string             $body       Template body.
	 * @param \WPCF7_ContactForm $form       Contact form.
	 * @param \WPCF7_Submission  $submission Submission instance.
	 * @param string             $parse_mode `none` or `HTML`.
	 * @return string
	 */
	public static function render( $body, $form, $submission, $parse_mode = 'none' ) {
		$fields = array();

		foreach ( (array) $submission->get_posted_data() as $name => $value ) {
			$fields[ $name ] = self::flatten_value( $value );
		}

		$placeholders = array_merge( $fields, self::special_values( $form, $submission ) );

		/**
		 * Filter the placeholder map before rendering.
		 * Pro can inject additional computed placeholders here.
		 *
		 * @param array              $placeholders Tag => value.
		 * @param \WPCF7_ContactForm $form         Contact form.
		 * @param \WPCF7_Submission  $submission   Submission.
		 */
		$placeholders = apply_filters( 'b418_wp_tg_cf7_placeholders', $placeholders, $form, $submission );

		return self::replace( $body, $placeholders, $fields, $parse_mode );
	}

	/**
	 * Render a template with sample data (used by admin "send test").
	 *
	 * @param string                  $body       Template body.
	 * @param \WPCF7_ContactForm|null $form       Contact form, if resolvable.
	 * @param string                  $parse_mode `none` or `HTML`.
	 * @return string
	 */
	public static function render_sample( $body, $form, $parse_mode = 'none' ) {
		$fields = array();

		if ( $form && method_exists( $form, 'scan_form_tags' ) ) {
			foreach ( $form->scan_form_tags() as $tag ) {
				if ( empty( $tag->name ) ) {
					continue;
				}
				/* translators: %s: form field name. */
				$fields[ $tag->name ] = sprintf( __( '(sample value for “%s”)', 'b418-telegram-for-contact-form-7' ), $tag->name );
			}
		}

		$specials = array(
			'all_fields'  => self::format_all_fields( $fields, 'none' ),
			'_form_title' => $form ? $form->title() : __( 'Sample form', 'b418-telegram-for-contact-form-7' ),
			'_site_title' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'_site_url'   => home_url(),
			'_page_url'   => home_url(),
			'_date'       => wp_date( get_option( 'date_format' ) ),
			'_time'       => wp_date( get_option( 'time_format' ) ),
			'_user_ip'    => '127.0.0.1',
			'_user_agent' => __( '(test message)', 'b418-telegram-for-contact-form-7' ),
		);

		return self::replace( $body, array_merge( $fields, $specials ), $fields, $parse_mode );
	}

	/* ── Internals ──────────────────────────────────────────────── */

	private static function replace( $body, $placeholders, $fields, $parse_mode ) {
		// [all_fields] must respect the parse mode, so rebuild it here.
		$placeholders['all_fields'] = self::format_all_fields( $fields, $parse_mode );

		$search  = array();
		$replace = array();

		foreach ( $placeholders as $tag => $value ) {
			$search[]  = '[' . $tag . ']';
			$replace[] = ( 'HTML' === $parse_mode && 'all_fields' !== $tag )
				? esc_html( (string) $value )
				: (string) $value;
		}

		return str_replace( $search, $replace, $body );
	}

	private static function special_values( $form, $submission ) {
		return array(
			'_form_title' => $form->title(),
			'_site_title' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'_site_url'   => home_url(),
			'_page_url'   => (string) $submission->get_meta( 'url' ),
			'_date'       => wp_date( get_option( 'date_format' ), (int) $submission->get_meta( 'timestamp' ) ),
			'_time'       => wp_date( get_option( 'time_format' ), (int) $submission->get_meta( 'timestamp' ) ),
			'_user_ip'    => (string) $submission->get_meta( 'remote_ip' ),
			'_user_agent' => (string) $submission->get_meta( 'user_agent' ),
		);
	}

	private static function format_all_fields( $fields, $parse_mode ) {
		$lines = array();

		foreach ( $fields as $name => $value ) {
			if ( '' === trim( (string) $value ) ) {
				continue;
			}
			if ( 'HTML' === $parse_mode ) {
				$lines[] = '<b>' . esc_html( $name ) . ':</b> ' . esc_html( $value );
			} else {
				$lines[] = $name . ': ' . $value;
			}
		}

		return implode( "\n", $lines );
	}

	private static function flatten_value( $value ) {
		if ( is_array( $value ) ) {
			return implode( ', ', array_map( array( self::class, 'flatten_value' ), $value ) );
		}

		return (string) $value;
	}
}
