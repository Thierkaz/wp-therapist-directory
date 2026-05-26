<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TD_Deactivator {

    /**
     * Désactivation du plugin.
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
}
