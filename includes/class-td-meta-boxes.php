<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TD_Meta_Boxes {

    /**
     * Champs obligatoires.
     */
    private static $required = [
        '_td_civilite',
        '_td_nom',
        '_td_prenom',
        '_td_titre',
        '_td_email',
        '_td_telephone_principal',
    ];

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_therapeute', [ $this, 'save' ], 10, 2 );
    }

    /**
     * Enregistrement de la meta box unique à onglets.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'td_therapeute_details',
            __( 'Fiche du thérapeute', 'therapist-directory' ),
            [ $this, 'render' ],
            'therapeute',
            'normal',
            'high'
        );

        // Supprimer les meta boxes par défaut qu'on n'utilise pas
        remove_meta_box( 'slugdiv', 'therapeute', 'normal' );
    }

    /**
     * Rendu de la meta box avec onglets.
     */
    public function render( $post ) {
        wp_nonce_field( 'td_save_therapeute', 'td_nonce' );

        // Récupération des valeurs
        $fields = [
            'civilite'             => get_post_meta( $post->ID, '_td_civilite', true ),
            'nom'                  => get_post_meta( $post->ID, '_td_nom', true ),
            'prenom'               => get_post_meta( $post->ID, '_td_prenom', true ),
            'titre'                => get_post_meta( $post->ID, '_td_titre', true ),
            'email'                => get_post_meta( $post->ID, '_td_email', true ),
            'telephone_principal'  => get_post_meta( $post->ID, '_td_telephone_principal', true ),
            'adeli'                => get_post_meta( $post->ID, '_td_adeli', true ),
            'site_internet'        => get_post_meta( $post->ID, '_td_site_internet', true ),
            'information'          => get_post_meta( $post->ID, '_td_information', true ),
            'notes'                => get_post_meta( $post->ID, '_td_notes', true ),
            'photo'                => get_post_meta( $post->ID, '_td_photo', true ),
            'telephone_pro_1'      => get_post_meta( $post->ID, '_td_telephone_pro_1', true ),
            'telephone_pro_2'      => get_post_meta( $post->ID, '_td_telephone_pro_2', true ),
            'visio'                => get_post_meta( $post->ID, '_td_visio', true ),
        ];

        // Récupération des adresses
        $addresses = TD_Address_DB::get_by_therapeute( $post->ID );
        ?>

        <div class="td-meta-box">
            <!-- Navigation onglets -->
            <nav class="td-tabs-nav">
                <button type="button" class="td-tab-btn active" data-tab="identite">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php esc_html_e( 'Identité', 'therapist-directory' ); ?>
                </button>
                <button type="button" class="td-tab-btn" data-tab="contact">
                    <span class="dashicons dashicons-phone"></span>
                    <?php esc_html_e( 'Contact', 'therapist-directory' ); ?>
                </button>
                <button type="button" class="td-tab-btn" data-tab="adresses">
                    <span class="dashicons dashicons-location"></span>
                    <?php esc_html_e( 'Adresses', 'therapist-directory' ); ?>
                </button>
                <button type="button" class="td-tab-btn" data-tab="notes">
                    <span class="dashicons dashicons-edit"></span>
                    <?php esc_html_e( 'Notes & Infos', 'therapist-directory' ); ?>
                </button>
            </nav>

            <!-- Onglet Identité -->
            <div class="td-tab-content active" id="td-tab-identite">
                <div class="td-fields-grid">
                    <div class="td-field td-field-half">
                        <label for="td_civilite">
                            <?php esc_html_e( 'Civilité', 'therapist-directory' ); ?>
                            <span class="td-required">*</span>
                        </label>
                        <select id="td_civilite" name="td_civilite">
                            <option value=""><?php esc_html_e( '— Choisir —', 'therapist-directory' ); ?></option>
                            <option value="femme" <?php selected( $fields['civilite'], 'femme' ); ?>>
                                <?php esc_html_e( 'Femme', 'therapist-directory' ); ?>
                            </option>
                            <option value="homme" <?php selected( $fields['civilite'], 'homme' ); ?>>
                                <?php esc_html_e( 'Homme', 'therapist-directory' ); ?>
                            </option>
                        </select>
                    </div>

                    <div class="td-field td-field-half">
                        <label for="td_titre">
                            <?php esc_html_e( 'Titre', 'therapist-directory' ); ?>
                            <span class="td-required">*</span>
                        </label>
                        <input type="text" id="td_titre" name="td_titre"
                               value="<?php echo esc_attr( $fields['titre'] ); ?>"
                               placeholder="<?php esc_attr_e( 'Ex: Ostéopathe D.O.', 'therapist-directory' ); ?>">
                    </div>

                    <div class="td-field td-field-half">
                        <label for="td_nom">
                            <?php esc_html_e( 'Nom', 'therapist-directory' ); ?>
                            <span class="td-required">*</span>
                        </label>
                        <input type="text" id="td_nom" name="td_nom"
                               value="<?php echo esc_attr( $fields['nom'] ); ?>">
                    </div>

                    <div class="td-field td-field-half">
                        <label for="td_prenom">
                            <?php esc_html_e( 'Prénom', 'therapist-directory' ); ?>
                            <span class="td-required">*</span>
                        </label>
                        <input type="text" id="td_prenom" name="td_prenom"
                               value="<?php echo esc_attr( $fields['prenom'] ); ?>">
                    </div>

                    <div class="td-field td-field-full">
                        <label><?php esc_html_e( 'Photo', 'therapist-directory' ); ?></label>
                        <div class="td-photo-upload">
                            <div class="td-photo-preview" id="td-photo-preview">
                                <?php if ( $fields['photo'] ) : ?>
                                    <?php echo wp_get_attachment_image( $fields['photo'], 'thumbnail' ); ?>
                                <?php else : ?>
                                    <span class="dashicons dashicons-format-image"></span>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" id="td_photo" name="td_photo"
                                   value="<?php echo esc_attr( $fields['photo'] ); ?>">
                            <button type="button" class="button td-upload-btn" id="td-upload-photo">
                                <?php esc_html_e( 'Choisir une photo', 'therapist-directory' ); ?>
                            </button>
                            <button type="button" class="button td-remove-btn" id="td-remove-photo"
                                    style="<?php echo $fields['photo'] ? '' : 'display:none;'; ?>">
                                <?php esc_html_e( 'Supprimer', 'therapist-directory' ); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglet Contact -->
            <div class="td-tab-content" id="td-tab-contact">
                <div class="td-fields-grid">
                    <div class="td-field td-field-half">
                        <label for="td_email">
                            <?php esc_html_e( 'Email', 'therapist-directory' ); ?>
                            <span class="td-required">*</span>
                        </label>
                        <input type="email" id="td_email" name="td_email"
                               value="<?php echo esc_attr( $fields['email'] ); ?>">
                    </div>

                    <div class="td-field td-field-half">
                        <label for="td_telephone_principal">
                            <?php esc_html_e( 'Téléphone principal', 'therapist-directory' ); ?>
                            <span class="td-required">*</span>
                        </label>
                        <input type="tel" id="td_telephone_principal" name="td_telephone_principal"
                               value="<?php echo esc_attr( $fields['telephone_principal'] ); ?>">
                    </div>

                    <div class="td-field td-field-half">
                        <label for="td_telephone_pro_1">
                            <?php esc_html_e( 'Téléphone pro 1 (publié)', 'therapist-directory' ); ?>
                        </label>
                        <input type="tel" id="td_telephone_pro_1" name="td_telephone_pro_1"
                               value="<?php echo esc_attr( $fields['telephone_pro_1'] ); ?>">
                    </div>

                    <div class="td-field td-field-half">
                        <label for="td_telephone_pro_2">
                            <?php esc_html_e( 'Téléphone pro 2', 'therapist-directory' ); ?>
                        </label>
                        <input type="tel" id="td_telephone_pro_2" name="td_telephone_pro_2"
                               value="<?php echo esc_attr( $fields['telephone_pro_2'] ); ?>">
                    </div>

                    <div class="td-field td-field-half">
                        <label for="td_site_internet">
                            <?php esc_html_e( 'Site internet', 'therapist-directory' ); ?>
                        </label>
                        <input type="url" id="td_site_internet" name="td_site_internet"
                               value="<?php echo esc_attr( $fields['site_internet'] ); ?>"
                               placeholder="https://">
                    </div>

                    <div class="td-field td-field-half">
                        <label for="td_adeli">
                            <?php esc_html_e( 'Numéro ADELI', 'therapist-directory' ); ?>
                        </label>
                        <input type="text" id="td_adeli" name="td_adeli"
                               value="<?php echo esc_attr( $fields['adeli'] ); ?>">
                    </div>

                    <div class="td-field td-field-full">
                        <label class="td-checkbox-label">
                            <input type="checkbox" id="td_visio" name="td_visio" value="1"
                                   <?php checked( $fields['visio'], '1' ); ?>>
                            <?php esc_html_e( 'Propose des consultations en visioconférence', 'therapist-directory' ); ?>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Onglet Adresses -->
            <div class="td-tab-content" id="td-tab-adresses">
                <div id="td-addresses-container">
                    <?php if ( ! empty( $addresses ) ) : ?>
                        <?php foreach ( $addresses as $index => $addr ) : ?>
                            <?php self::render_address_card( $addr, $index ); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <button type="button" class="button button-primary" id="td-add-address">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e( 'Ajouter une adresse', 'therapist-directory' ); ?>
                </button>

                <!-- Template pour nouvelle adresse (utilisé par JS) -->
                <template id="td-address-template">
                    <?php self::render_address_card( null, '__INDEX__' ); ?>
                </template>
            </div>

            <!-- Onglet Notes -->
            <div class="td-tab-content" id="td-tab-notes">
                <div class="td-fields-grid">
                    <div class="td-field td-field-full">
                        <label for="td_information">
                            <?php esc_html_e( 'Informations', 'therapist-directory' ); ?>
                        </label>
                        <?php
                        wp_editor( $fields['information'], 'td_information', [
                            'textarea_name' => 'td_information',
                            'textarea_rows' => 8,
                            'media_buttons' => true,
                            'teeny'         => true,
                            'quicktags'     => true,
                            'tinymce'       => [
                                'toolbar1' => 'bold,italic,bullist,numlist,link,unlink,separator,undo,redo',
                                'toolbar2' => '',
                            ],
                        ] );
                        ?>                        
                    </div>

                    <div class="td-field td-field-full td-wysiwyg-field">
                        <label>
                            <?php esc_html_e( 'Notes internes', 'therapist-directory' ); ?>
                        </label>
                        <?php
                        wp_editor( $fields['notes'], 'td_notes', [
                            'textarea_name' => 'td_notes',
                            'textarea_rows' => 8,
                            'media_buttons' => false,
                            'teeny'         => true,
                            'quicktags'     => true,
                            'tinymce'       => [
                                'toolbar1' => 'bold,italic,bullist,numlist,separator,undo,redo',
                                'toolbar2' => '',
                            ],
                        ] );
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu d'une carte adresse (utilisé aussi comme template JS).
     */
    public static function render_address_card( $address, $index ) {
        $addr = wp_parse_args( (array) $address, [
            'id'          => '',
            'adresse'     => '',
            'code_postal' => '',
            'ville'       => '',
            'pays'        => 'France',
            'latitude'    => '',
            'longitude'   => '',
            'is_primary'  => 0,
        ]);
        $prefix = "td_addresses[{$index}]";
        ?>
        <div class="td-address-card" data-index="<?php echo esc_attr( $index ); ?>">
            <div class="td-address-card-header">
                <h4>
                    <span class="dashicons dashicons-location"></span>
                    <?php
                    if ( $addr['ville'] ) {
                        echo esc_html( $addr['adresse'] . ', ' . $addr['ville'] );
                    } else {
                        esc_html_e( 'Nouvelle adresse', 'therapist-directory' );
                    }
                    ?>
                </h4>
                <div class="td-address-actions">
                    <label class="td-primary-label">
                        <input type="radio" name="td_address_primary"
                               value="<?php echo esc_attr( $index ); ?>"
                               <?php checked( $addr['is_primary'], 1 ); ?>>
                        <?php esc_html_e( 'Principale', 'therapist-directory' ); ?>
                    </label>
                    <button type="button" class="td-btn-icon td-toggle-address" title="<?php esc_attr_e( 'Réduire', 'therapist-directory' ); ?>">
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                    <button type="button" class="td-btn-icon td-remove-address" title="<?php esc_attr_e( 'Supprimer', 'therapist-directory' ); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>

            <div class="td-address-card-body">
                <input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[id]"
                       value="<?php echo esc_attr( $addr['id'] ); ?>">

                <div class="td-fields-grid">
                    <div class="td-field td-field-full">
                        <label><?php esc_html_e( 'Adresse', 'therapist-directory' ); ?></label>
                        <input type="text" name="<?php echo esc_attr( $prefix ); ?>[adresse]"
                               value="<?php echo esc_attr( $addr['adresse'] ); ?>"
                               placeholder="<?php esc_attr_e( 'Numéro et nom de la rue', 'therapist-directory' ); ?>">
                    </div>

                    <div class="td-field td-field-quarter">
                        <label><?php esc_html_e( 'Code postal', 'therapist-directory' ); ?></label>
                        <input type="text" name="<?php echo esc_attr( $prefix ); ?>[code_postal]"
                               value="<?php echo esc_attr( $addr['code_postal'] ); ?>">
                    </div>

                    <div class="td-field td-field-half">
                        <label><?php esc_html_e( 'Ville', 'therapist-directory' ); ?></label>
                        <input type="text" name="<?php echo esc_attr( $prefix ); ?>[ville]"
                               value="<?php echo esc_attr( $addr['ville'] ); ?>">
                    </div>

                    <div class="td-field td-field-quarter">
                        <label><?php esc_html_e( 'Pays', 'therapist-directory' ); ?></label>
                        <input type="text" name="<?php echo esc_attr( $prefix ); ?>[pays]"
                               value="<?php echo esc_attr( $addr['pays'] ); ?>">
                    </div>

                    <div class="td-field td-field-quarter">
                        <label><?php esc_html_e( 'Latitude', 'therapist-directory' ); ?></label>
                        <input type="text" name="<?php echo esc_attr( $prefix ); ?>[latitude]"
                               value="<?php echo esc_attr( $addr['latitude'] ); ?>"
                               class="td-geo-field" readonly>
                    </div>

                    <div class="td-field td-field-quarter">
                        <label><?php esc_html_e( 'Longitude', 'therapist-directory' ); ?></label>
                        <input type="text" name="<?php echo esc_attr( $prefix ); ?>[longitude]"
                               value="<?php echo esc_attr( $addr['longitude'] ); ?>"
                               class="td-geo-field" readonly>
                    </div>

                    <div class="td-field td-field-half td-geo-actions">
                        <button type="button" class="button td-geocode-btn">
                            <span class="dashicons dashicons-location-alt"></span>
                            <?php esc_html_e( 'Géolocaliser', 'therapist-directory' ); ?>
                        </button>
                        <span class="td-geocode-status"></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Sauvegarde des données.
     */
    public function save( $post_id, $post ) {
        // Vérifications de sécurité
        if ( ! isset( $_POST['td_nonce'] ) || ! wp_verify_nonce( $_POST['td_nonce'], 'td_save_therapeute' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Sauvegarde des champs simples
        $text_fields = [
            'td_civilite'             => '_td_civilite',
            'td_nom'                  => '_td_nom',
            'td_prenom'               => '_td_prenom',
            'td_titre'                => '_td_titre',
            'td_email'                => '_td_email',
            'td_telephone_principal'  => '_td_telephone_principal',
            'td_adeli'                => '_td_adeli',
            'td_site_internet'        => '_td_site_internet',
            'td_telephone_pro_1'      => '_td_telephone_pro_1',
            'td_telephone_pro_2'      => '_td_telephone_pro_2',
        ];

        foreach ( $text_fields as $field => $meta_key ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $field ] ) );
            }
        }

        // Checkbox visio
        update_post_meta( $post_id, '_td_visio', isset( $_POST['td_visio'] ) ? '1' : '0' );

        // Champs textarea
        if ( isset( $_POST['td_information'] ) ) {
            update_post_meta( $post_id, '_td_information', wp_kses_post( $_POST['td_information'] ) );
        }
        if ( isset( $_POST['td_notes'] ) ) {
            update_post_meta( $post_id, '_td_notes', wp_kses_post( $_POST['td_notes'] ) );
        }

        // Photo
        if ( isset( $_POST['td_photo'] ) ) {
            update_post_meta( $post_id, '_td_photo', absint( $_POST['td_photo'] ) );
        }

        // Mise à jour automatique du titre du post
        $nom    = sanitize_text_field( $_POST['td_nom'] ?? '' );
        $prenom = sanitize_text_field( $_POST['td_prenom'] ?? '' );
        if ( $nom || $prenom ) {
            remove_action( 'save_post_therapeute', [ $this, 'save' ] );
            wp_update_post( [
                'ID'         => $post_id,
                'post_title' => trim( "$prenom $nom" ),
            ]);
            add_action( 'save_post_therapeute', [ $this, 'save' ], 10, 2 );
        }

        // Sauvegarde des adresses
        $this->save_addresses( $post_id );
    }

    /**
     * Sauvegarde des adresses avec géocodage.
     */
    private function save_addresses( $post_id ) {
        $addresses = $_POST['td_addresses'] ?? [];
        $primary   = $_POST['td_address_primary'] ?? null;

        // IDs des adresses existantes
        $existing_ids = array_map( function( $a ) {
            return $a->id;
        }, TD_Address_DB::get_by_therapeute( $post_id ) );

        $submitted_ids = [];

        foreach ( $addresses as $index => $data ) {
            if ( $index === '__INDEX__' ) {
                continue; // Template JS, ignorer
            }

            $address_data = [
                'therapeute_id' => $post_id,
                'adresse'       => sanitize_text_field( $data['adresse'] ?? '' ),
                'code_postal'   => sanitize_text_field( $data['code_postal'] ?? '' ),
                'ville'         => sanitize_text_field( $data['ville'] ?? '' ),
                'pays'          => sanitize_text_field( $data['pays'] ?? 'France' ),
                'latitude'      => floatval( $data['latitude'] ?? 0 ) ?: null,
                'longitude'     => floatval( $data['longitude'] ?? 0 ) ?: null,
                'is_primary'    => ( (string) $primary === (string) $index ) ? 1 : 0,
            ];

            // Géocodage si pas de coordonnées
            if ( empty( $address_data['latitude'] ) && ! empty( $address_data['adresse'] ) ) {
                $coords = TD_Geocoder::geocode(
                    $address_data['adresse'],
                    $address_data['code_postal'],
                    $address_data['ville'],
                    $address_data['pays']
                );
                if ( $coords ) {
                    $address_data['latitude']  = $coords['lat'];
                    $address_data['longitude'] = $coords['lng'];
                }
            }

            $existing_id = intval( $data['id'] ?? 0 );
            if ( $existing_id ) {
                TD_Address_DB::update( $existing_id, $address_data );
                $submitted_ids[] = $existing_id;
            } else {
                $new_id = TD_Address_DB::insert( $address_data );
                if ( $new_id ) {
                    $submitted_ids[] = $new_id;
                }
            }
        }

        // Supprimer les adresses retirées du formulaire
        $to_delete = array_diff( $existing_ids, $submitted_ids );
        foreach ( $to_delete as $id ) {
            TD_Address_DB::delete( $id );
        }
    }
}
