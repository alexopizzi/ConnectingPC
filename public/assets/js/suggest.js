/*
 * Suggerimenti di ricerca (vault "62"): elenco di link sotto il campo, raggiungibile con Tab;
 * il numero di suggerimenti è annunciato agli screen reader. Senza JavaScript il modulo funziona normalmente.
 */
(function () {
  'use strict';

  document.querySelectorAll('form[data-suggest-url]').forEach(function (form) {
    var input = form.querySelector('input[name="q"]');
    if (!input) { return; }
    var box = document.createElement('div');
    box.className = 'suggestions';
    var list = document.createElement('ul');
    var live = document.createElement('p');
    live.className = 'visually-hidden';
    live.setAttribute('role', 'status');
    live.setAttribute('aria-live', 'polite');
    box.appendChild(list);
    form.appendChild(box);
    form.appendChild(live);

    var timer = null;
    var controller = null;
    var render = function (items) {
      list.textContent = '';
      items.forEach(function (item) {
        var li = document.createElement('li');
        var a = document.createElement('a');
        a.textContent = (item.type === 'need' ? '➜ ' : '') + item.label;
        a.href = item.type === 'need'
          ? form.dataset.needUrl.replace('__code__', encodeURIComponent(item.code))
          : form.dataset.serviceUrl.replace(/\/0$/, '/' + encodeURIComponent(item.id));
        li.appendChild(a);
        list.appendChild(li);
      });
      box.hidden = items.length === 0;
      live.textContent = items.length ? (form.dataset.msgCount || '{count}').replace('{count}', String(items.length)) : '';
    };

    input.addEventListener('input', function () {
      clearTimeout(timer);
      var q = input.value.trim();
      if (q.length < 3) { render([]); return; }
      timer = setTimeout(function () {
        if (controller) { controller.abort(); }
        controller = new AbortController();
        var url = form.dataset.suggestUrl + (form.dataset.suggestUrl.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(q);
        fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } })
          .then(function (r) { return r.ok ? r.json() : { suggestions: [] }; })
          .then(function (data) { render(data.suggestions || []); })
          .catch(function () { /* ignora: la ricerca normale funziona comunque */ });
      }, 250);
    });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { render([]); }
    });
    render([]);
  });
})();
