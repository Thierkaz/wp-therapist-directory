<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TD_Ordering {

    /**
     * Hook de la page admin (nécessaire pour conditionner l'enqueue).
     */
    private static $page_hook = '';

    /**
     * Initialisation.
     */
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_submenu_page' ] );
        add_action( 'wp_ajax_td_save_ordering', [ __CLASS__, 'save_ordering' ] );
    }

    /**
     * Ajout de la sous-page « Ordre d'affichage » dans le menu Annuaire.
     */
    public static function add_submenu_page() {
        self::$page_hook = add_submenu_page(
            'edit.php?post_type=therapeute',
            __( 'Ordre d\'affichage', 'therapist-directory' ),
            __( 'Ordre d\'affichage', 'therapist-directory' ),
            'edit_posts',
            'td-ordering',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Retourne le hook de la page (utilisé pour conditionner l'enqueue).
     */
    public static function get_page_hook() {
        return self::$page_hook;
    }

    /**
     * Rendu de la page d'ordonnancement.
     */
    public static function render_page() {
        // Récupérer les catégories de thérapeutes
        $categories = get_terms( [
            'taxonomy'   => 'therapeute_category',
            'hide_empty' => false,
            'parent'     => 0,
        ] );

        $current_cat = isset( $_GET['td_cat'] ) ? sanitize_text_field( $_GET['td_cat'] ) : '';

        // Construire la requête
        $args = [
            'post_type'      => 'therapeute',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        ];

        if ( $current_cat ) {
            $args['tax_query'] = [ [
                'taxonomy' => 'therapeute_category',
                'field'    => 'slug',
                'terms'    => $current_cat,
            ] ];
        }

        $therapeutes = new WP_Query( $args );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-sort" style="vertical-align: middle; margin-right: 6px;"></span>
                <?php esc_html_e( 'Ordre d\'affichage des thérapeutes', 'therapist-directory' ); ?>
            </h1>
            <p class="description">
                <?php esc_html_e( 'Glissez-déposez les thérapeutes pour définir leur ordre d\'affichage dans l\'annuaire.', 'therapist-directory' ); ?>
            </p>

            <!-- Filtre par catégorie -->
            <div class="td-ordering-filters">
                <form method="get">
                    <input type="hidden" name="post_type" value="therapeute">
                    <input type="hidden" name="page" value="td-ordering">
                    <label for="td-cat-filter">
                        <?php esc_html_e( 'Catégorie :', 'therapist-directory' ); ?>
                    </label>
                    <select name="td_cat" id="td-cat-filter" onchange="this.form.submit()">
                        <option value=""><?php esc_html_e( 'Toutes les catégories', 'therapist-directory' ); ?></option>
                        <?php foreach ( $categories as $cat ) : ?>
                            <option value="<?php echo esc_attr( $cat->slug ); ?>"
                                <?php selected( $current_cat, $cat->slug ); ?>>
                                <?php echo esc_html( $cat->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if ( $therapeutes->have_posts() ) : ?>
                <div class="td-ordering-status" id="td-ordering-status" style="display: none;">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e( 'Ordre sauvegardé !', 'therapist-directory' ); ?>
                </div>

                <ul class="td-ordering-list" id="td-ordering-list">
                    <?php $position = 1; ?>
                    <?php while ( $therapeutes->have_posts() ) : $therapeutes->the_post(); ?>
                        <?php
                        $post_id  = get_the_ID();
                        $nom      = get_post_meta( $post_id, '_td_nom', true );
                        $prenom   = get_post_meta( $post_id, '_td_prenom', true );
                        $titre    = get_post_meta( $post_id, '_td_titre', true );
                        $photo_id = get_post_meta( $post_id, '_td_photo', true );
                        $terms    = get_the_terms( $post_id, 'therapeute_category' );
                        $cats     = $terms && ! is_wp_error( $terms )
                                    ? implode( ', ', wp_list_pluck( $terms, 'name' ) )
                                    : '';
                        ?>
                        <li class="td-ordering-item" data-post-id="<?php echo esc_attr( $post_id ); ?>">
                            <span class="td-ordering-handle">
                                <span class="dashicons dashicons-menu"></span>
                            </span>
                            <span class="td-ordering-position"><?php echo esc_html( $position ); ?></span>
                            <span class="td-ordering-avatar">
                                <?php if ( $photo_id ) : ?>
                                    <?php echo wp_get_attachment_image( $photo_id, 'thumbnail' ); ?>
                                <?php else : ?>
                                    <span class="td-ordering-initials">
                                        <?php echo esc_html( mb_substr( $prenom, 0, 1 ) . mb_substr( $nom, 0, 1 ) ); ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                            <span class="td-ordering-info">
                                <strong><?php echo esc_html( "$prenom $nom" ); ?></strong>
                                <?php if ( $titre ) : ?>
                                    <span class="td-ordering-titre"><?php echo esc_html( $titre ); ?></span>
                                <?php endif; ?>
                            </span>
                            <?php if ( $cats ) : ?>
                                <span class="td-ordering-cats"><?php echo esc_html( $cats ); ?></span>
                            <?php endif; ?>
                        </li>
                        <?php $position++; ?>
                    <?php endwhile; ?>
                </ul>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <p><?php esc_html_e( 'Aucun thérapeute publié trouvé.', 'therapist-directory' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX : sauvegarder l'ordre des thérapeutes.
     */
    public static function save_ordering() {
        check_ajax_referer( 'td_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'Permission refusée.' ] );
        }

        $order = $_POST['order'] ?? [];

        if ( ! is_array( $order ) ) {
            wp_send_json_error( [ 'message' => 'Données invalides.' ] );
        }

        foreach ( $order as $position => $post_id ) {
            wp_update_post( [
                'ID'         => absint( $post_id ),
                'menu_order' => intval( $position ),
            ] );
        }

        wp_send_json_success( [ 'message' => 'Ordre sauvegardé.' ] );
    }
}
