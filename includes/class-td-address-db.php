<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TD_Address_DB {

    /**
     * Nom de la table.
     */
    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'td_addresses';
    }

    /**
     * Récupérer toutes les adresses d'un thérapeute.
     */
    public static function get_by_therapeute( $therapeute_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE therapeute_id = %d ORDER BY is_primary DESC, id ASC",
            $therapeute_id
        ) );
    }

    /**
     * Récupérer une adresse par son ID.
     */
    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE id = %d",
            $id
        ) );
    }

    /**
     * Insérer une nouvelle adresse.
     */
    public static function insert( $data ) {
        global $wpdb;
        $result = $wpdb->insert( self::table(), $data, self::formats( $data ) );
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Mettre à jour une adresse.
     */
    public static function update( $id, $data ) {
        global $wpdb;
        return $wpdb->update(
            self::table(),
            $data,
            [ 'id' => $id ],
            self::formats( $data ),
            [ '%d' ]
        );
    }

    /**
     * Supprimer une adresse.
     */
    public static function delete( $id ) {
        global $wpdb;
        return $wpdb->delete( self::table(), [ 'id' => $id ], [ '%d' ] );
    }

    /**
     * Supprimer toutes les adresses d'un thérapeute.
     */
    public static function delete_by_therapeute( $therapeute_id ) {
        global $wpdb;
        return $wpdb->delete( self::table(), [ 'therapeute_id' => $therapeute_id ], [ '%d' ] );
    }

    /**
     * Recherche par proximité (formule Haversine).
     *
     * @param float $lat       Latitude du point de recherche.
     * @param float $lng       Longitude du point de recherche.
     * @param int   $radius    Rayon en kilomètres.
     * @param array $term_ids  IDs de termes de taxonomie (optionnel).
     * @param int   $limit     Nombre max de résultats.
     * @return array
     */
    public static function find_nearby( $lat, $lng, $radius = 50, $term_ids = [], $limit = 50 ) {
        global $wpdb;

        $table = self::table();
        $having_clause = $wpdb->prepare( "HAVING distance <= %f", $radius );
        $limit_clause  = $wpdb->prepare( "LIMIT %d", $limit );

        $join  = '';
        $where = "AND p.post_status = 'publish'";

        if ( ! empty( $term_ids ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );
            $join  = "INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id";
            $where .= $wpdb->prepare(
                " AND tr.term_taxonomy_id IN ($placeholders)",
                ...$term_ids
            );
        }

        $sql = $wpdb->prepare(
            "SELECT a.*, p.ID as post_id, p.post_title,
                ( 6371 * acos(
                    cos( radians(%f) ) * cos( radians(a.latitude) ) *
                    cos( radians(a.longitude) - radians(%f) ) +
                    sin( radians(%f) ) * sin( radians(a.latitude) )
                )) AS distance
            FROM $table a
            INNER JOIN {$wpdb->posts} p ON a.therapeute_id = p.ID
            $join
            WHERE a.latitude IS NOT NULL
              AND a.longitude IS NOT NULL
              $where
            GROUP BY a.id
            $having_clause
            ORDER BY distance ASC
            $limit_clause",
            $lat, $lng, $lat
        );

        return $wpdb->get_results( $sql );
    }

    /**
     * Formats pour wpdb en fonction des clés.
     */
    private static function formats( $data ) {
        $formats = [];
        foreach ( $data as $key => $value ) {
            switch ( $key ) {
                case 'therapeute_id':
                case 'is_primary':
                    $formats[] = '%d';
                    break;
                case 'latitude':
                case 'longitude':
                    $formats[] = $value === null ? '%s' : '%f';
                    break;
                default:
                    $formats[] = '%s';
            }
        }
        return $formats;
    }
}
