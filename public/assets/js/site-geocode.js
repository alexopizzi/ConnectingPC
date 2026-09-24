/*
 * Geocodifica assistita nel modulo della sede (RF-40, vault "61"):
 * "Trova sulla mappa" chiede al server le proposte (Nominatim lato server), l'utente sceglie e poi
 * corregge trascinando il marcatore. Le coordinate finiscono nei campi lat/lng: senza JavaScript
 * restano modificabili a mano. Nessun dato viene inviato a terzi dal browser.
 */
(function () {
    'use strict';

    var box = document.querySelector('[data-geocode]');
    if (!box) {
        return;
    }
    var form = box.closest('form');
    var lat = form.querySelector('[name="lat"]');
    var lng = form.querySelector('[name="lng"]');
    var status = box.querySelector('[data-geocode-status]');
    var list = box.querySelector('[data-geocode-results]');
    var mapEl = box.querySelector('[data-geocode-map]');
    var map = null;
    var marker = null;

    function setCoordinates(latitude, longitude) {
        lat.value = latitude.toFixed(6);
        lng.value = longitude.toFixed(6);
        showMap(latitude, longitude);
    }

    function showMap(latitude, longitude) {
        if (!window.L || !mapEl) {
            return;
        }
        mapEl.hidden = false;
        if (!map) {
            map = L.map(mapEl).setView([latitude, longitude], 17);
            L.tileLayer(mapEl.dataset.tiles, { maxZoom: 19, attribution: mapEl.dataset.attribution }).addTo(map);
            marker = L.marker([latitude, longitude], { draggable: true, keyboard: true, title: mapEl.dataset.markerLabel }).addTo(map);
            marker.on('dragend', function () {
                var p = marker.getLatLng();
                lat.value = p.lat.toFixed(6);
                lng.value = p.lng.toFixed(6);
                status.textContent = box.dataset.movedText;
            });
        } else {
            marker.setLatLng([latitude, longitude]);
            map.setView([latitude, longitude], 17);
        }
    }

    box.querySelector('[data-geocode-button]').addEventListener('click', function () {
        var data = new FormData();
        ['address_line', 'postal_code', 'territory_id', '_token'].forEach(function (name) {
            var field = form.querySelector('[name="' + name + '"]');
            data.append(name, field ? field.value : '');
        });
        status.textContent = box.dataset.searchingText;
        list.textContent = '';
        fetch(box.dataset.geocode, { method: 'POST', body: data, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (payload.error) {
                    status.textContent = payload.error.message;
                    return;
                }
                if (!payload.results.length) {
                    status.textContent = box.dataset.noneText;
                    return;
                }
                status.textContent = box.dataset.chooseText;
                payload.results.forEach(function (result) {
                    var item = document.createElement('li');
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'button button--link';
                    button.textContent = result.label;
                    button.addEventListener('click', function () {
                        setCoordinates(result.lat, result.lng);
                        status.textContent = box.dataset.chosenText;
                    });
                    item.appendChild(button);
                    list.appendChild(item);
                });
            })
            .catch(function () { status.textContent = box.dataset.errorText; });
    });

    if (lat.value !== '' && lng.value !== '') {
        showMap(parseFloat(lat.value), parseFloat(lng.value));
    }
}());
