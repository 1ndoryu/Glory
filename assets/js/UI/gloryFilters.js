(function(){
  function ajaxPost(action, data){
    if (typeof gloryAjax === 'function') {
      return gloryAjax(action, data);
    }
    const ajaxUrl = (typeof ajax_params !== 'undefined' && ajax_params.ajax_url)
      ? ajax_params.ajax_url
      : '/wp-admin/admin-ajax.php';
    return new Promise(function(resolve){
      if (typeof jQuery !== 'undefined') {
        jQuery.post(ajaxUrl, { action, ...data }).done(function(resp){ resolve(resp); });
      } else {
        fetch(ajaxUrl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action, ...data }) })
          .then(r => r.text()).then(t => { try { resolve(JSON.parse(t)); } catch(_) { resolve({ success: true, data: { html: t } }); } });
      }
    });
  }

  function serializeForm(form){
    const data = {};
    if (typeof jQuery !== 'undefined') {
      const $form = jQuery(form);
      $form.serializeArray().forEach(function(it){ data[it.name] = it.value; });
      $form.find('input[type="hidden"]').each(function(){
        const name = jQuery(this).attr('name');
        if (name && !(name in data)) { data[name] = jQuery(this).val(); }
      });
    } else {
      const fd = new FormData(form);
      for (const [k, v] of fd.entries()) { data[k] = v; }
    }
    return data;
  }

  function resolveTarget(form){
    const explicit = form.getAttribute('data-target') || '';
    if (explicit) {
      const t = document.querySelector(explicit);
      if (t) return t;
    }
    let scope = form.closest('.pestanaContenido') || form.closest('.wrap') || document.body;
    let grid = scope.querySelector('.gloryDataGridContenedor') || document.querySelector('.gloryDataGridContenedor');
    return grid ? (grid.parentElement || scope) : scope;
  }

  function replaceGridHtml(scopeOrEl, html){
    const temp = document.createElement('div');
    temp.innerHTML = html;
    const nuevo = temp.querySelector('.gloryDataGridContenedor');
    const target = (scopeOrEl.classList && scopeOrEl.classList.contains('gloryDataGridContenedor')) ? scopeOrEl.parentElement : scopeOrEl;
    if (nuevo) {
      const actual = target.querySelector('.gloryDataGridContenedor');
      if (actual) { actual.replaceWith(nuevo); }
      else { target.appendChild(nuevo); }
    } else {
      target.innerHTML = html;
    }
  }

  function findAjaxFilterFormFrom(el){
    const scope = el.closest('.pestanaContenido') || el.closest('.wrap') || document;
    return scope.querySelector('form[data-glory-filters="ajax"]');
  }

  function onSubmit(e){
    const form = e.target.closest('form[data-glory-filters="ajax"]');
    if (!form) return;
    e.preventDefault();
    const action = form.getAttribute('data-ajax-action') || '';
    if (!action) return;
    const data = serializeForm(form);
    const target = resolveTarget(form);
    ajaxPost(action, data).then(function(resp){
      if (resp && resp.success && resp.data && resp.data.html){
        replaceGridHtml(target, resp.data.html);
        document.dispatchEvent(new CustomEvent('gloryRecarga', {bubbles: true, cancelable: true}));
      }
    });
  }

  function onClick(e){
    // Ordenamiento
    const orderLink = e.target.closest('.gloryDataGridContenedor th.columnaOrdenable a');
    if (orderLink) {
      const form = findAjaxFilterFormFrom(orderLink);
      if (!form) return;
      e.preventDefault();
      const url = new URL(orderLink.href, window.location.origin);
      const orderby = url.searchParams.get('orderby') || '';
      const order = url.searchParams.get('order') || '';
      const action = form.getAttribute('data-ajax-action') || '';
      if (!action) return;
      const data = serializeForm(form);
      if (orderby) data.orderby = orderby;
      if (order) data.order = order;
      const target = resolveTarget(form);
      ajaxPost(action, data).then(function(resp){
        if (resp && resp.success && resp.data && resp.data.html){
          replaceGridHtml(target, resp.data.html);
          document.dispatchEvent(new CustomEvent('gloryRecarga', {bubbles: true, cancelable: true}));
        }
      });
      return;
    }

    // Paginación
    const pageLink = e.target.closest('.gloryDataGridContenedor .gloryPaginacion a[data-page]');
    if (pageLink) {
      const form = findAjaxFilterFormFrom(pageLink);
      if (!form) return;
      e.preventDefault();
      const page = parseInt(pageLink.getAttribute('data-page') || '1', 10) || 1;
      const action = form.getAttribute('data-ajax-action') || '';
      if (!action) return;
      const data = serializeForm(form);
      data.paged = page;
      const target = resolveTarget(form);
      ajaxPost(action, data).then(function(resp){
        if (resp && resp.success && resp.data && resp.data.html){
          replaceGridHtml(target, resp.data.html);
          document.dispatchEvent(new CustomEvent('gloryRecarga', {bubbles: true, cancelable: true}));
        }
      });
    }

    // Acciones masivas (Aplicar)
    const bulkBtn = e.target.closest('.gloryDataGridAccionesMasivas .gloryGridBulkApply');
    if (bulkBtn) {
      const contenedor = bulkBtn.closest('.gloryDataGridContenedor');
      const form = findAjaxFilterFormFrom(bulkBtn);
      if (!contenedor || !form) return;
      e.preventDefault();
      const select = contenedor.querySelector('.gloryGridBulkSelect');
      if (!select) return;
      const accionId = select.value;
      const ajaxAction = select.options[select.selectedIndex]?.getAttribute('data-ajax-action') || '';
      const confirmMsg = select.options[select.selectedIndex]?.getAttribute('data-confirm') || '';
      if (!accionId || !ajaxAction) return;
      if (confirmMsg && !window.confirm(confirmMsg)) return;
      const checks = contenedor.querySelectorAll('input.gloryGridSelect:checked');
      const ids = Array.from(checks).map(ch => ch.value).filter(Boolean);
      if (ids.length === 0) return;
      const data = serializeForm(form);
      data.ids = ids.join(',');
      const target = resolveTarget(form);
      ajaxPost(ajaxAction, data).then(function(resp){
        if (resp && resp.success && resp.data && resp.data.html){
          replaceGridHtml(target, resp.data.html);
          document.dispatchEvent(new CustomEvent('gloryRecarga', {bubbles: true, cancelable: true}));
        }
      });
      return;
    }

    // Seleccionar todo
    const selectAll = e.target.closest('.gloryGridSelectAll');
    if (selectAll) {
      const contenedor = selectAll.closest('.gloryDataGridContenedor');
      if (!contenedor) return;
      const checks = contenedor.querySelectorAll('input.gloryGridSelect');
      checks.forEach(ch => { ch.checked = selectAll.checked; });
      return;
    }
  }

  window.gloryFiltersInit = function gloryFiltersInit(){
    if (window.gloryFiltersInitialized) return;
    window.gloryFiltersInitialized = true;
    document.addEventListener('submit', onSubmit, true);
    document.addEventListener('click', onClick, true);
  };

  // Inicialización en carga y reinicialización en gloryRecarga
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){ window.gloryFiltersInit(); });
  } else {
    window.gloryFiltersInit();
  }
  document.addEventListener('gloryRecarga', function(){ window.gloryFiltersInitialized = false; window.gloryFiltersInit(); });
})();


