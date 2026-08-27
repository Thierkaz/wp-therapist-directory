<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TD_Clicks {

    /**
     * Types de clics autorisés.
     */
    const TYPES = [ 'phone', 'email', 'website' ];

    /**
     * Nom de la table (avec préfixe).
     */
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'td_clicks';
    }

    /**
     * Création de la table des clics.
     */
    public static function create_table() {
        global $wpdb;

        $table_name      = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            therapeute_id BIGINT(20) UNSIGNED NOT NULL,
            click_type VARCHAR(20) NOT NULL,
            click_date DATE NOT NULL,
            clicks INT UNSIGNED NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY therapeute_type_date (therapeute_id, click_type, click_date),
            KEY therapeute_id (therapeute_id),
            KEY click_date (click_date)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Enregistrement des hooks AJAX.
     */
    public static function init() {
        add_action( 'wp_ajax_td_record_click', [ __CLASS__, 'ajax_record_click' ] );
        add_action( 'wp_ajax_nopriv_td_record_click', [ __CLASS__, 'ajax_record_click' ] );
    }

    /**
     * AJAX : enregistrer un clic.
     */
    public static function ajax_record_click() {
        check_ajax_referer( 'td_public_nonce', 'nonce' );

        $post_id    = absint( $_POST['post_id'] ?? 0 );
        $click_type = sanitize_text_field( $_POST['click_type'] ?? '' );

        if ( ! $post_id || ! in_array( $click_type, self::TYPES, true ) ) {
            wp_send_json_error( [ 'message' => 'Paramètres invalides.' ] );
        }

        // Vérifier que le post existe et est bien un thérapeute
        if ( get_post_type( $post_id ) !== 'therapeute' ) {
            wp_send_json_error( [ 'message' => 'Post invalide.' ] );
        }

        self::record_click( $post_id, $click_type );

        wp_send_json_success();
    }

    /**
     * Enregistrer un clic (une ligne par thérapeute/type/jour).
     */
    public static function record_click( $post_id, $click_type ) {
        global $wpdb;
        $table = self::table_name();
        $today = current_time( 'Y-m-d' );

        $wpdb->query( $wpdb->prepare(
            "INSERT INTO $table (therapeute_id, click_type, click_date, clicks)
             VALUES (%d, %s, %s, 1)
             ON DUPLICATE KEY UPDATE clicks = clicks + 1",
            absint( $post_id ),
            $click_type,
            $today
        ) );
    }

    /**
     * Total des clics pour un thérapeute.
     *
     * @param int         $post_id    ID du thérapeute.
     * @param string|null $click_type Type de clic (null = tous les types).
     * @return int
     */
    public static function get_total_clicks( $post_id, $click_type = null ) {
        global $wpdb;
        $table = self::table_name();

        if ( $click_type ) {
            $total = $wpdb->get_var( $wpdb->prepare(
                "SELECT COALESCE(SUM(clicks), 0) FROM $table WHERE therapeute_id = %d AND click_type = %s",
                absint( $post_id ),
                $click_type
            ) );
        } else {
            $total = $wpdb->get_var( $wpdb->prepare(
                "SELECT COALESCE(SUM(clicks), 0) FROM $table WHERE therapeute_id = %d",
                absint( $post_id )
            ) );
        }

        return intval( $total );
    }

    /**
     * Totaux par type pour un thérapeute.
     *
     * @return array [ 'phone' => int, 'email' => int, 'website' => int ]
     */
    public static function get_clicks_by_type( $post_id ) {
        global $wpdb;
        $table = self::table_name();

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT click_type, COALESCE(SUM(clicks), 0) AS total
             FROM $table
             WHERE therapeute_id = %d
             GROUP BY click_type",
            absint( $post_id )
        ) );

        $result = [ 'phone' => 0, 'email' => 0, 'website' => 0 ];
        foreach ( $rows as $row ) {
            if ( isset( $result[ $row->click_type ] ) ) {
                $result[ $row->click_type ] = intval( $row->total );
            }
        }

        return $result;
    }
}
