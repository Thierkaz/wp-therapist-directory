<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TD_Post_Type {

    /**
     * Enregistrement du Custom Post Type "therapeute".
     */
    public static function register() {
        $labels = [
            'name'               => __( 'Thérapeutes', 'therapist-directory' ),
            'singular_name'      => __( 'Thérapeute', 'therapist-directory' ),
            'menu_name'          => __( 'Annuaire', 'therapist-directory' ),
            'add_new'            => __( 'Ajouter', 'therapist-directory' ),
            'add_new_item'       => __( 'Ajouter un thérapeute', 'therapist-directory' ),
            'edit_item'          => __( 'Modifier le thérapeute', 'therapist-directory' ),
            'new_item'           => __( 'Nouveau thérapeute', 'therapist-directory' ),
            'view_item'          => __( 'Voir le thérapeute', 'therapist-directory' ),
            'search_items'       => __( 'Rechercher un thérapeute', 'therapist-directory' ),
            'not_found'          => __( 'Aucun thérapeute trouvé.', 'therapist-directory' ),
            'not_found_in_trash' => __( 'Aucun thérapeute dans la corbeille.', 'therapist-directory' ),
            'all_items'          => __( 'Tous les thérapeutes', 'therapist-directory' ),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_position'      => 25,
            'menu_icon'          => 'dashicons-heart',
            'supports'           => [ 'title', 'thumbnail' ],
            'has_archive'        => false,
            'rewrite'            => false, // Géré par une rewrite rule custom
            'show_in_rest'       => false,
            'capability_type'    => 'post',
        ];

        register_post_type( 'therapeute', $args );

        // Rewrite rule custom : 2 segments après le préfixe = fiche thérapeute
        // Les pages enfants (1 segment) ne sont PAS matchées par cette règle
        add_rewrite_rule(
            '^trouble-borderline-annuaire-des-therapeutes/([^/]+)/([^/]+)/?$',
            'index.php?therapeute=$matches[2]',
            'top'
        );

        // Construire le permalien manuellement
        add_filter( 'post_type_link', [ __CLASS__, 'rewrite_permalink' ], 10, 2 );

        // Colonnes admin personnalisées
        add_filter( 'manage_therapeute_posts_columns', [ __CLASS__, 'custom_columns' ] );
        add_action( 'manage_therapeute_posts_custom_column', [ __CLASS__, 'column_content' ], 10, 2 );
        add_filter( 'manage_edit-therapeute_sortable_columns', [ __CLASS__, 'sortable_columns' ] );
    }

    /**
     * Définition des colonnes personnalisées.
     */
    public static function custom_columns( $columns ) {
        $new = [];
        $new['cb']        = $columns['cb'];
        $new['title']     = __( 'Nom complet', 'therapist-directory' );
        $new['td_titre']  = __( 'Titre', 'therapist-directory' );
        $new['td_email']  = __( 'Email', 'therapist-directory' );
        $new['td_phone']  = __( 'Téléphone', 'therapist-directory' );
        $new['td_ville']  = __( 'Ville(s)', 'therapist-directory' );
        $new['taxonomy-therapeute_category'] = __( 'Catégories', 'therapist-directory' );
        $new['td_order']  = __( 'Ordre', 'therapist-directory' );
        $new['date']      = $columns['date'];

        return $new;
    }

    /**
     * Contenu des colonnes personnalisées.
     */
    public static function column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'td_titre':
                echo esc_html( get_post_meta( $post_id, '_td_titre', true ) );
                break;

            case 'td_email':
                $email = get_post_meta( $post_id, '_td_email', true );
                if ( $email ) {
                    echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
                }
                break;

            case 'td_phone':
                echo esc_html( get_post_meta( $post_id, '_td_telephone_principal', true ) );
                break;

            case 'td_order':
                echo esc_html( get_post_field( 'menu_order', $post_id ) );
                break;

            case 'td_ville':
                global $wpdb;
                $table = $wpdb->prefix . 'td_addresses';
                $villes = $wpdb->get_col( $wpdb->prepare(
                    "SELECT ville FROM $table WHERE therapeute_id = %d AND ville != '' ORDER BY is_primary DESC",
                    $post_id
                ) );
                echo esc_html( implode( ', ', $villes ) );
                break;
        }
    }

    /**
     * Construire le permalien manuellement.
     * Format : /trouble-borderline-annuaire-des-therapeutes/{categorie}/{slug}/
     */
    public static function rewrite_permalink( $post_link, $post ) {
        if ( 'therapeute' !== $post->post_type ) {
            return $post_link;
        }

        $terms = get_the_terms( $post->ID, 'therapeute_category' );
        $cat_slug = 'non-classe';
        if ( $terms && ! is_wp_error( $terms ) ) {
            $cat_slug = $terms[0]->slug;
            // Préférer la catégorie enfant si elle existe
            foreach ( $terms as $term ) {
                if ( $term->parent > 0 ) {
                    $cat_slug = $term->slug;
                    break;
                }
            }
        }

        return home_url( user_trailingslashit(
            "trouble-borderline-annuaire-des-therapeutes/{$cat_slug}/{$post->post_name}"
        ) );
    }

    /**
     * Colonnes triables.
     */
    public static function sortable_columns( $columns ) {
        $columns['td_titre'] = 'td_titre';
        $columns['td_email'] = 'td_email';
        $columns['td_order'] = 'menu_order';
        return $columns;
    }
}
