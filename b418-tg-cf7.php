<?php
/**
 * Plugin Name: branch418 Telegram for Contact Form 7
 * Description: Sends Contact Form 7 submissions to Telegram. Connect multiple bots and chats, route any form to any chat, and customize the message per form with templates.
 * Author: branch418
 * Author URI: https://branch418.dev
 * Plugin URI: https://branch418.dev/plugins/wp-tg-cf7
 * Version: 1.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: contact-form-7
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: b418-tg-cf7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'B418_WP_TG_CF7_VERSION', '1.1.0' );
define( 'B418_WP_TG_CF7_FILE', __FILE__ );
define( 'B418_WP_TG_CF7_PATH', plugin_dir_path( __FILE__ ) );
define( 'B418_WP_TG_CF7_TEMPLATES_PATH', B418_WP_TG_CF7_PATH . 'templates/' );

define( 'B418_WP_TG_CF7_URI', plugin_dir_url( __FILE__ ) );
define( 'B418_WP_TG_CF7_IMAGES_URI', B418_WP_TG_CF7_URI . 'assets/images/' );

require_once B418_WP_TG_CF7_PATH . 'includes/class-settings.php';
require_once B418_WP_TG_CF7_PATH . 'includes/class-telegram.php';
require_once B418_WP_TG_CF7_PATH . 'includes/class-template-engine.php';
require_once B418_WP_TG_CF7_PATH . 'includes/class-logger.php';
require_once B418_WP_TG_CF7_PATH . 'includes/class-form-handler.php';
require_once B418_WP_TG_CF7_PATH . 'includes/class-ajax.php';
require_once B418_WP_TG_CF7_PATH . 'includes/class-admin-panel.php';

use Branch418\WpTgCf7\Admin\Admin_Panel;
use Branch418\WpTgCf7\Ajax;
use Branch418\WpTgCf7\Form_Handler;

function b418_wp_tg_cf7_init() {
	Admin_Panel::init();
	Ajax::init();
	Form_Handler::init();

	/**
	 * Fires once the free plugin is fully initialized.
	 * The Pro add-on boots from this hook so it always loads after core.
	 */
	do_action( 'b418_wp_tg_cf7_loaded' );
}
add_action( 'plugins_loaded', 'b418_wp_tg_cf7_init' );
