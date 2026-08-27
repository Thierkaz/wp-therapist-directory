<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TD_Activator {

    /**
     * Activation du plugin : création de la table des adresses et flush des rewrite rules.
     */
    public static function activate() {
        self::create_tables();

        // Enregistrer CPT et taxonomie avant le flush
        TD_Post_Type::register();
        TD_Taxonomy::register();
        flush_rewrite_rules();

        // Tables des statistiques
        TD_Stats::create_table();
        TD_Clicks::create_table();

        update_option( 'td_version', TD_VERSION );
    }

    /**
     * Création de la table wp_td_addresses.
     */
    private static function create_tables() {
        global $wpdb;

        $table_name      = $wpdb->prefix . 'td_addresses';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            therapeute_id BIGINT(20) UNSIGNED NOT NULL,
            etablissement VARCHAR(255) DEFAULT '',
            adresse VARCHAR(255) DEFAULT '',
            code_postal VARCHAR(20) DEFAULT '',
            ville VARCHAR(100) DEFAULT '',
            pays VARCHAR(100) DEFAULT 'France',
            latitude DECIMAL(10,8) DEFAULT NULL,
            longitude DECIMAL(11,8) DEFAULT NULL,
            is_primary TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY therapeute_id (therapeute_id),
            KEY latitude_longitude (latitude, longitude)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
}
