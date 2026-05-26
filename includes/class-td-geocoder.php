<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TD_Geocoder {

    /**
     * URL de l'API Nominatim.
     */
    const API_URL = 'https://nominatim.openstreetmap.org/search';

    /**
     * Géocoder une adresse.
     *
     * @param string $adresse     Numéro et rue.
     * @param string $code_postal Code postal.
     * @param string $ville       Ville.
     * @param string $pays        Pays.
     * @return array|false        ['lat' => float, 'lng' => float] ou false.
     */
    public static function geocode( $adresse, $code_postal, $ville, $pays = 'France' ) {
        $query = implode( ', ', array_filter( [ $adresse, $code_postal, $ville, $pays ] ) );

        if ( empty( trim( $query ) ) ) {
            return false;
        }

        // Vérifier le cache
        $cache_key = 'td_geo_' . md5( $query );
        $cached    = get_transient( $cache_key );
        if ( $cached !== false ) {
            return $cached;
        }

        $url = add_query_arg( [
            'q'      => $query,
            'format' => 'json',
            'limit'  => 1,
        ], self::API_URL );

        $response = wp_remote_get( $url, [
            'timeout'    => 10,
            'user-agent' => 'TherapistDirectory/1.0 (WordPress Plugin)',
            'headers'    => [
                'Accept-Language' => 'fr',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body ) || ! isset( $body[0]['lat'], $body[0]['lon'] ) ) {
            return false;
        }

        $result = [
            'lat' => floatval( $body[0]['lat'] ),
            'lng' => floatval( $body[0]['lon'] ),
        ];

        // Cacher le résultat pendant 30 jours
        set_transient( $cache_key, $result, 30 * DAY_IN_SECONDS );

        return $result;
    }

    /**
     * Géocoder un code postal (pour la recherche par proximité côté frontend).
     *
     * @param string $code_postal
     * @param string $pays
     * @return array|false
     */
    public static function geocode_postal( $code_postal, $pays = 'France' ) {
        return self::geocode( '', $code_postal, '', $pays );
    }
}
