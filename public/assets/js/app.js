/*
 * Comportamenti di base dell'interfaccia pubblica (D-038). Caricato nell'head senza defer:
 * imposta subito la classe "js" (così il menu su mobile non lampeggia) e attende il DOM per il resto.
 * Senza JavaScript tutto resta utilizzabile: menu sempre visibile, filtri e selettore lingua con <details>.
 */
(function () {
  'use strict';

  document.documentElement.classList.add('js');

  var ready = function (fn) {
    if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); }
  };

  ready(function () {
    // Menu principale su schermi piccoli: pulsante con aria-expanded, Esc chiude e riporta il focus al pulsante
    var nav = document.querySelector('[data-nav]');
    var toggle = nav && nav.querySelector('[data-nav-toggle]');
    if (nav && toggle) {
      var setOpen = function (open) {
        nav.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      };
      toggle.addEventListener('click', function () { setOpen(toggle.getAttribute('aria-expanded') !== 'true'); });
      nav.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && nav.classList.contains('is-open')) { setOpen(false); toggle.focus(); }
      });
      document.addEventListener('click', function (e) {
        if (nav.classList.contains('is-open') && !nav.contains(e.target)) { setOpen(false); }
      });
    }

    // Menu a tendina basati su <details> (selettore lingua): Esc e clic fuori li chiudono
    document.querySelectorAll('details[data-dropdown]').forEach(function (details) {
      details.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && details.open) {
          details.open = false;
          details.querySelector('summary').focus();
        }
      });
      document.addEventListener('click', function (e) {
        if (details.open && !details.contains(e.target)) { details.open = false; }
      });
    });

    // Pannello dei filtri: aperto di default sugli schermi larghi (dove sta nella colonna laterale)
    var wide = window.matchMedia('(min-width: 64rem)');
    document.querySelectorAll('details[data-filters-panel]').forEach(function (panel) {
      if (wide.matches) { panel.open = true; }
    });
  });
})();
