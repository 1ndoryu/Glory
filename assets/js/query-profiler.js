/* global jQuery, window, document */
(function ($) {
  'use strict';

  var data = window.GloryQueryProfilerData || null;
  if (!data || !data.enabled) return;

  function detectFusionBuilderFallback() {
    try {
      if (window.location && typeof window.location.search === 'string' && window.location.search.indexOf('fb-edit') !== -1) {
        return true;
      }
    } catch (e) {}

    try {
      if (window.self !== window.top) {
        if (window.top.location && typeof window.top.location.search === 'string' && window.top.location.search.indexOf('fb-edit') !== -1) {
          return true;
        }
        if (window.parent && window.parent.document && window.parent.document.querySelector('.fusion-builder-live-toolbar')) {
          return true;
        }
      }
    } catch (e) {}

    try {
      if (document.querySelector('.fusion-builder-live-toolbar')) {
        return true;
      }
    } catch (e) {}

    return false;
  }

  var fusionBuilderActive = false;
  if (typeof window.isFusionBuilderActive === 'function') {
    fusionBuilderActive = window.isFusionBuilderActive();
  } else if (typeof window.FUSION_BUILDER_ACTIVE !== 'undefined') {
    fusionBuilderActive = !!window.FUSION_BUILDER_ACTIVE;
  } else {
    fusionBuilderActive = detectFusionBuilderFallback();
  }

  if (fusionBuilderActive) {
    return;
  }

  function createPanel() {
    if (document.getElementById('glory-query-profiler')) return;

    var container = document.createElement('div');
    container.id = 'glory-query-profiler';

    var button = document.createElement('button');
    button.className = 'gqp-toggle';
    button.type = 'button';
    button.setAttribute('aria-expanded', 'false');
    var totalTime = typeof data.total_time === 'number' ? data.total_time : parseFloat(data.total_time || 0);
    if (!totalTime || totalTime === 0) {
      totalTime = typeof data.wp_time === 'number' ? data.wp_time : parseFloat(data.wp_time || 0);
    }
    button.textContent = 'SQL ' + data.total + ' • ' + (totalTime || 0).toFixed(3) + 's';

    var panel = document.createElement('div');
    panel.className = 'gqp-panel';
    panel.setAttribute('hidden', 'hidden');

    var header = document.createElement('div');
    header.className = 'gqp-header';
    header.textContent = 'Consultas (ordenadas por duración)';

    var list = document.createElement('ol');
    list.className = 'gqp-list';

    (data.queries || []).forEach(function (q) {
      var li = document.createElement('li');
      li.className = 'gqp-item';

      var time = document.createElement('div');
      time.className = 'gqp-time';
      time.textContent = q.time.toFixed(6) + 's';

      var sql = document.createElement('pre');
      sql.className = 'gqp-sql';
      sql.textContent = q.sql;

      var caller = document.createElement('div');
      caller.className = 'gqp-caller';
      caller.textContent = q.caller || '';

      li.appendChild(time);
      li.appendChild(sql);
      li.appendChild(caller);
      list.appendChild(li);
    });

    panel.appendChild(header);
    panel.appendChild(list);

    container.appendChild(button);
    container.appendChild(panel);
    document.body.appendChild(container);

    button.addEventListener('click', function () {
      var expanded = button.getAttribute('aria-expanded') === 'true';
      button.setAttribute('aria-expanded', String(!expanded));
      if (expanded) {
        panel.setAttribute('hidden', 'hidden');
      } else {
        panel.removeAttribute('hidden');
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', createPanel);
  } else {
    createPanel();
  }
})(jQuery);


