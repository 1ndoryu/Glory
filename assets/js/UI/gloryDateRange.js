(function(){
  function openPopover(input){
    closeAnyPopover();
    const rect = input.getBoundingClientRect();
    const pop = document.createElement('div');
    pop.className = 'gloryDateRangePopover';
    var isAdmin = (window.location && window.location.pathname && window.location.pathname.indexOf('/wp-admin/') !== -1);
    var actionsHtml = isAdmin
      ? '<button type="button" class="button gdr-apply">Aplicar</button><button type="button" class="button gdr-clear">Limpiar</button>'
      : '<button type="button" class="button gdr-clear">Restablecer</button>';
    pop.innerHTML = [
      '<div class="gdr-head">Rango de fechas</div>',
      '<div class="gdr-body">',
      '  <label>Desde</label><input type="date" class="gdr-from">',
      '  <label>Hasta</label><input type="date" class="gdr-to">',
      '</div>',
      '<div class="gdr-actions">',
      actionsHtml,
      '</div>'
    ].join('');
    document.body.appendChild(pop);

    // Position
    const top = window.scrollY + rect.bottom + 6;
    const left = window.scrollX + rect.left;
    pop.style.top = top + 'px';
    pop.style.left = left + 'px';

    // Prefill from hidden inputs
    const fromName = input.getAttribute('data-from-name') || '';
    const toName   = input.getAttribute('data-to-name') || '';
    const form     = input.closest('form');
    const fromH    = form ? form.querySelector('input[name="' + fromName + '"]') : null;
    const toH      = form ? form.querySelector('input[name="' + toName + '"]') : null;
    const fromI    = pop.querySelector('.gdr-from');
    const toI      = pop.querySelector('.gdr-to');
    if (fromH && fromH.value) fromI.value = fromH.value;
    if (toH && toH.value) toI.value = toH.value;

    function apply(){
      const fromVal = fromI.value || '';
      const toVal   = toI.value || '';
      if (fromH) fromH.value = fromVal;
      if (toH) toH.value = toVal;
      input.value = (fromVal || toVal) ? (fromVal + ' — ' + toVal) : '';
      // En admin: no auto-AJAX; en front: ya no hay botón aplicar
      if (!isAdmin) {
        if (form) form.dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
      }
      closePopover();
    }
    function clear(){
      if (fromH) fromH.value = '';
      if (toH) toH.value = '';
      input.value = '';
      if (form) form.dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
      closePopover();
    }

    var applyBtn = pop.querySelector('.gdr-apply');
    if (applyBtn) applyBtn.addEventListener('click', apply);
    pop.querySelector('.gdr-clear').addEventListener('click', clear);

    // En front: aplicar automáticamente al cambiar fechas
    function onDateChange(){
      const fromVal = fromI.value || '';
      const toVal   = toI.value || '';
      if (fromH) fromH.value = fromVal;
      if (toH) toH.value = toVal;
      input.value = (fromVal || toVal) ? (fromVal + ' — ' + toVal) : '';
      if (!isAdmin && form) {
        form.dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
        // Si ambos valores están establecidos, cerramos el popover
        if (fromVal && toVal) closePopover();
      }
    }
    fromI.addEventListener('change', onDateChange);
    toI.addEventListener('change', onDateChange);

    // Cerrar al clic fuera
    setTimeout(function(){
      document.addEventListener('mousedown', onDocDown, true);
      document.addEventListener('scroll', closePopover, true);
      window.addEventListener('resize', closePopover, true);
    }, 0);

    function onDocDown(e){
      if (pop.contains(e.target) || e.target === input) return;
      closePopover();
    }

    function closePopover(){
      if (pop && pop.parentNode) pop.parentNode.removeChild(pop);
      document.removeEventListener('mousedown', onDocDown, true);
      document.removeEventListener('scroll', closePopover, true);
      window.removeEventListener('resize', closePopover, true);
    }

    // Guardar referencia para un único popover
    window.__gloryDateRangeClose__ = closePopover;
  }

  function closeAnyPopover(){
    if (typeof window.__gloryDateRangeClose__ === 'function') {
      window.__gloryDateRangeClose__();
      window.__gloryDateRangeClose__ = null;
    }
  }

  function onFocusOrClick(e){
    const input = e.target.closest('.gloryDateRangeInput');
    if (!input) return;
    e.preventDefault();
    openPopover(input);
  }

  window.gloryDateRangeInit = function gloryDateRangeInit(){
    if (window.__gloryDateRangeInitialized__) return;
    window.__gloryDateRangeInitialized__ = true;
    document.addEventListener('focus', onFocusOrClick, true);
    document.addEventListener('click', onFocusOrClick, true);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){ window.gloryDateRangeInit(); });
  } else {
    window.gloryDateRangeInit();
  }
  document.addEventListener('gloryRecarga', function(){ window.__gloryDateRangeInitialized__ = false; window.gloryDateRangeInit(); });
})();


