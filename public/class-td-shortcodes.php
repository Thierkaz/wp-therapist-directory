<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TD_Shortcodes {

    public static function init() {
        add_shortcode( 'therapist_directory', [ __CLASS__, 'directory' ] );
        add_shortcode( 'therapist_search', [ __CLASS__, 'search' ] );
        add_shortcode( 'therapist_map', [ __CLASS__, 'map' ] );
    }

    /**
     * Shortcode [therapist_directory category="slug" per_page="12"]
     * Affiche la liste des thérapeutes avec filtres.
     */
    public static function directory( $atts ) {
        $atts = shortcode_atts( [
            'category' => '',
            'per_page' => 12,
        ], $atts, 'therapist_directory' );

        // Pagination : préférer le paramètre GET ?paged=X (fiable sur les pages WP)
        $paged = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 0;
        if ( ! $paged ) {
            $paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : get_query_var( 'page' );
        }
        $paged = max( 1, intval( $paged ) );

        // Filtres
        $filter_ville = sanitize_text_field( $_GET['td_ville'] ?? '' );
        $filter_nom   = sanitize_text_field( $_GET['td_nom'] ?? '' );

        $args = [
            'post_type'      => 'therapeute',
            'post_status'    => 'publish',
            'posts_per_page' => intval( $atts['per_page'] ),
            'paged'          => $paged,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        ];

        if ( $atts['category'] ) {
            $args['tax_query'] = [ [
                'taxonomy' => 'therapeute_category',
                'field'    => 'slug',
                'terms'    => $atts['category'],
            ] ];
        }

        // Filtre par nom/prénom
        if ( $filter_nom ) {
            $args['meta_query'][] = [
                'relation' => 'OR',
                [
                    'key'     => '_td_nom',
                    'value'   => $filter_nom,
                    'compare' => 'LIKE',
                ],
                [
                    'key'     => '_td_prenom',
                    'value'   => $filter_nom,
                    'compare' => 'LIKE',
                ],
            ];
        }

        // Filtre par ville via la table des adresses
        if ( $filter_ville ) {
            global $wpdb;
            $table = $wpdb->prefix . 'td_addresses';
            $ville_filtered_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT DISTINCT therapeute_id FROM $table WHERE ville LIKE %s",
                '%' . $wpdb->esc_like( $filter_ville ) . '%'
            ) );
            $args['post__in'] = ! empty( $ville_filtered_ids ) ? $ville_filtered_ids : [ 0 ];
        }

        $query = new WP_Query( $args );

        ob_start();
        ?>
        <div class="td-directory">
            <!-- Filtres : action vers l'URL propre de la page (sans paramètres résiduels) -->
            <form class="td-filters" method="get" action="<?php echo esc_url( get_permalink() ); ?>">
                <div class="td-filters-row">
                    <input type="text" name="td_nom" placeholder="<?php esc_attr_e( 'Nom ou prénom…', 'therapist-directory' ); ?>"
                           value="<?php echo esc_attr( $filter_nom ); ?>">
                    <input type="text" name="td_ville" placeholder="<?php esc_attr_e( 'Ville…', 'therapist-directory' ); ?>"
                           value="<?php echo esc_attr( $filter_ville ); ?>">
                    <button type="submit" class="td-btn-primary">
                        <?php esc_html_e( 'Filtrer', 'therapist-directory' ); ?>
                    </button>
                    <?php if ( $filter_nom || $filter_ville ) : ?>
                        <a href="<?php echo esc_url( remove_query_arg( [ 'td_nom', 'td_ville' ] ) ); ?>" class="td-btn-secondary">
                            <?php esc_html_e( 'Réinitialiser', 'therapist-directory' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ( $query->have_posts() ) : ?>
                <div class="td-grid">
                    <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                        <?php self::render_card( get_the_ID() ); ?>
                    <?php endwhile; ?>
                </div>

                <?php if ( $query->max_num_pages > 1 ) : ?>
                    <div class="td-pagination">
                        <?php
                        // Utiliser le format ?paged=X pour que ça fonctionne sur les pages WordPress
                        $base_url = get_pagenum_link( 1, false );
                        $pagination_args = [
                            'base'    => add_query_arg( 'paged', '%#%', $base_url ),
                            'format'  => '',
                            'total'   => $query->max_num_pages,
                            'current' => $paged,
                        ];
                        // Conserver les paramètres de filtre dans les liens de pagination
                        $query_params = [];
                        if ( $filter_nom ) {
                            $query_params['td_nom'] = $filter_nom;
                        }
                        if ( $filter_ville ) {
                            $query_params['td_ville'] = $filter_ville;
                        }
                        if ( ! empty( $query_params ) ) {
                            $pagination_args['add_args'] = $query_params;
                        }
                        echo paginate_links( $pagination_args );
                        ?>
                    </div>
                <?php endif; ?>
            <?php else : ?>
                <p class="td-no-results"><?php esc_html_e( 'Aucun thérapeute trouvé.', 'therapist-directory' ); ?></p>
            <?php endif; ?>

            <?php wp_reset_postdata(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [therapist_search category="slug" radius="50"]
     * Formulaire de recherche par proximité avec résultats AJAX.
     */
    public static function search( $atts ) {
        $atts = shortcode_atts( [
            'category' => '',
            'radius'   => 50,
        ], $atts, 'therapist_search' );

        ob_start();
        ?>
        <div class="td-search" data-category="<?php echo esc_attr( $atts['category'] ); ?>">
            <form class="td-search-form" id="td-search-form">
                <div class="td-search-row">
                    <div class="td-search-field">
                        <label for="td-search-cp"><?php esc_html_e( 'Votre code postal', 'therapist-directory' ); ?></label>
                        <input type="text" id="td-search-cp" name="code_postal" placeholder="75001" required>
                    </div>
                    <div class="td-search-field">
                        <label for="td-search-radius"><?php esc_html_e( 'Rayon de recherche', 'therapist-directory' ); ?></label>
                        <select id="td-search-radius" name="radius">
                            <option value="10">10 km</option>
                            <option value="20">20 km</option>
                            <option value="30">30 km</option>
                            <option value="50" <?php selected( $atts['radius'], 50 ); ?>>50 km</option>
                            <option value="100">100 km</option>
                        </select>
                    </div>
                    <button type="submit" class="td-btn-primary">
                        <?php esc_html_e( 'Rechercher', 'therapist-directory' ); ?>
                    </button>
                </div>
            </form>

            <div id="td-search-results" class="td-grid" style="display:none;"></div>
            <div id="td-search-map" class="td-map-container" style="display:none;"></div>
            <p id="td-search-loading" style="display:none;">
                <?php esc_html_e( 'Recherche en cours…', 'therapist-directory' ); ?>
            </p>
            <p id="td-search-empty" style="display:none;">
                <?php esc_html_e( 'Aucun thérapeute trouvé dans ce périmètre.', 'therapist-directory' ); ?>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [therapist_map category="slug" height="500"]
     * Carte OpenStreetMap avec tous les thérapeutes.
     */
    public static function map( $atts ) {
        $atts = shortcode_atts( [
            'category' => '',
            'height'   => 500,
        ], $atts, 'therapist_map' );

        // Récupérer les thérapeutes avec adresses
        $args = [
            'post_type'      => 'therapeute',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ];

        if ( $atts['category'] ) {
            $args['tax_query'] = [ [
                'taxonomy' => 'therapeute_category',
                'field'    => 'slug',
                'terms'    => $atts['category'],
            ] ];
        }

        $query   = new WP_Query( $args );
        $markers = [];

        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id   = get_the_ID();
            $addresses = TD_Address_DB::get_by_therapeute( $post_id );

            foreach ( $addresses as $addr ) {
                if ( $addr->latitude && $addr->longitude ) {
                    $markers[] = [
                        'lat'   => floatval( $addr->latitude ),
                        'lng'   => floatval( $addr->longitude ),
                        'title' => get_the_title(),
                        'titre' => get_post_meta( $post_id, '_td_titre', true ),
                        'ville' => $addr->ville,
                        'link'  => get_permalink(),
                    ];
                }
            }
        }
        wp_reset_postdata();

        $map_id = 'td-map-' . uniqid();

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $map_id ); ?>" class="td-map-container"
             style="height: <?php echo intval( $atts['height'] ); ?>px;"
             data-markers="<?php echo esc_attr( wp_json_encode( $markers ) ); ?>">
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Rendu d'une carte thérapeute.
     */
    private static function render_card( $post_id ) {
        $nom            = get_post_meta( $post_id, '_td_nom', true );
        $prenom         = get_post_meta( $post_id, '_td_prenom', true );
        $titre          = get_post_meta( $post_id, '_td_titre', true );
        $telephone      = get_post_meta( $post_id, '_td_telephone_pro_1', true )
                          ?: get_post_meta( $post_id, '_td_telephone_pro_2', true );
        $site_internet  = get_post_meta( $post_id, '_td_site_internet', true );
        $photo_id       = get_post_meta( $post_id, '_td_photo', true );
        $addresses      = TD_Address_DB::get_by_therapeute( $post_id );

        // Collecter toutes les villes uniques
        $villes = array_unique( array_filter( array_map( function( $a ) {
            return $a->ville . ' - ' . $a->code_postal;
        }, $addresses ) ) );
        ?>
        <div class="td-card">
            <div class="td-card-photo">
                <?php if ( $photo_id ) : ?>
                    <?php echo wp_get_attachment_image( $photo_id, 'medium' ); ?>
                <?php else : ?>
                    <div class="td-card-avatar">
                        <?php echo esc_html( mb_substr( $prenom, 0, 1 ) . mb_substr( $nom, 0, 1 ) ); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="td-card-body">
                <h3 class="td-card-name">
                    <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                        <?php echo esc_html( "$prenom $nom" ); ?>
                    </a>
                </h3>
                <?php if ( $titre ) : ?>
                    <p class="td-card-titre"><?php echo esc_html( $titre ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $villes ) ) : ?>
                    <p class="td-card-location">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <?php echo esc_html( implode( ', ', $villes ) ); ?>
                    </p>
                <?php endif; ?>
                <?php if ( $telephone ) : ?>
                    <p class="td-card-phone">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/>
                        </svg>
                        <?php echo esc_html( $telephone ); ?>
                    </p>
                <?php endif; ?>
                <?php if ( get_post_meta( $post_id, '_td_visio', true ) === '1' ) : ?>
                    <p class="td-card-visio">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="23 7 16 12 23 17 23 7"/>
                            <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
                        </svg>
                        <?php esc_html_e( 'Visio', 'therapist-directory' ); ?>
                    </p>
                <?php endif; ?>
                <?php if ( $site_internet ) : ?>
                    <p class="td-card-website">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                            <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                        </svg>
                        <a href="<?php echo esc_url( $site_internet ); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', $site_internet ) ); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

TD_Shortcodes::init();
