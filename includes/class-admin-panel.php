<?php
/**
 * Registers the admin page and enqueues the Vue admin app.
 *
 * @package Branch418\WpTgCf7
 */

namespace Branch418\WpTgCf7\Admin;

use Branch418\WpTgCf7\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Panel {

	const MENU_SLUG = 'b418-wp-tg-cf7';

	public static function init() {
		add_action( 'admin_menu', array( self::class, 'register_admin_menu' ), 25 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_scripts' ) );
		add_action( 'admin_notices', array( self::class, 'maybe_show_cf7_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( B418_WP_TG_CF7_FILE ), array( self::class, 'add_settings_link' ) );
	}

	public static function register_admin_menu() {
		add_menu_page(
			__( 'Telegram for Contact Form 7', 'b418-telegram-for-contact-form-7' ),
			__( 'CF7 Telegram', 'b418-telegram-for-contact-form-7' ),
			'manage_options',
			self::MENU_SLUG,
			array( self::class, 'display_page' ),
			self::menu_icon(),
			90
		);
	}

	/**
	 * Paper-plane menu icon as a data URI (dashicons has no Telegram glyph).
	 *
	 * @return string
	 */
	private static function menu_icon() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="#a7aaad" d="M21.9 3.2 2.6 10.7c-1 .4-1 1.8.1 2.1l4.9 1.5 1.9 5.9c.3 1 1.6 1.2 2.2.4l2.7-3.3 5 3.7c.8.6 2 .2 2.2-.8l3-15.4c.2-1.1-.8-2-1.9-1.6zM9.4 13.9l9.1-5.7c.4-.2.8.3.5.6l-7.5 7-.3 3.2-1.8-5.1z"/></svg>';

		return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	public static function display_page() {
		?>
		<div id="b418-wp-tg-cf7-container"></div>
		<style>
			#wpbody-content .metabox-prefs ~ .error, #wpbody-content .metabox-prefs ~ .notice, #wpbody-content .metabox-prefs ~ .updated{
				display: none !important;
			}
		</style>
		<?php
	}

	public static function enqueue_scripts( $hook ) {
		if ( 'toplevel_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style( 'b418-wp-tg-cf7-admin-vue', B418_WP_TG_CF7_URI . 'dist/admin.css', array(), B418_WP_TG_CF7_VERSION );
		wp_enqueue_script( 'b418-wp-tg-cf7-admin', B418_WP_TG_CF7_URI . 'dist/admin.iife.js', array(), B418_WP_TG_CF7_VERSION, true );

		$localize_data = array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( Ajax::NONCE_ACTION ),
			'imagesUri' => B418_WP_TG_CF7_IMAGES_URI,
			'version'   => B418_WP_TG_CF7_VERSION,
		);

		/**
		 * Filter data exposed to the admin app.
		 * Pro injects its feature flags and license state here.
		 *
		 * @param array $localize_data Localized data.
		 */
		$localize_data = apply_filters( 'b418_wp_tg_cf7_localize_data', $localize_data );

		wp_localize_script( 'b418-wp-tg-cf7-admin', 'b418WpTgCf7Data', $localize_data );
	}

	/**
	 * Warn on our own admin page area when Contact Form 7 is missing.
	 */
	public static function maybe_show_cf7_notice() {
		if ( class_exists( '\WPCF7_ContactForm' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_' . self::MENU_SLUG !== $screen->id ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'Telegram for Contact Form 7 requires the Contact Form 7 plugin to be installed and activated.', 'b418-telegram-for-contact-form-7' )
		);
	}

	public static function add_settings_link( $links ) {
		$settings = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ),
			esc_html__( 'Settings', 'b418-telegram-for-contact-form-7' )
		);
		array_unshift( $links, $settings );

		return $links;
	}
}
