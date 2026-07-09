<?php
/**
 * Removes all plugin data when the plugin is deleted.
 *
 * @package Branch418\WpTgCf7
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$b418_wp_tg_cf7_options = array(
	'b418_wp_tg_cf7_bots',
	'b418_wp_tg_cf7_chats',
	'b418_wp_tg_cf7_templates',
	'b418_wp_tg_cf7_rules',
	'b418_wp_tg_cf7_log',
);

foreach ( $b418_wp_tg_cf7_options as $b418_wp_tg_cf7_option ) {
	delete_option( $b418_wp_tg_cf7_option );
}
