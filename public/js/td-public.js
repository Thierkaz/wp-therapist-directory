(function ($) {
    'use strict';

    /**
     * Initialisation des cartes Leaflet (shortcode [therapist_map]).
     */
    function initMaps() {
        $('.td-map-container[data-markers]').each(function () {
            var container = $(this);
            var markers = JSON.parse(container.attr('data-markers') || '[]');

            if (markers.length === 0) {
                container.html('<p style="text-align:center;padding:40px;color:#868e96;">Aucun thérapeute géolocalisé.</p>');
                return;
            }

            var map = L.map(container.attr('id')).setView([46.6, 2.3], 6);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 18
            }).addTo(map);

            var bounds = L.latLngBounds();

            markers.forEach(function (m) {
                var popup = '<div class="td-popup-name">' + escapeHtml(m.title) + '</div>';
                if (m.titre) {
                    popup += '<div class="td-popup-titre">' + escapeHtml(m.titre) + '</div>';
                }
                if (m.ville) {
                    popup += '<div class="td-popup-ville">' + escapeHtml(m.ville) + '</div>';
                }
                if (m.link) {
                    popup += '<a href="' + m.link + '" style="font-size:12px;">Voir la fiche</a>';
                }

                L.marker([m.lat, m.lng])
                    .addTo(map)
                    .bindPopup(popup);

                bounds.extend([m.lat, m.lng]);
            });

            map.fitBounds(bounds, { padding: [30, 30] });
        });
    }

    /**
     * Recherche par proximité (shortcode [therapist_search]).
     */
    function initSearch() {
        var searchMap = null;
        var searchMarkers = [];

        $('#td-search-form').on('submit', function (e) {
            e.preventDefault();

            var form = $(this);
            var container = form.closest('.td-search');
            var category = container.data('category') || '';

            var resultsEl = $('#td-search-results');
            var mapEl = $('#td-search-map');
            var loadingEl = $('#td-search-loading');
            var emptyEl = $('#td-search-empty');

            // Reset
            resultsEl.empty().hide();
            mapEl.hide();
            emptyEl.hide();
            loadingEl.show();

            $.post(tdPublic.ajaxUrl, {
                action: 'td_search_nearby',
                nonce: tdPublic.nonce,
                code_postal: form.find('[name="code_postal"]').val(),
                radius: form.find('[name="radius"]').val(),
                category: category
            }, function (response) {
                loadingEl.hide();

                if (!response.success || response.data.results.length === 0) {
                    emptyEl.show();
                    return;
                }

                var results = response.data.results;
                var center = response.data.center;

                // Afficher les cartes
                results.forEach(function (t) {
                    var initials = (t.prenom ? t.prenom.charAt(0) : '') + (t.nom ? t.nom.charAt(0) : '');
                    var photoHtml = t.photo
                        ? '<img src="' + t.photo + '" alt="">'
                        : '<div class="td-card-avatar">' + escapeHtml(initials) + '</div>';

                    var villesHtml = '';
                    if (t.villes && t.villes.length > 0) {
                        villesHtml = '<p class="td-card-location">' + escapeHtml(t.villes.join(', ')) + '</p>';
                    }

                    var websiteHtml = '';
                    if (t.site_internet) {
                        var displayUrl = t.site_internet.replace(/^https?:\/\/(www\.)?/, '');
                        websiteHtml = '<p class="td-card-website"><a href="' + t.site_internet + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(displayUrl) + '</a></p>';
                    }

                    var visioHtml = t.visio ? '<p class="td-card-visio"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg> Visio</p>' : '';

                    var card = '<div class="td-card">'
                        + '<div class="td-card-photo">' + photoHtml + '</div>'
                        + '<div class="td-card-body">'
                        + '<h3 class="td-card-name"><a href="' + t.permalink + '">' + escapeHtml(t.prenom + ' ' + t.nom) + '</a></h3>'
                        + (t.titre ? '<p class="td-card-titre">' + escapeHtml(t.titre) + '</p>' : '')
                        + '<p class="td-card-distance">' + t.distance + ' km</p>'
                        + villesHtml
                        + (t.telephone ? '<p class="td-card-phone">' + escapeHtml(t.telephone) + '</p>' : '')
                        + visioHtml
                        + websiteHtml
                        + '</div></div>';
                    resultsEl.append(card);
                });
                resultsEl.show();

                // Afficher la carte
                mapEl.show().css('height', '400px');

                if (searchMap) {
                    searchMap.remove();
                }

                searchMap = L.map('td-search-map').setView([center.lat, center.lng], 10);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 18
                }).addTo(searchMap);

                var bounds = L.latLngBounds();

                // Marqueur du centre (position de l'utilisateur)
                L.circleMarker([center.lat, center.lng], {
                    radius: 8,
                    fillColor: '#4f6df5',
                    color: '#fff',
                    weight: 2,
                    fillOpacity: 0.8
                }).addTo(searchMap).bindPopup('Votre position');

                bounds.extend([center.lat, center.lng]);

                results.forEach(function (t) {
                    if (t.latitude && t.longitude) {
                        var popup = '<strong>' + escapeHtml(t.prenom + ' ' + t.nom) + '</strong><br>'
                            + (t.titre ? '<em>' + escapeHtml(t.titre) + '</em><br>' : '')
                            + t.distance + ' km'
                            + '<br><a href="' + t.permalink + '">Voir la fiche</a>';

                        L.marker([t.latitude, t.longitude])
                            .addTo(searchMap)
                            .bindPopup(popup);

                        bounds.extend([t.latitude, t.longitude]);
                    }
                });

                searchMap.fitBounds(bounds, { padding: [30, 30] });
            });
        });
    }

    /**
     * Échapper le HTML.
     */
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    /**
     * Tracking des clics sur les liens de contact.
     */
    function initClickTracking() {
        $(document).on('click', '[data-td-click]', function () {
            var $link    = $(this);
            var clickType = $link.data('td-click');
            var postId    = $link.closest('[data-td-post-id]').data('td-post-id');

            if (!postId || !clickType) {
                return;
            }

            // Envoi asynchrone (fire-and-forget, ne bloque pas la navigation)
            navigator.sendBeacon
                ? navigator.sendBeacon(tdPublic.ajaxUrl, new URLSearchParams({
                    action: 'td_record_click',
                    nonce: tdPublic.nonce,
                    post_id: postId,
                    click_type: clickType
                }))
                : $.post(tdPublic.ajaxUrl, {
                    action: 'td_record_click',
                    nonce: tdPublic.nonce,
                    post_id: postId,
                    click_type: clickType
                });
        });
    }

    /**
     * Initialisation.
     */
    $(document).ready(function () {
        initMaps();
        initSearch();
        initClickTracking();
    });

})(jQuery);
