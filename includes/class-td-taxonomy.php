<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TD_Taxonomy {

    /**
     * Enregistrement de la taxonomie hiérarchique.
     */
    public static function register() {
        $labels = [
            'name'              => __( 'Catégories de thérapeutes', 'therapist-directory' ),
            'singular_name'     => __( 'Catégorie', 'therapist-directory' ),
            'search_items'      => __( 'Rechercher une catégorie', 'therapist-directory' ),
            'all_items'         => __( 'Toutes les catégories', 'therapist-directory' ),
            'parent_item'       => __( 'Catégorie parente', 'therapist-directory' ),
            'parent_item_colon' => __( 'Catégorie parente :', 'therapist-directory' ),
            'edit_item'         => __( 'Modifier la catégorie', 'therapist-directory' ),
            'update_item'       => __( 'Mettre à jour la catégorie', 'therapist-directory' ),
            'add_new_item'      => __( 'Ajouter une catégorie', 'therapist-directory' ),
            'new_item_name'     => __( 'Nom de la nouvelle catégorie', 'therapist-directory' ),
            'menu_name'         => __( 'Catégories', 'therapist-directory' ),
        ];

        $args = [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'categorie-therapeute' ],
            'show_in_rest'      => false,
        ];

        register_taxonomy( 'therapeute_category', 'therapeute', $args );
    }
}
