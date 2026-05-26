(function ($) {
    'use strict';

    /**
     * Gestion des onglets.
     */
    function initTabs() {
        $('.td-tab-btn').on('click', function () {
            var tab = $(this).data('tab');
            $('.td-tab-btn').removeClass('active');
            $(this).addClass('active');
            $('.td-tab-content').removeClass('active');
            $('#td-tab-' + tab).addClass('active');

            // Rafraîchir TinyMCE quand l'onglet Notes devient visible
            if (tab === 'notes' && typeof tinymce !== 'undefined') {
                var editor = tinymce.get('td_notes');
                if (editor) {
                    editor.fire('show');
                    editor.dom.loadCSS('');
                    setTimeout(function () { editor.execCommand('mceRepaint'); }, 100);
                }
            }
        });
    }

    /**
     * Gestion dynamique des adresses.
     */
    function initAddresses() {
        var addressIndex = $('.td-address-card').length;

        // Ajouter une adresse
        $('#td-add-address').on('click', function () {
            var template = $('#td-address-template').html();
            template = template.replace(/__INDEX__/g, addressIndex);
            $('#td-addresses-container').append(template);

            // Si c'est la première adresse, la marquer comme principale
            if (addressIndex === 0) {
                $('#td-addresses-container .td-address-card:first')
                    .find('input[name="td_address_primary"]')
                    .prop('checked', true);
            }

            addressIndex++;
        });

        // Supprimer une adresse
        $(document).on('click', '.td-remove-address', function () {
            if (confirm(tdAdmin.i18n.confirmDelete)) {
                $(this).closest('.td-address-card').slideUp(200, function () {
                    $(this).remove();
                });
            }
        });

        // Réduire/Déplier une adresse
        $(document).on('click', '.td-toggle-address', function () {
            var card = $(this).closest('.td-address-card');
            var body = card.find('.td-address-card-body');
            var icon = $(this).find('.dashicons');

            body.slideToggle(200);
            icon.toggleClass('dashicons-arrow-up-alt2 dashicons-arrow-down-alt2');
        });
    }

    /**
     * Géocodage via AJAX.
     */
    function initGeocode() {
        $(document).on('click', '.td-geocode-btn', function () {
            var card = $(this).closest('.td-address-card');
            var btn = $(this);
            var status = card.find('.td-geocode-status');

            var data = {
                action: 'td_geocode',
                nonce: tdAdmin.nonce,
                adresse: card.find('input[name$="[adresse]"]').val(),
                code_postal: card.find('input[name$="[code_postal]"]').val(),
                ville: card.find('input[name$="[ville]"]').val(),
                pays: card.find('input[name$="[pays]"]').val()
            };

            btn.prop('disabled', true);
            status.text('…').removeClass('td-status-success td-status-error');

            $.post(tdAdmin.ajaxUrl, data, function (response) {
                btn.prop('disabled', false);

                if (response.success) {
                    card.find('input[name$="[latitude]"]').val(response.data.lat);
                    card.find('input[name$="[longitude]"]').val(response.data.lng);
                    status.text(tdAdmin.i18n.geocodeSuccess).addClass('td-status-success');
                } else {
                    status.text(tdAdmin.i18n.geocodeError).addClass('td-status-error');
                }

                // Effacer le message après 3 secondes
                setTimeout(function () {
                    status.fadeOut(300, function () {
                        $(this).text('').show();
                    });
                }, 3000);
            });
        });
    }

    /**
     * Upload de la photo via la médiathèque WordPress.
     */
    function initPhotoUpload() {
        var mediaFrame;

        $('#td-upload-photo').on('click', function (e) {
            e.preventDefault();

            if (mediaFrame) {
                mediaFrame.open();
                return;
            }

            mediaFrame = wp.media({
                title: 'Choisir une photo',
                button: { text: 'Utiliser cette photo' },
                multiple: false,
                library: { type: 'image' }
            });

            mediaFrame.on('select', function () {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                var thumbUrl = attachment.sizes.thumbnail
                    ? attachment.sizes.thumbnail.url
                    : attachment.url;

                $('#td_photo').val(attachment.id);
                $('#td-photo-preview').html('<img src="' + thumbUrl + '" alt="">');
                $('#td-remove-photo').show();
            });

            mediaFrame.open();
        });

        $('#td-remove-photo').on('click', function () {
            $('#td_photo').val('');
            $('#td-photo-preview').html('<span class="dashicons dashicons-format-image"></span>');
            $(this).hide();
        });
    }

    /**
     * Mise à jour automatique du titre WordPress à partir de nom + prénom.
     */
    function initAutoTitle() {
        function updateTitle() {
            var prenom = $('#td_prenom').val() || '';
            var nom = $('#td_nom').val() || '';
            var title = (prenom + ' ' + nom).trim();
            if (title) {
                $('#title').val(title);
            }
        }

        $('#td_nom, #td_prenom').on('input', updateTitle);
    }

    /**
     * Initialisation.
     */
    $(document).ready(function () {
        if ($('.td-meta-box').length === 0) {
            return;
        }

        initTabs();
        initAddresses();
        initGeocode();
        initPhotoUpload();
        initAutoTitle();
    });

})(jQuery);
