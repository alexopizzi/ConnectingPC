/*
 * Mappa dei servizi (vault "61"): Leaflet + OpenStreetMap, marker raggruppati, popup con link alle schede.
 * I contenuti arrivano da /api/v1/map/points; il testo è inserito sempre con textContent (niente HTML dal server).
 * L'elenco equivalente accanto alla mappa resta la via principale e accessibile.
 */
(function () {
  'use strict';

  var el = document.querySelector('[data-map]');
  if (!el || typeof L === 'undefined') {
    return;
  }

  var center = (el.dataset.center || '45.0526,9.6930').split(',').map(Number);
  // Area della provincia di Piacenza (D-024) con margine: la mappa non si sposta altrove
  var province = L.latLngBounds([44.45, 9.0], [45.30, 10.25]);
  var map = L.map(el, { scrollWheelZoom: false, keyboard: true, minZoom: 9, maxBounds: province.pad(0.1), maxBoundsViscosity: 0.8 })
    .setView(center, Math.max(9, Number(el.dataset.zoom || 10)));
  L.tileLayer(el.dataset.tiles, { maxZoom: 18, minZoom: 9, attribution: el.dataset.attribution }).addTo(map);
  var cluster = typeof L.markerClusterGroup === 'function' ? L.markerClusterGroup({ showCoverageOnHover: false }) : L.layerGroup();
  map.addLayer(cluster);

  var serviceUrl = function (id) {
    return el.dataset.serviceUrl.replace(/\/0$/, '/' + encodeURIComponent(id));
  };

  var node = function (tag, text, attrs) {
    var n = document.createElement(tag);
    if (text) { n.textContent = text; }
    Object.keys(attrs || {}).forEach(function (k) { n.setAttribute(k, attrs[k]); });
    return n;
  };

  var popup = function (point) {
    var box = node('div', null, { 'class': 'map-popup', dir: el.dataset.dir || 'ltr' });
    box.appendChild(node('strong', point.organization));
    if (point.name && point.name !== point.organization) {
      box.appendChild(node('div', point.name));
    }
    box.appendChild(node('div', point.address, { 'class': 'map-popup__address' }));
    if (point.mediation) {
      box.appendChild(node('div', '💬 ' + el.dataset.msgMediation));
    }
    box.appendChild(node('div', el.dataset.msgServices, { 'class': 'map-popup__label' }));
    var list = node('ul');
    point.services.slice(0, 6).forEach(function (s) {
      var li = node('li');
      li.appendChild(node('a', s.name, { href: serviceUrl(s.id) }));
      list.appendChild(li);
    });
    box.appendChild(list);
    box.appendChild(node('a', '🧭 ' + el.dataset.msgDirections, {
      href: 'https://www.openstreetmap.org/directions?route=%3B' + point.lat + '%2C' + point.lng,
      target: '_blank', rel: 'noopener noreferrer', 'class': 'map-popup__directions'
    }));
    return box;
  };

  var markersByService = {};
  fetch(el.dataset.pointsUrl, { headers: { Accept: 'application/json' } })
    .then(function (r) { return r.ok ? r.json() : { points: [] }; })
    .then(function (data) {
      var bounds = [];
      (data.points || []).forEach(function (point) {
        var marker = L.marker([point.lat, point.lng], { title: point.organization, alt: point.organization, keyboard: true });
        marker.bindPopup(function () { return popup(point); });
        cluster.addLayer(marker);
        bounds.push([point.lat, point.lng]);
        point.services.forEach(function (s) { (markersByService[s.id] = markersByService[s.id] || []).push(marker); });
      });
      if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [24, 24], maxZoom: 14 });
      } else if (bounds.length === 1) {
        map.setView(bounds[0], 14);
      }
    })
    .catch(function () { /* la mappa resta vuota: l'elenco è comunque disponibile */ });

  // Dall'elenco alla mappa: "mostra sulla mappa" evidenzia il punto
  document.querySelectorAll('[data-service-id]').forEach(function (item) {
    item.addEventListener('focusin', function () {
      var markers = markersByService[item.dataset.serviceId];
      if (markers && markers.length && typeof cluster.zoomToShowLayer === 'function') {
        cluster.zoomToShowLayer(markers[0], function () { markers[0].openPopup(); });
      }
    });
  });

  // Posizione scelta in near-me.js: segnaposto "sei qui" (solo nel browser)
  var you = null;
  document.addEventListener('nearme:position', function (event) {
    var pos = [event.detail.lat, event.detail.lng];
    if (you) { map.removeLayer(you); }
    you = L.circleMarker(pos, { radius: 9, weight: 3 }).addTo(map).bindTooltip(el.dataset.msgYou);
    map.setView(pos, 12);
  });
})();
