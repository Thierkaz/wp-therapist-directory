<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TD_Ajax {

    public function __construct() {
        // Admin : géocodage d'une adresse
        add_action( 'wp_ajax_td_geocode', [ $this, 'geocode' ] );

        // Public : recherche par proximité
        add_action( 'wp_ajax_td_search_nearby', [ $this, 'search_nearby' ] );
        add_action( 'wp_ajax_nopriv_td_search_nearby', [ $this, 'search_nearby' ] );
    }

    /**
     * AJAX : géocoder une adresse (admin).
     */
    public function geocode() {
        check_ajax_referer( 'td_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'Permission refusée.' ] );
        }

        $adresse     = sanitize_text_field( $_POST['adresse'] ?? '' );
        $code_postal = sanitize_text_field( $_POST['code_postal'] ?? '' );
        $ville       = sanitize_text_field( $_POST['ville'] ?? '' );
        $pays        = sanitize_text_field( $_POST['pays'] ?? 'France' );

        $coords = TD_Geocoder::geocode( $adresse, $code_postal, $ville, $pays );

        if ( $coords ) {
            wp_send_json_success( $coords );
        } else {
            wp_send_json_error( [ 'message' => 'Impossible de géolocaliser cette adresse.' ] );
        }
    }

    /**
     * AJAX : recherche de thérapeutes par proximité (public).
     */
    public function search_nearby() {
        check_ajax_referer( 'td_public_nonce', 'nonce' );

        $code_postal = sanitize_text_field( $_POST['code_postal'] ?? '' );
        $radius      = intval( $_POST['radius'] ?? 50 );
        $category    = sanitize_text_field( $_POST['category'] ?? '' );

        if ( empty( $code_postal ) ) {
            wp_send_json_error( [ 'message' => 'Code postal requis.' ] );
        }

        // Géocoder le code postal
        $center = TD_Geocoder::geocode_postal( $code_postal );
        if ( ! $center ) {
            wp_send_json_error( [ 'message' => 'Code postal introuvable.' ] );
        }

        // Récupérer les term_ids si catégorie spécifiée
        $term_ids = [];
        if ( $category ) {
            $term = get_term_by( 'slug', $category, 'therapeute_category' );
            if ( $term ) {
                // Inclure les sous-catégories
                $term_ids   = [ $term->term_taxonomy_id ];
                $children   = get_term_children( $term->term_id, 'therapeute_category' );
                if ( ! is_wp_error( $children ) ) {
                    foreach ( $children as $child_id ) {
                        $child_term = get_term( $child_id, 'therapeute_category' );
                        if ( $child_term ) {
                            $term_ids[] = $child_term->term_taxonomy_id;
                        }
                    }
                }
            }
        }

        $results = TD_Address_DB::find_nearby( $center['lat'], $center['lng'], $radius, $term_ids );

        // Enrichir les résultats
        $output = [];
        $seen_therapeutes = [];

        foreach ( $results as $row ) {
            if ( in_array( $row->therapeute_id, $seen_therapeutes ) ) {
                continue; // Un résultat par thérapeute
            }
            $seen_therapeutes[] = $row->therapeute_id;

            $photo_id = get_post_meta( $row->therapeute_id, '_td_photo', true );

            // Collecter toutes les villes du thérapeute
            $all_addresses = TD_Address_DB::get_by_therapeute( $row->therapeute_id );
            $villes = array_values( array_unique( array_filter( array_map( function( $a ) {
                return $a->ville;
            }, $all_addresses ) ) ) );

            $output[] = [
                'id'             => $row->therapeute_id,
                'nom'            => get_post_meta( $row->therapeute_id, '_td_nom', true ),
                'prenom'         => get_post_meta( $row->therapeute_id, '_td_prenom', true ),
                'titre'          => get_post_meta( $row->therapeute_id, '_td_titre', true ),
                'telephone'      => get_post_meta( $row->therapeute_id, '_td_telephone_pro_1', true )
                                    ?: get_post_meta( $row->therapeute_id, '_td_telephone_principal', true ),
                'email'          => get_post_meta( $row->therapeute_id, '_td_email', true ),
                'photo'          => $photo_id ? wp_get_attachment_image_url( $photo_id, 'thumbnail' ) : '',
                'villes'         => $villes,
                'visio'          => get_post_meta( $row->therapeute_id, '_td_visio', true ) === '1',
                'site_internet'  => get_post_meta( $row->therapeute_id, '_td_site_internet', true ),
                'distance'       => round( $row->distance, 1 ),
                'latitude'       => $row->latitude,
                'longitude'      => $row->longitude,
                'permalink'      => get_permalink( $row->therapeute_id ),
            ];
        }

        wp_send_json_success( [
            'center'  => $center,
            'results' => $output,
        ] );
    }
}
