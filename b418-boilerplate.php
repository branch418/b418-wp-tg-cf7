<?php
/**
 * Plugin Name: branch418 Boilerplate
 * Description: Boilerplate for branch418 plugins
 * Author: branch418
 * Author URI: https://branch418.dev
 * Version: 1.0.0
 * Plugin Slug: b418-boilerplate
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'B418_BOILERPLATE_VERSION', '1.0.1' );
define( 'B418_BOILERPLATE_PATH', plugin_dir_path( __FILE__ ) );
define( 'B418_BOILERPLATE_TEMPLATES_PATH', B418_BOILERPLATE_PATH . DIRECTORY_SEPARATOR . 'templates/' );

define( 'B418_BOILERPLATE_URI', plugin_dir_url( __FILE__ ) );
define( 'B418_BOILERPLATE_IMAGES_URI', plugin_dir_url( __FILE__ ) . DIRECTORY_SEPARATOR . 'assets/images/' );

require_once B418_BOILERPLATE_PATH . 'includes/class-ajax.php';
require_once B418_BOILERPLATE_PATH . 'includes/class-admin-panel.php';

use Branch418\Boilerplate\Admin\Admin_Panel;
use Branch418\Boilerplate\Ajax;

function b418_boilerplate_init()
{
    Admin_Panel::init();
    Ajax::init();
}
add_action( 'plugins_loaded', 'b418_boilerplate_init' );