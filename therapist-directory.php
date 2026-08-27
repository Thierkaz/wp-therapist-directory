<?php
/**
 * Plugin Name: Annuaire de Thérapeutes
 * Plugin URI:
 * Description: Gestion d'un annuaire de thérapeutes avec recherche géolocalisée.
 * Version: 1.0.1
 * Author: Thierry François - https://fknet.fr
 * Author URI:
 * Text Domain: therapist-directory
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TD_VERSION', '1.0.1' );
define( 'TD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload des classes du plugin.
 */
spl_autoload_register( function ( $class ) {
    $prefix = 'TD_';
    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }

    $class_name = str_replace( $prefix, '', $class );
    $class_name = strtolower( str_replace( '_', '-', $class_name ) );
    $file       = TD_PLUGIN_DIR . 'includes/class-td-' . $class_name . '.php';

    if ( file_exists( $file ) ) {
        require_once $file;
    }
});

/**
 * Activation du plugin.
 */
function td_activate() {
    TD_Activator::activate();
}
register_activation_hook( __FILE__, 'td_activate' );

/**
 * Désactivation du plugin.
 */
function td_deactivate() {
    TD_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'td_deactivate' );

/**
 * Initialisation du plugin.
 */
function td_init() {
    // Chargement des traductions
    load_plugin_textdomain( 'therapist-directory', false, dirname( TD_PLUGIN_BASENAME ) . '/languages' );

    // Enregistrement CPT et taxonomie
    TD_Post_Type::register();
    TD_Taxonomy::register();

    // Hooks AJAX pour le tracking des clics
    TD_Clicks::init();
}
add_action( 'init', 'td_init' );

/**
 * Chargement admin.
 */
function td_admin_init() {
    new TD_Meta_Boxes();
    new TD_Ajax();

    // Création automatique des tables si absentes (mise à jour sans réactivation)
    global $wpdb;
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}td_stats'" ) !== $wpdb->prefix . 'td_stats' ) {
        TD_Stats::create_table();
    }
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}td_clicks'" ) !== $wpdb->prefix . 'td_clicks' ) {
        TD_Clicks::create_table();
    }
}
add_action( 'admin_init', 'td_admin_init' );

/**
 * Chargement de la page d'ordonnancement (doit être sur admin_menu).
 */
require_once TD_PLUGIN_DIR . 'includes/class-td-ordering.php';
TD_Ordering::init();

/**
 * Enqueue des assets admin.
 */
function td_admin_enqueue( $hook ) {
    global $post_type;

    $is_ordering_page = ( $hook === TD_Ordering::get_page_hook() );
    $is_therapeute    = ( 'therapeute' === $post_type );

    if ( ! $is_therapeute && ! $is_ordering_page ) {
        return;
    }

    if ( $is_therapeute ) {
        wp_enqueue_media();
    }

    wp_enqueue_style(
        'td-admin',
        TD_PLUGIN_URL . 'admin/css/td-admin.css',
        [],
        TD_VERSION
    );

    $js_deps = [ 'jquery' ];
    if ( $is_ordering_page ) {
        $js_deps[] = 'jquery-ui-sortable';
    }

    wp_enqueue_script(
        'td-admin',
        TD_PLUGIN_URL . 'admin/js/td-admin.js',
        $js_deps,
        TD_VERSION,
        true
    );

    wp_localize_script( 'td-admin', 'tdAdmin', [
        'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
        'nonce'          => wp_create_nonce( 'td_admin_nonce' ),
        'isOrderingPage' => $is_ordering_page,
        'i18n'           => [
            'confirmDelete'  => __( 'Supprimer cette adresse ?', 'therapist-directory' ),
            'geocodeError'   => __( 'Impossible de géolocaliser cette adresse.', 'therapist-directory' ),
            'geocodeSuccess' => __( 'Adresse géolocalisée avec succès.', 'therapist-directory' ),
            'requiredFields' => __( 'Veuillez remplir tous les champs obligatoires.', 'therapist-directory' ),
            'orderSaved'     => __( 'Ordre sauvegardé !', 'therapist-directory' ),
            'orderError'     => __( 'Erreur lors de la sauvegarde.', 'therapist-directory' ),
        ],
    ]);
}
add_action( 'admin_enqueue_scripts', 'td_admin_enqueue' );

/**
 * Enqueue des assets frontend.
 */
function td_public_enqueue() {
    wp_enqueue_style(
        'td-public',
        TD_PLUGIN_URL . 'public/css/td-public.css',
        [],
        TD_VERSION
    );

    // Leaflet CSS + JS
    wp_enqueue_style(
        'leaflet',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
        [],
        '1.9.4'
    );
    wp_enqueue_script(
        'leaflet',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        [],
        '1.9.4',
        true
    );

    wp_enqueue_script(
        'td-public',
        TD_PLUGIN_URL . 'public/js/td-public.js',
        [ 'jquery', 'leaflet' ],
        TD_VERSION,
        true
    );

    wp_localize_script( 'td-public', 'tdPublic', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'td_public_nonce' ),
    ]);
}
add_action( 'wp_enqueue_scripts', 'td_public_enqueue' );

/**
 * Template single pour le CPT therapeute.
 * Utilise template_include (plus fiable que single_template avec rewrite custom).
 */
function td_template_include( $template ) {
    if ( is_singular( 'therapeute' ) ) {
        // Enregistrer la vue
        TD_Stats::record_view( get_queried_object_id() );

        $plugin_template = TD_PLUGIN_DIR . 'public/templates/single-therapeute.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }
    return $template;
}
add_filter( 'template_include', 'td_template_include' );

/**
 * S'assurer que la rewrite rule custom résout bien le bon post_type.
 */
function td_parse_request( $wp ) {
    if ( ! empty( $wp->query_vars['therapeute'] ) && empty( $wp->query_vars['post_type'] ) ) {
        $wp->query_vars['post_type'] = 'therapeute';
        $wp->query_vars['name']      = $wp->query_vars['therapeute'];
    }
}
add_action( 'parse_request', 'td_parse_request' );

/**
 * Chargement des shortcodes.
 */
require_once TD_PLUGIN_DIR . 'public/class-td-shortcodes.php';
