<?php
/**
 * Template single pour le CPT therapeute.
 * Affiche la fiche complète d'un thérapeute avec carte de géolocalisation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

while ( have_posts() ) :
    the_post();

    $post_id        = get_the_ID();
    $civilite       = get_post_meta( $post_id, '_td_civilite', true );
    $nom            = get_post_meta( $post_id, '_td_nom', true );
    $prenom         = get_post_meta( $post_id, '_td_prenom', true );
    $titre          = get_post_meta( $post_id, '_td_titre', true );
    $email          = get_post_meta( $post_id, '_td_email', true );
    $tel_pro_1      = get_post_meta( $post_id, '_td_telephone_pro_1', true );
    $tel_pro_2      = get_post_meta( $post_id, '_td_telephone_pro_2', true );
    $adeli          = get_post_meta( $post_id, '_td_adeli', true );
    $site_internet  = get_post_meta( $post_id, '_td_site_internet', true );
    $information    = get_post_meta( $post_id, '_td_information', true );
    $photo_id       = get_post_meta( $post_id, '_td_photo', true );
    $visio          = get_post_meta( $post_id, '_td_visio', true );
    $addresses      = TD_Address_DB::get_by_therapeute( $post_id );
    $categories     = get_the_terms( $post_id, 'therapeute_category' );

    // Préparer les marqueurs pour la carte
    $markers = [];
    foreach ( $addresses as $addr ) {
        if ( $addr->latitude && $addr->longitude ) {
            $markers[] = [
                'lat'   => floatval( $addr->latitude ),
                'lng'   => floatval( $addr->longitude ),
                'title' => $addr->adresse . ', ' . $addr->ville,
            ];
        }
    }

    $telephone_affiche = $tel_pro_1 ?: $tel_pro_2;
?>

<div class="td-single-wrap">
    <div class="td-single">

        <!-- En-tête -->
        <header class="td-single-header">
            <div class="td-single-photo">
                <?php if ( $photo_id ) : ?>
                    <?php echo wp_get_attachment_image( $photo_id, 'medium_large' ); ?>
                <?php else : ?>
                    <div class="td-single-avatar">
                        <?php echo esc_html( mb_substr( $prenom, 0, 1 ) . mb_substr( $nom, 0, 1 ) ); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="td-single-identity">
                <?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
                    <div class="td-single-cats">
                        <?php foreach ( $categories as $cat ) : ?>
                            <span class="td-single-cat"><?php echo esc_html( $cat->name ); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <h1 class="td-single-name">
                    <?php echo esc_html( "$prenom $nom" ); ?>
                </h1>

                <?php if ( $titre ) : ?>
                    <p class="td-single-titre"><?php echo esc_html( $titre ); ?></p>
                <?php endif; ?>

                <?php if ( $adeli ) : ?>
                    <p class="td-single-adeli">ADELI / RPPS : <?php echo esc_html( $adeli ); ?></p>
                <?php endif; ?>

                <?php if ( $visio === '1' ) : ?>
                    <span class="td-single-visio-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="23 7 16 12 23 17 23 7"/>
                            <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
                        </svg>
                        <?php esc_html_e( 'Visioconférence disponible', 'therapist-directory' ); ?>
                    </span>
                <?php endif; ?>
            </div>
        </header>

        <!-- Corps -->
        <div class="td-single-body">

            <!-- Colonne principale -->
            <div class="td-single-main">

                <?php if ( $information ) : ?>
                    <section class="td-single-section">
                        <h2><?php esc_html_e( 'Présentation', 'therapist-directory' ); ?></h2>
                        <div class="td-single-text">
                            <?php echo wp_kses_post( wpautop( $information ) ); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ( ! empty( $addresses ) ) : ?>
                    <section class="td-single-section">
                        <h2><?php esc_html_e( 'Lieux d\'exercice', 'therapist-directory' ); ?></h2>
                        <div class="td-single-addresses">
                            <?php foreach ( $addresses as $addr ) : ?>
                                <div class="td-single-address-item">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <div>
                                        <?php if ( $addr->adresse ) : ?>
                                            <span class="td-single-address-street"><?php echo esc_html( $addr->adresse ); ?></span><br>
                                        <?php endif; ?>
                                        <span class="td-single-address-city">
                                            <?php echo esc_html( trim( $addr->code_postal . ' ' . $addr->ville ) ); ?>
                                            <?php if ( $addr->pays && $addr->pays !== 'France' ) : ?>
                                                — <?php echo esc_html( $addr->pays ); ?>
                                            <?php endif; ?>
                                        </span>
                                        <?php if ( $addr->is_primary ) : ?>
                                            <span class="td-single-badge"><?php esc_html_e( 'Principal', 'therapist-directory' ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ( ! empty( $markers ) ) : ?>
                    <section class="td-single-section">
                        <h2><?php esc_html_e( 'Localisation', 'therapist-directory' ); ?></h2>
                        <div id="td-single-map" class="td-single-map"
                             data-markers="<?php echo esc_attr( wp_json_encode( $markers ) ); ?>">
                        </div>
                    </section>
                <?php endif; ?>
            </div>

            <!-- Sidebar contact -->
            <aside class="td-single-sidebar">
                <div class="td-single-contact-card" data-td-post-id="<?php echo esc_attr( $post_id ); ?>">
                    <h3><?php esc_html_e( 'Contact', 'therapist-directory' ); ?></h3>

                    <?php if ( $telephone_affiche ) : ?>
                        <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $telephone_affiche ) ); ?>" class="td-single-contact-item td-single-contact-phone" data-td-click="phone">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/>
                            </svg>
                            <?php echo esc_html( $telephone_affiche ); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ( $tel_pro_2 ) : ?>
                        <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $tel_pro_2 ) ); ?>" class="td-single-contact-item" data-td-click="phone">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/>
                            </svg>
                            <?php echo esc_html( $tel_pro_2 ); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ( $email ) : ?>
                        <a href="mailto:<?php echo esc_attr( $email ); ?>" class="td-single-contact-item" data-td-click="email">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <?php echo esc_html( $email ); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ( $site_internet ) : ?>
                        <a href="<?php echo esc_url( $site_internet ); ?>" target="_blank" rel="noopener noreferrer" class="td-single-contact-item" data-td-click="website">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="2" y1="12" x2="22" y2="12"/>
                                <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                            </svg>
                            <?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', $site_internet ) ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </aside>
        </div>

        <!-- Retour -->
        <div class="td-single-back">
            <a href="javascript:history.back()" class="td-btn-secondary">
                ← <?php esc_html_e( 'Retour à l\'annuaire', 'therapist-directory' ); ?>
            </a>
        </div>

    </div>
</div>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var mapEl = document.getElementById('td-single-map');
        if (!mapEl || typeof L === 'undefined') return;

        var markers = JSON.parse(mapEl.getAttribute('data-markers') || '[]');
        if (markers.length === 0) return;

        var map = L.map('td-single-map').setView([markers[0].lat, markers[0].lng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 18
        }).addTo(map);

        var bounds = L.latLngBounds();
        markers.forEach(function(m) {
            L.marker([m.lat, m.lng]).addTo(map).bindPopup(m.title);
            bounds.extend([m.lat, m.lng]);
        });

        if (markers.length > 1) {
            map.fitBounds(bounds, { padding: [40, 40] });
        }
    });
})();
</script>

<?php
endwhile;

get_footer();
