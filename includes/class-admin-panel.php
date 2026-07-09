<?php

namespace Branch418\Boilerplate\Admin;

use function add_meta_box, add_action, wp_enqueue_style;

class Admin_Panel
{

    public static function init() {
        add_action('admin_menu', [ self::class, 'register_admin_menu' ], 25);
        add_action('admin_enqueue_scripts', [ self::class, 'enqueue_scripts' ]);
    }

    public static function register_admin_menu() {
        add_menu_page(
            'B418 Boilerplate',
            'B418 Boilerplate',
            'manage_options',
            'b418-boilerplate',
            [Admin_Panel::class, 'display_page'],
            'dashicons-admin-plugins',
            90
        );
    }

    public static function display_page() {
        ?>
        <div id="b418-boilerplate-container"></div>
        <style>
            #wpbody-content .metabox-prefs ~ .error, #wpbody-content .metabox-prefs ~ .notice, #wpbody-content .metabox-prefs ~ .updated{
                display: none !important;
            }
        </style>
        <?php
    }

    public static function enqueue_scripts($hook) {
        if ( $hook !== 'toplevel_page_b418-boilerplate' ) {
            return;
        }
        wp_enqueue_style( 'b418-boilerplate-admin-vue', B418_BOILERPLATE_URI . 'dist/admin.css', array(), time() );
        wp_enqueue_script( 'b418-boilerplate-admin', B418_BOILERPLATE_URI . 'dist/admin.iife.js', array(), time(), true );


        $localize_data = [
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'b418_boilerplate_nonce_admin' ),
            'imagesUri' => B418_BOILERPLATE_IMAGES_URI
        ];

        wp_localize_script( 'b418-boilerplate-admin', 'b418BoilerplateData', $localize_data );
    }
}