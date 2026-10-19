/*
 * Mappa dei servizi per comune (vault "61", D-038): Leaflet + OpenStreetMap.
 * - Livello dei comuni (confini ISTAT) colorato secondo i servizi raggiungibili: un clic su un comune
 *   filtra elenco e punti senza ricaricare la pagina (l'URL resta condivisibile, il tasto Indietro funziona).
 * - Il menu "Scegli il tuo comune" fa la stessa cosa da tastiera; l'annuncio dei risultati è in una regione live.
 * - Testi inseriti sempre con textContent (niente HTML dal server, tranne il frammento della pagina stessa).
 * Senza JavaScript la pagina funziona con i normali moduli GET.
 */
(function () {
  'use strict';

  var app = document.querySelector('[data-map-app]');
  var el = document.querySelector('[data-map]');
  if (!app || !el || typeof L === 'undefined') {
    return;
  }
  document.documentElement.classList.add('map-enhanced');

  var townSelect = app.querySelector('[data-town-select]');
  var results = app.querySelector('[data-map-results]');
  var announcer = app.querySelector('[data-map-announcer]');
  var filtersForm = app.querySelector('[data-catalog-filters]');
  var toolbar = app.querySelector('[data-map-toolbar]');
  var legend = app.querySelector('[data-map-legend]');
  var sprite = el.dataset.sprite;
  var iconMap = {};
  try { iconMap = JSON.parse(el.dataset.icons || '{}'); } catch (e) { iconMap = {}; }
  var lang = document.documentElement.lang || 'it';
  var fmt = new Intl.NumberFormat(lang);

  // --- Stato: i parametri dell'URL sono la fonte (stessi nomi dei moduli GET) --------------------------
  var params = function () { return new URLSearchParams(window.location.search); };
  var withLocale = function (base, search) {
    var p = new URLSearchParams(search);
    p.delete('page');
    p.set('locale', app.dataset.locale);
    return base + '?' + p.toString();
  };

  // --- Mappa di base -------------------------------------------------------------------------------
  var center = (el.dataset.center || '45.0526,9.6930').split(',').map(Number);
  var province = L.latLngBounds([44.45, 9.0], [45.30, 10.25]); // D-024, con margine
  var map = L.map(el, { scrollWheelZoom: false, keyboard: true, zoomControl: false, minZoom: 9, maxBounds: province.pad(0.1), maxBoundsViscosity: 0.8, zoomSnap: 0.5 })
    .setView(center, Math.max(9, Number(el.dataset.zoom || 10)));
  L.tileLayer(el.dataset.tiles, { maxZoom: 18, minZoom: 9, attribution: el.dataset.attribution }).addTo(map);
  // Pulsanti + e − con nomi tradotti (Leaflet li chiamerebbe "Zoom in"/"Zoom out")
  L.control.zoom({ position: 'topleft', zoomInTitle: el.dataset.msgZoomIn, zoomOutTitle: el.dataset.msgZoomOut }).addTo(map);

  var svgIcon = function (name, cls) {
    var ns = 'http://www.w3.org/2000/svg';
    var svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('class', 'icon' + (cls ? ' ' + cls : ''));
    svg.setAttribute('aria-hidden', 'true');
    svg.setAttribute('focusable', 'false');
    var use = document.createElementNS(ns, 'use');
    use.setAttribute('href', sprite + '#i-' + (iconMap[name] || name));
    svg.appendChild(use);
    return svg;
  };
  var node = function (tag, text, attrs) {
    var n = document.createElement(tag);
    if (text) { n.textContent = text; }
    Object.keys(attrs || {}).forEach(function (k) { n.setAttribute(k, attrs[k]); });
    return n;
  };
  var announce = function (text) {
    if (!announcer) { return; }
    announcer.textContent = '';
    window.setTimeout(function () { announcer.textContent = text; }, 60);
  };

  // --- Controlli di zoom e schermo intero (D-039) ----------------------------------------------------
  // Rotella: zooma dopo un clic o quando la mappa ha il focus; con Ctrl (o Cmd) zooma sempre.
  // Senza Ctrl la pagina continua a scorrere e compare un suggerimento.
  var canvas = el.closest('.map-app__canvas') || el.parentNode;
  var hint = node('p', el.dataset.msgWheelHint, { 'class': 'map-hint', 'aria-hidden': 'true' });
  canvas.appendChild(hint);
  var hintTimer = null;
  var showHint = function () {
    hint.classList.add('is-visible');
    clearTimeout(hintTimer);
    hintTimer = setTimeout(function () { hint.classList.remove('is-visible'); }, 1600);
  };
  map.on('click', function () { map.scrollWheelZoom.enable(); });
  map.on('mouseout', function () { if (!canvas.classList.contains('is-fullscreen')) { map.scrollWheelZoom.disable(); } });
  el.addEventListener('focus', function () { map.scrollWheelZoom.enable(); });
  el.addEventListener('blur', function () { if (!canvas.classList.contains('is-fullscreen')) { map.scrollWheelZoom.disable(); } });
  el.addEventListener('wheel', function (e) {
    if (map.scrollWheelZoom.enabled()) { return; }
    if (e.ctrlKey || e.metaKey) {
      e.preventDefault();
      map.setZoom(map.getZoom() + (e.deltaY < 0 ? 1 : -1));
    } else {
      showHint();
    }
  }, { passive: false });

  var button = function (icon, label, onClick) {
    var b = node('button', null, { type: 'button', 'class': 'map-ctrl', title: label, 'aria-label': label });
    b.appendChild(svgIcon(icon));
    L.DomEvent.on(b, 'click', function (e) { L.DomEvent.stop(e); onClick(b); });
    return b;
  };
  var setFullscreen = function (on, b) {
    canvas.classList.toggle('is-fullscreen', on);
    document.documentElement.classList.toggle('has-map-fullscreen', on);
    // Il resto della pagina, coperto dalla mappa, esce dal percorso del focus (WCAG 2.4.11)
    document.querySelectorAll('.skip-link, .site-header, .site-footer, .map-app__intro, .map-app__results, .map-app > .skip-link--inline').forEach(function (n) {
      if (on) { n.setAttribute('inert', ''); } else { n.removeAttribute('inert'); }
    });
    b.setAttribute('aria-pressed', on ? 'true' : 'false');
    var label = on ? el.dataset.msgFullscreenExit : el.dataset.msgFullscreen;
    b.setAttribute('aria-label', label);
    b.title = label;
    b.querySelector('use').setAttribute('href', sprite + '#i-' + (on ? 'minimize' : 'maximize'));
    if (on) { map.scrollWheelZoom.enable(); } else { map.scrollWheelZoom.disable(); }
    setTimeout(function () { map.invalidateSize(); }, 50);
    b.focus();
  };
  var Tools = L.Control.extend({
    options: { position: 'topleft' },
    onAdd: function () {
      var box = L.DomUtil.create('div', 'leaflet-bar map-ctrl-group');
      L.DomEvent.disableClickPropagation(box);
      box.appendChild(button('house', el.dataset.msgResetView, function () {
        map.fitBounds(townsLayer ? townsLayer.getBounds() : province, { padding: [12, 12] });
      }));
      var full = button('maximize', el.dataset.msgFullscreen, function (b) { setFullscreen(!canvas.classList.contains('is-fullscreen'), b); });
      full.setAttribute('aria-pressed', 'false');
      box.appendChild(full);
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && canvas.classList.contains('is-fullscreen')) { setFullscreen(false, full); }
      });
      return box;
    }
  });
  map.addControl(new Tools());

  // --- Livello dei comuni --------------------------------------------------------------------------
  var palette = ['#f1f4fa', '#d6e1fa', '#a9c0f3', '#6f92e6', '#2f5bd3'];
  var townsLayer = null;
  var townById = {};       // id territorio => { layer, name, count, district }
  var idByIstat = {};
  var bins = [0, 1, 3, 6, 10];
  var selectedId = null;

  var binFor = function (count) {
    var b = 0;
    for (var i = 0; i < bins.length; i++) { if (count >= bins[i]) { b = i; } }
    return b;
  };
  // Classi crescenti in modo non lineare: pochi comuni hanno molti servizi, molti ne hanno pochi
  var computeBins = function (counts) {
    var max = Math.max.apply(null, counts.concat([0]));
    if (max <= 4) { bins = [0, 1, 2, 3, 4]; return; }
    var b = [0, 1, Math.ceil(max / 10), Math.ceil(max / 4), Math.ceil(max / 2)];
    for (var i = 2; i < b.length; i++) { if (b[i] <= b[i - 1]) { b[i] = b[i - 1] + 1; } }
    bins = b;
  };
  var isSelected = function (town) {
    if (selectedId === null) { return false; }
    return town.id === selectedId || town.district === selectedId;
  };
  var styleFor = function (town) {
    var selected = isSelected(town);
    return {
      color: selected ? '#142033' : '#ffffff',
      weight: selected ? 3 : 1,
      fillColor: palette[binFor(town.count)],
      fillOpacity: selected ? 0.35 : 0.6,
      opacity: 1
    };
  };
  var restyle = function () {
    Object.keys(townById).forEach(function (id) {
      var town = townById[id];
      if (town.layer) {
        town.layer.setStyle(styleFor(town));
        if (isSelected(town)) { town.layer.bringToFront(); }
      }
    });
  };
  var renderLegend = function () {
    var list = legend && legend.querySelector('[data-map-legend-list]');
    if (!list) { return; }
    list.textContent = '';
    bins.forEach(function (from, i) {
      var to = i + 1 < bins.length ? bins[i + 1] - 1 : null;
      var label = to === null ? fmt.format(from) + '+' : (from === to ? fmt.format(from) : fmt.format(from) + '–' + fmt.format(to));
      var li = node('li');
      var swatch = node('span', null, { 'class': 'map-legend__swatch' });
      swatch.style.background = palette[i];
      li.appendChild(swatch);
      li.appendChild(node('span', label));
      list.appendChild(li);
    });
    legend.hidden = false;
  };
  var tooltipText = function (town) {
    return town.name + ' — ' + (el.dataset.msgTownCount || '{count}').replace('{count}', fmt.format(town.count));
  };

  var loadCounts = function (search) {
    var p = new URLSearchParams(search);
    p.delete('comune');
    return fetch(withLocale(app.dataset.countsUrl, p.toString()), { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : { municipalities: [] }; })
      .then(function (data) {
        var counts = [];
        (data.municipalities || []).forEach(function (m) {
          idByIstat[m.istat] = m.id;
          var town = townById[m.id] || (townById[m.id] = { id: m.id, layer: null });
          town.name = m.name;
          town.count = m.count;
          town.district = m.district.id;
          town.districtName = m.district.name;
          counts.push(m.count);
        });
        computeBins(counts);
        Object.keys(townById).forEach(function (id) {
          var town = townById[id];
          if (town.layer) { town.layer.setTooltipContent(tooltipText(town)); }
        });
        restyle();
        renderLegend();
      });
  };

  var shapesReady = fetch(el.dataset.shapesUrl || app.dataset.shapesUrl, { headers: { Accept: 'application/json' } })
    .then(function (r) { return r.ok ? r.json() : null; });

  var drawTowns = function (geojson) {
    if (!geojson) { return; }
    townsLayer = L.geoJSON(geojson, {
      style: function () { return { color: '#ffffff', weight: 1, fillColor: palette[0], fillOpacity: 0.6 }; },
      onEachFeature: function (feature, layer) {
        var id = idByIstat[feature.properties.istat];
        if (!id) { return; }
        var town = townById[id];
        town.layer = layer;
        layer.bindTooltip(tooltipText(town), { sticky: true, direction: 'top', className: 'town-tooltip', opacity: 1 });
        layer.on('mouseover', function () { if (!isSelected(town)) { layer.setStyle({ weight: 2.5, color: '#1f4fd1' }); } });
        layer.on('mouseout', function () { layer.setStyle(styleFor(town)); });
        layer.on('click', function () { selectTown(town.id === selectedId ? null : town.id, true); });
      }
    }).addTo(map);
    townsLayer.bringToBack();
    restyle();
  };

  var focusSelection = function () {
    if (selectedId === null) {
      if (townsLayer) { map.fitBounds(townsLayer.getBounds(), { padding: [12, 12] }); }
      return;
    }
    var bounds = null;
    Object.keys(townById).forEach(function (id) {
      var town = townById[id];
      if (town.layer && isSelected(town)) {
        bounds = bounds ? bounds.extend(town.layer.getBounds()) : L.latLngBounds(town.layer.getBounds().getSouthWest(), town.layer.getBounds().getNorthEast());
      }
    });
    if (bounds) { map.fitBounds(bounds, { padding: [24, 24], maxZoom: 13 }); }
  };

  // --- Punti (sedi) ----------------------------------------------------------------------------------
  var cluster = typeof L.markerClusterGroup === 'function' ? L.markerClusterGroup({ showCoverageOnHover: false, maxClusterRadius: 45 }) : L.layerGroup();
  map.addLayer(cluster);
  var markersByService = {};
  var serviceUrl = function (id) { return el.dataset.serviceUrl.replace(/\/0$/, '/' + encodeURIComponent(id)); };

  var popup = function (point) {
    var box = node('div', null, { 'class': 'map-popup', dir: el.dataset.dir || 'ltr' });
    box.appendChild(node('strong', point.organization));
    if (point.name && point.name !== point.organization) { box.appendChild(node('div', point.name)); }
    box.appendChild(node('div', point.address, { 'class': 'map-popup__address' }));
    if (point.mediation) {
      var m = node('div', null, { 'class': 'map-popup__row' });
      m.appendChild(svgIcon('messages-square'));
      m.appendChild(node('span', el.dataset.msgMediation));
      box.appendChild(m);
    }
    if (point.step_free) {
      var a = node('div', null, { 'class': 'map-popup__row' });
      a.appendChild(svgIcon('accessibility'));
      a.appendChild(node('span', el.dataset.msgStepFree));
      box.appendChild(a);
    }
    box.appendChild(node('div', el.dataset.msgServices, { 'class': 'map-popup__label' }));
    var list = node('ul');
    point.services.slice(0, 6).forEach(function (s) {
      var li = node('li');
      li.appendChild(node('a', s.name, { href: serviceUrl(s.id) }));
      list.appendChild(li);
    });
    box.appendChild(list);
    var dir = node('a', null, {
      href: 'https://www.openstreetmap.org/directions?route=%3B' + point.lat + '%2C' + point.lng,
      target: '_blank', rel: 'noopener noreferrer', 'class': 'map-popup__directions'
    });
    dir.appendChild(svgIcon('navigation'));
    dir.appendChild(node('span', el.dataset.msgDirections));
    box.appendChild(dir);
    return box;
  };

  var markerIcon = function (point) {
    var wrap = node('span', null, { 'class': 'map-marker' });
    wrap.appendChild(svgIcon(point.icon || 'default'));
    return L.divIcon({ html: wrap, className: 'map-marker-icon', iconSize: [36, 36], iconAnchor: [18, 34], popupAnchor: [0, -30] });
  };

  var pointsRequest = 0;
  var loadPoints = function (search, fit) {
    var ticket = ++pointsRequest;
    return fetch(withLocale(app.dataset.pointsUrl, search), { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : { points: [] }; })
      .then(function (data) {
        if (ticket !== pointsRequest) { return; }
        cluster.clearLayers();
        markersByService = {};
        var bounds = [];
        (data.points || []).forEach(function (point) {
          var marker = L.marker([point.lat, point.lng], { icon: markerIcon(point), title: point.organization, keyboard: true, riseOnHover: true });
          marker.on('add', function () {
            var icon = marker.getElement();
            if (icon) {
              icon.setAttribute('role', 'button');
              icon.setAttribute('aria-label', point.organization + ', ' + point.address);
            }
          });
          marker.bindPopup(function () { return popup(point); });
          cluster.addLayer(marker);
          bounds.push([point.lat, point.lng]);
          point.services.forEach(function (s) { (markersByService[s.id] = markersByService[s.id] || []).push(marker); });
        });
        if (fit && selectedId === null && bounds.length > 1) {
          map.fitBounds(bounds, { padding: [24, 24], maxZoom: 13 });
        }
      })
      .catch(function () { /* la mappa resta senza punti: l'elenco è comunque disponibile */ });
  };

  // Dall'elenco alla mappa: il servizio con il focus apre il suo punto
  results.addEventListener('focusin', function (event) {
    var item = event.target.closest('[data-service-id]');
    if (!item) { return; }
    var markers = markersByService[item.dataset.serviceId];
    if (markers && markers.length && typeof cluster.zoomToShowLayer === 'function') {
      cluster.zoomToShowLayer(markers[0], function () { markers[0].openPopup(); });
    }
  });

  // --- Aggiornamento dei risultati senza ricaricare --------------------------------------------------
  var pageRequest = 0;
  var refresh = function (search, options) {
    options = options || {};
    var url = app.dataset.mapUrl + (search ? '?' + search : '');
    if (options.push) { window.history.pushState({ map: true }, '', url); }
    var ticket = ++pageRequest;
    results.setAttribute('aria-busy', 'true');
    announce(el.dataset.msgLoading);

    var p = new URLSearchParams(search);
    selectedId = p.get('comune') ? Number(p.get('comune')) : null;
    if (townSelect) { townSelect.value = p.get('comune') || ''; }
    restyle();
    if (options.focusMap) { focusSelection(); }

    var countsDone = options.counts ? loadCounts(search) : Promise.resolve();
    loadPoints(search, false);
    return fetch(url, { headers: { Accept: 'text/html', 'X-Requested-With': 'fetch' } })
      .then(function (r) { if (!r.ok) { throw new Error(String(r.status)); } return r.text(); })
      .then(function (html) {
        if (ticket !== pageRequest) { return; }
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var fresh = doc.querySelector('[data-map-results]');
        if (!fresh) { throw new Error('fragment'); }
        results.innerHTML = fresh.innerHTML;
        var freshFilters = doc.querySelector('[data-catalog-filters]');
        if (freshFilters && filtersForm) {
          filtersForm.innerHTML = freshFilters.innerHTML;
          var summary = filtersForm.closest('details') && filtersForm.closest('details').querySelector('summary');
          var freshSummary = freshFilters.closest('details') && freshFilters.closest('details').querySelector('summary');
          if (summary && freshSummary) { summary.innerHTML = freshSummary.innerHTML; }
        }
        document.title = doc.title;
        results.removeAttribute('aria-busy');
        var count = results.querySelector('.results-count');
        var town = selectedId !== null && townSelect ? townSelect.options[townSelect.selectedIndex].text : '';
        announce((el.dataset.msgUpdated || '{results}').replace('{results}', count ? count.textContent.trim() : '').replace('{town}', town));
        document.dispatchEvent(new CustomEvent('catalog:updated'));
        return countsDone;
      })
      .catch(function () {
        // In caso di errore si ricarica la pagina normalmente: il risultato è lo stesso
        window.location.href = url;
      });
  };

  var selectTown = function (id, fromMap) {
    var p = params();
    if (id === null || id === '') { p.delete('comune'); } else { p.set('comune', String(id)); }
    p.delete('page');
    refresh(p.toString(), { push: true, focusMap: fromMap !== 'keep' });
  };

  if (townSelect) {
    townSelect.addEventListener('change', function () { selectTown(townSelect.value || null, true); });
    townSelect.form.addEventListener('submit', function (e) { e.preventDefault(); selectTown(townSelect.value || null, true); });
  }

  // Link "allarga al distretto" / "togli il comune" dentro i risultati
  results.addEventListener('click', function (e) {
    var link = e.target.closest('[data-town-link]');
    if (!link) { return; }
    e.preventDefault();
    selectTown(link.dataset.townLink || null, true);
    if (townSelect) { townSelect.focus(); }
  });

  // Filtri: il modulo si applica senza ricaricare; il comune scelto resta
  if (filtersForm) {
    filtersForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var p = new URLSearchParams(new FormData(filtersForm));
      var current = params();
      if (current.get('comune')) { p.set('comune', current.get('comune')); }
      Array.from(p.keys()).forEach(function (k) { if (p.get(k) === '') { p.delete(k); } });
      refresh(p.toString(), { push: true, counts: true });
    });
  }

  // Ricerca testuale: mantiene comune e filtri correnti (i campi nascosti del modulo possono essere superati)
  var searchForm = app.querySelector('form[role="search"]');
  if (searchForm) {
    searchForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var p = params();
      var q = (searchForm.querySelector('input[name="q"]') || {}).value || '';
      if (q.trim() === '') { p.delete('q'); } else { p.set('q', q.trim()); }
      p.delete('page');
      refresh(p.toString(), { push: true, counts: true });
    });
  }

  window.addEventListener('popstate', function () {
    refresh(window.location.search.replace(/^\?/, ''), { counts: true, focusMap: true });
  });

  // Livelli visibili
  if (toolbar) {
    toolbar.hidden = false;
    toolbar.querySelector('[data-toggle-towns]').addEventListener('change', function (e) {
      if (!townsLayer) { return; }
      if (e.target.checked) { townsLayer.addTo(map); townsLayer.bringToBack(); legend.hidden = false; } else { map.removeLayer(townsLayer); legend.hidden = true; }
    });
    toolbar.querySelector('[data-toggle-points]').addEventListener('change', function (e) {
      if (e.target.checked) { map.addLayer(cluster); } else { map.removeLayer(cluster); }
    });
  }

  // Posizione scelta in near-me.js: segnaposto "sei qui" (solo nel browser)
  var you = null;
  document.addEventListener('nearme:position', function (event) {
    var pos = [event.detail.lat, event.detail.lng];
    if (you) { map.removeLayer(you); }
    you = L.circleMarker(pos, { radius: 9, weight: 3, color: '#142033', fillColor: '#ffbf47', fillOpacity: 1 }).addTo(map).bindTooltip(el.dataset.msgYou);
    map.setView(pos, 12);
  });

  // --- Avvio ---------------------------------------------------------------------------------------
  var initial = params();
  selectedId = initial.get('comune') ? Number(initial.get('comune')) : null;
  window.history.replaceState({ map: true }, '', window.location.href);
  loadCounts(initial.toString())
    .then(function () { return shapesReady; })
    .then(function (geojson) {
      drawTowns(geojson);
      focusSelection();
    })
    .catch(function () { /* senza confini: restano i punti e l'elenco */ });
  loadPoints(initial.toString(), false);
})();
