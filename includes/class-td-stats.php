<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TD_Stats {

    /**
     * Nom de la table (avec préfixe).
     */
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'td_stats';
    }

    /**
     * Création de la table des statistiques.
     */
    public static function create_table() {
        global $wpdb;

        $table_name      = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            therapeute_id BIGINT(20) UNSIGNED NOT NULL,
            view_date DATE NOT NULL,
            views INT UNSIGNED NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY therapeute_date (therapeute_id, view_date),
            KEY therapeute_id (therapeute_id),
            KEY view_date (view_date)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Enregistrer une vue pour un thérapeute (une ligne par jour).
     */
    public static function record_view( $post_id ) {
        // Ne pas compter les admins connectés
        if ( current_user_can( 'edit_posts' ) ) {
            return;
        }

        // Ne pas compter les bots
        if ( self::is_bot() ) {
            return;
        }

        global $wpdb;
        $table = self::table_name();
        $today = current_time( 'Y-m-d' );

        // Upsert : incrémenter si la ligne existe, sinon insérer
        $wpdb->query( $wpdb->prepare(
            "INSERT INTO $table (therapeute_id, view_date, views)
             VALUES (%d, %s, 1)
             ON DUPLICATE KEY UPDATE views = views + 1",
            absint( $post_id ),
            $today
        ) );
    }

    /**
     * Récupérer le total des vues pour un thérapeute.
     */
    public static function get_total_views( $post_id ) {
        global $wpdb;
        $table = self::table_name();

        $total = $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(views), 0) FROM $table WHERE therapeute_id = %d",
            absint( $post_id )
        ) );

        return intval( $total );
    }

    /**
     * Détection basique des bots via User-Agent.
     */
    private static function is_bot() {
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if ( empty( $ua ) ) {
            return true;
        }

        $bots = [
            'bot', 'crawl', 'spider', 'slurp', 'mediapartners',
            'facebookexternalhit', 'linkedinbot', 'twitterbot',
            'whatsapp', 'pingdom', 'pagespeed', 'lighthouse',
        ];

        $ua_lower = strtolower( $ua );
        foreach ( $bots as $bot ) {
            if ( strpos( $ua_lower, $bot ) !== false ) {
                return true;
            }
        }

        return false;
    }
}
