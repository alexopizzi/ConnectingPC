/*
 * "Vicino a me" (D-006): la posizione è letta una sola volta, solo dopo il clic, e resta nel browser.
 * Nessuna richiesta al server, nessun salvataggio (niente cookie o localStorage).
 * Alternativa senza geolocalizzazione: scelta del comune (centroide).
 */
(function () {
  'use strict';

  var box = document.querySelector('[data-near-me]');
  var list = document.querySelector('[data-near-me-list]');
  if (!box || !list) {
    return;
  }
  box.hidden = false;
  var status = box.querySelector('[data-near-me-status]');

  var haversine = function (a, b) {
    var rad = Math.PI / 180;
    var dLat = (b[0] - a[0]) * rad;
    var dLng = (b[1] - a[1]) * rad;
    var h = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(a[0] * rad) * Math.cos(b[0] * rad) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return 6371 * 2 * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
  };

  var formatKm = function (km) {
    var lang = document.documentElement.lang || 'it';
    return new Intl.NumberFormat(lang, { maximumFractionDigits: km < 10 ? 1 : 0 }).format(km) + ' ' + box.dataset.msgKm;
  };

  var sortBy = function (lat, lng) {
    var items = Array.prototype.slice.call(list.children);
    items.forEach(function (item) {
      var hasCoords = item.dataset.lat !== '' && item.dataset.lng !== '';
      item.dataset.distance = hasCoords ? haversine([lat, lng], [Number(item.dataset.lat), Number(item.dataset.lng)]) : '99999';
      var slot = item.querySelector('[data-distance]');
      if (slot) {
        slot.textContent = hasCoords ? ' · ' + formatKm(Number(item.dataset.distance)) : '';
      }
    });
    items.sort(function (a, b) { return Number(a.dataset.distance) - Number(b.dataset.distance); });
    items.forEach(function (item) { list.appendChild(item); });
    status.textContent = box.dataset.msgSorted;
    document.dispatchEvent(new CustomEvent('nearme:position', { detail: { lat: lat, lng: lng } }));
  };

  var locate = box.querySelector('[data-near-me-locate]');
  if (!('geolocation' in navigator)) {
    locate.hidden = true;
  }
  locate.addEventListener('click', function () {
    status.textContent = box.dataset.msgSearching;
    navigator.geolocation.getCurrentPosition(
      function (position) { sortBy(position.coords.latitude, position.coords.longitude); },
      function (error) { status.textContent = error.code === 1 ? box.dataset.msgDenied : box.dataset.msgError; },
      { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 }
    );
  });

  box.querySelector('[data-near-me-town]').addEventListener('change', function (event) {
    if (!event.target.value) { return; }
    var parts = event.target.value.split(',').map(Number);
    sortBy(parts[0], parts[1]);
  });
})();
