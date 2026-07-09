<?php

namespace Branch418\Boilerplate;

class Ajax {

    public static function init() {
        add_action( 'wp_ajax_b418_boilerplate_run', array( self::class, 'handle_run' ) );
    }

    public static function handle_run() {
        wp_send_json_success( array(
			'message' => __( 'Success!', 'b418-boilerplate' ),
        ) );
    }
}