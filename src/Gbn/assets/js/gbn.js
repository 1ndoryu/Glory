(function(){
  var log = function(){ try{ (console.debug||console.log).apply(console, ['[GBN]'].concat([].slice.call(arguments))); }catch(e){} };
  if (!document.body) { log('document.body no disponible, abortando'); return; }
  log('Iniciando gbn.js');

  function isBuilderActive(){
    try{
      if (location.search.indexOf('fb-edit') !== -1) { log('Detectado fb-edit en URL'); return true; }
      if (window.FusionApp || window.FusionPageBuilder || window.FusionPageBuilderApp) { log('Detectado objeto Fusion/Builder en ventana'); return true; }
      var inIframe = window.self !== window.top;
      if (inIframe && window.top && window.top.document){
        if (window.top.location.search.indexOf('fb-edit') !== -1) { log('Detectado fb-edit en parent'); return true; }
        if (window.top.document.querySelector('.fusion-builder-live-toolbar')) { log('Detectada toolbar del builder en parent'); return true; }
      }
    }catch(e){}
    return false;
  }

  if (isBuilderActive()) { log('Builder activo, GBN no se inicia'); return; }

  var cfg = (window.gloryGbnCfg||{});
  if (!cfg.ajaxUrl) { log('Config no encontrada o ajaxUrl vacío', cfg); }
  var footer = document.getElementById('glory-gbn-root');
  if (!footer) { log('No se encontró #glory-gbn-root en el DOM'); } else { log('#glory-gbn-root presente', footer.dataset); }

  function qsAll(sel, root){ return Array.prototype.slice.call((root||document).querySelectorAll(sel)); }

  var currentRoot = null;
  var pendingValues = {};
  var dirty = false;

  function ensurePanel(){
    var panel = document.getElementById('gbn-panel');
    if (!panel){
      panel = document.createElement('div');
      panel.id = 'gbn-panel';
      panel.innerHTML = '<div class="gbn-header"><span>Glory Split Content</span><button type="button" id="gbn-close"><svg data-testid="geist-icon" height="16" stroke-linejoin="round" style="color:currentColor" viewBox="0 0 16 16" width="16"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.4697 13.5303L13 14.0607L14.0607 13L13.5303 12.4697L9.06065 7.99999L13.5303 3.53032L14.0607 2.99999L13 1.93933L12.4697 2.46966L7.99999 6.93933L3.53032 2.46966L2.99999 1.93933L1.93933 2.99999L2.46966 3.53032L6.93933 7.99999L2.46966 12.4697L1.93933 13L2.99999 14.0607L3.53032 13.5303L7.99999 9.06065L12.4697 13.5303Z" fill="currentColor"></path></svg></button></div><div class="gbn-body"></div><div class="gbn-footer"><button type="button" id="gbn-save">Save</button></div>';
      document.body.appendChild(panel);
      panel.querySelector('#gbn-close').addEventListener('click', function(){
        panel.classList.remove('is-open');
        try {
          if (currentRoot) {
            currentRoot.classList.remove('gbn-open');
            updateDragState(currentRoot, false);
          }
        } catch(e){}
      });
      var saveBtn = panel.querySelector('#gbn-save');
      if (saveBtn){
        saveBtn.addEventListener('click', function(){ try { saveOptions(false); } catch(e){} });
      }
      log('Panel GBN creado');
      return panel;
    }
    return panel;
  }

  function openPanelFor(root){
    currentRoot = root;
    pendingValues = {};
    dirty = false;
    var panel = ensurePanel();
    var body = panel.querySelector('.gbn-body');
    var schema = root.getAttribute('data-gbn-schema');
    var config = root.getAttribute('data-gbn-config');
    var gbnId = root.getAttribute('data-gbn-id');
    var pageId = root.getAttribute('data-gbn-page-id');
    try{ schema = JSON.parse(schema||'[]'); }catch(e){ log('Error parseando schema', e); schema = []; }
    try{ config = JSON.parse(config||'{}'); }catch(e){ log('Error parseando config', e); config = {}; }
    body.innerHTML = '';
    panel.dataset.gbnId = gbnId || '';
    panel.dataset.pageId = pageId || '';
    log('Abriendo panel para', { gbnId: gbnId, pageId: pageId, schemaLen: schema.length });

    if (!schema.length || !schema[0].tab) {
      // Schema antiguo (sin tabs), renderizar plano
      schema.forEach(function(ctrl){
        var wrap = document.createElement('div');
        wrap.className = 'gbn-control';
        var label = document.createElement('label');
        label.textContent = ctrl.label || ctrl.key;
        wrap.appendChild(label);
        var input = createInput(ctrl, config);
        wrap.appendChild(input);
        body.appendChild(wrap);
      });
    } else {
      // Nuevo schema con tabs
      var tabsNav = document.createElement('div');
      tabsNav.className = 'gbn-tabs-nav';
      var activeTab = 0;
      schema.forEach(function(tab, idx){
        var tabBtn = document.createElement('button');
        tabBtn.className = 'gbn-tab-btn' + (idx === 0 ? ' active' : '');
        tabBtn.textContent = tab.tab;
        tabBtn.addEventListener('click', function(){
          qsAll('.gbn-tab-btn', tabsNav).forEach(function(b){ b.classList.remove('active'); });
          this.classList.add('active');
          renderTab(idx);
        });
        tabsNav.appendChild(tabBtn);
      });
      body.appendChild(tabsNav);

      var tabContent = document.createElement('div');
      tabContent.className = 'gbn-tabs-content';
      body.appendChild(tabContent);

      function renderTab(idx){
        tabContent.innerHTML = '';
        var tab = schema[idx];
        if (tab && tab.controls) {
          tab.controls.forEach(function(ctrl){
            var wrap = document.createElement('div');
            wrap.className = 'gbn-control';
            var label = document.createElement('label');
            label.textContent = ctrl.label || ctrl.key;
            wrap.appendChild(label);
            var input = createInput(ctrl, config);
            wrap.appendChild(input);
            tabContent.appendChild(wrap);
          });
        }
      }
      renderTab(activeTab);
    }

    panel.classList.add('is-open');
    // Marcar root como abierto (habilita drag y cursor)
    try { root.classList.add('gbn-open'); updateDragState(root, true); } catch(e){}
  }

  function createInput(ctrl, config){
    var input;
    if (ctrl.type === 'range'){
      // Contenedor para el range con valor (inspirado en FormBuilder)
      var container = document.createElement('div');
      container.className = 'range-container';

      var valueSpan = document.createElement('span');
      valueSpan.className = 'range-value';
      valueSpan.id = 'range-value-' + ctrl.key;

      var rangeInput = document.createElement('input');
      rangeInput.type = 'range';
      rangeInput.min = String(ctrl.min||0);
      rangeInput.max = String(ctrl.max||100);
      rangeInput.step = String(ctrl.step||1);
      var val = String(config[ctrl.key] || '').replace('%','').replace('px','');
      rangeInput.value = val || String(ctrl.min||0);

      // Actualizar valor en tiempo real como en FormBuilder
      valueSpan.textContent = rangeInput.value + (ctrl.unit || '');
      rangeInput.addEventListener('input', function(){
        valueSpan.textContent = this.value + (ctrl.unit || '');
        pendingValues[ctrl.key] = this.value;
        dirty = true;
        updateLocalConfig(ctrl.key, this.value);
        debouncedPreview();
      });

      container.appendChild(valueSpan);
      rangeInput.dataset.key = ctrl.key;
      container.appendChild(rangeInput);
      input = container;
    } else if (ctrl.type === 'select'){
      if (ctrl.search) {
        // Select personalizado con dropdown y buscador interno
        var selectedValue = String(config[ctrl.key] || '');
        var wrap = document.createElement('div');
        wrap.className = 'gbn-select-custom';

        var display = document.createElement('button');
        display.type = 'button';
        display.className = 'gbn-select-display';
        var getLabel = function(val){
          if (!ctrl.options) return '';
          if (Object.prototype.hasOwnProperty.call(ctrl.options, val)) return String(ctrl.options[val]);
          return String(val||'');
        };
        display.textContent = getLabel(selectedValue) || 'Default';

        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.value = selectedValue;
        hidden.dataset.key = ctrl.key;

        var dropdown = document.createElement('div');
        dropdown.className = 'gbn-select-dropdown';

        var search = document.createElement('input');
        search.type = 'text';
        search.className = 'gbn-select-search';
        search.placeholder = 'Search font...';

        var list = document.createElement('ul');
        list.className = 'gbn-select-list';

        var buildOption = function(val, label){
          var li = document.createElement('li');
          li.className = 'gbn-select-option' + (String(val) === String(selectedValue) ? ' is-selected' : '');
          li.textContent = String(label);
          li.dataset.value = String(val);
          li.addEventListener('click', function(e){
            selectedValue = this.dataset.value || '';
            hidden.value = selectedValue;
            display.textContent = getLabel(selectedValue) || 'Seleccionar';
            pendingValues[ctrl.key] = selectedValue;
            dirty = true;
            updateLocalConfig(ctrl.key, selectedValue);
            reloadBlockPreview();
            // actualizar selección visual
            Array.prototype.forEach.call(list.querySelectorAll('.gbn-select-option'), function(opt){ opt.classList.toggle('is-selected', opt.dataset.value === selectedValue); });
            wrap.classList.remove('is-open');
          });
          return li;
        };

        Object.keys(ctrl.options||{}).forEach(function(k){ list.appendChild(buildOption(k, ctrl.options[k])); });

        var filterOptions = function(){
          var q = (search.value||'').toLowerCase();
          Array.prototype.forEach.call(list.children, function(li){
            var txt = (li.textContent||'').toLowerCase();
            li.style.display = (!q || txt.indexOf(q) !== -1) ? '' : 'none';
          });
        };
        search.addEventListener('input', filterOptions);

        dropdown.appendChild(search);
        dropdown.appendChild(list);

        var closeOnOutside = function(e){ if (!wrap.contains(e.target)) { wrap.classList.remove('is-open'); document.removeEventListener('click', closeOnOutside); } };
        display.addEventListener('click', function(){
          var isOpen = wrap.classList.toggle('is-open');
          if (isOpen) {
            filterOptions();
            setTimeout(function(){ try { search.focus(); } catch(e){} }, 0);
            document.addEventListener('click', closeOnOutside);
          }
        });

        wrap.appendChild(display);
        wrap.appendChild(hidden);
        wrap.appendChild(dropdown);
        input = wrap;
      } else {
        input = document.createElement('select');
        Object.keys(ctrl.options||{}).forEach(function(k){
          var o=document.createElement('option'); o.value=k; o.textContent=ctrl.options[k]; input.appendChild(o);
        });
        input.value = String(config[ctrl.key] || '');
        input.addEventListener('change', function(){
          pendingValues[ctrl.key] = this.value;
          dirty = true;
          updateLocalConfig(ctrl.key, this.value);
          reloadBlockPreview();
        });
      }
    } else if (ctrl.type === 'toggle'){
      // Crear radio buttons en lugar de toggle switch
      var radioContainer = document.createElement('div');
      radioContainer.className = 'radio-group';

      var currentValue = String(config[ctrl.key] || 'no');

      // Opción "No"
      var noLabel = document.createElement('label');
      var noInput = document.createElement('input');
      noInput.type = 'radio';
      noInput.name = 'radio-' + ctrl.key;
      noInput.value = 'no';
      noInput.checked = currentValue === 'no';
      noInput.addEventListener('change', function(){
        pendingValues[ctrl.key] = this.value;
        dirty = true;
        updateLocalConfig(ctrl.key, this.value);
        reloadBlockPreview();
      });
      noInput.dataset.key = ctrl.key;
      noLabel.appendChild(noInput);
      noLabel.appendChild(document.createTextNode(' No'));
      radioContainer.appendChild(noLabel);

      // Opción "Sí"
      var yesLabel = document.createElement('label');
      var yesInput = document.createElement('input');
      yesInput.type = 'radio';
      yesInput.name = 'radio-' + ctrl.key;
      yesInput.value = 'yes';
      yesInput.checked = currentValue === 'yes';
      yesInput.addEventListener('change', function(){
        pendingValues[ctrl.key] = this.value;
        dirty = true;
        updateLocalConfig(ctrl.key, this.value);
        reloadBlockPreview();
      });
      yesInput.dataset.key = ctrl.key;
      yesLabel.appendChild(yesInput);
      yesLabel.appendChild(document.createTextNode(' Sí'));
      radioContainer.appendChild(yesLabel);

      input = radioContainer;
    } else if (ctrl.type === 'color'){
      input = document.createElement('input');
      input.type = 'color';
      input.value = String(config[ctrl.key] || '#000000');
      input.addEventListener('change', function(){
        pendingValues[ctrl.key] = this.value;
        dirty = true;
        updateLocalConfig(ctrl.key, this.value);
        reloadBlockPreview();
      });
    } else if (ctrl.type === 'textarea'){
      input = document.createElement('textarea');
      input.value = String(config[ctrl.key] || '');
      input.rows = 3;
      input.addEventListener('input', function(){
        pendingValues[ctrl.key] = this.value;
        dirty = true;
        updateLocalConfig(ctrl.key, this.value);
        debouncedPreview();
      });
    } else {
      input = document.createElement('input');
      input.type = 'text';
      input.value = String(config[ctrl.key] || '');
      if (ctrl.unit) input.placeholder = ctrl.unit;
      input.addEventListener('input', function(){
        pendingValues[ctrl.key] = this.value;
        dirty = true;
        updateLocalConfig(ctrl.key, this.value);
        debouncedPreview();
      });
    }
    if (input.dataset) input.dataset.key = ctrl.key;
    return input;
  }

  function updateLocalConfig(key, val){
    try {
      var rt = currentRoot; if (!rt) return;
      var confStr = rt.getAttribute('data-gbn-config') || '{}';
      var confObj = {}; try { confObj = JSON.parse(confStr); } catch(e) { confObj = {}; }
      confObj[key] = val;
      rt.setAttribute('data-gbn-config', JSON.stringify(confObj));
      if (key === 'post_type') { rt.setAttribute('data-post-type', String(val||'')); }
    } catch(e){}
  }

  var debouncedSave = function(){}; // deshabilitado: ahora solo guardado manual

  function saveOptions(isAuto){
    var panel = document.getElementById('gbn-panel');
    if (!panel) return;
    var body = panel.querySelector('.gbn-body');
    var values = Object.assign({}, pendingValues);
    qsAll('.gbn-control input, .gbn-control select, .gbn-control textarea', body).forEach(function(el){
      var k = el.dataset.key; if (!k) return;
      var v;
      if (el.type === 'checkbox'){ v = el.checked ? 'yes' : 'no'; }
      else if (el.type === 'radio'){ v = el.checked ? el.value : undefined; if (v === undefined) return; }
      else { v = el.value; if (k.indexOf('width') !== -1 && v && v.indexOf('%') === -1) v = v + '%'; }
      values[k] = v;
    });
    var fd = new FormData();
    fd.append('action','gbn_save_options');
    fd.append('nonce', (cfg||{}).nonce || '' );
    fd.append('gbnId', panel.dataset.gbnId || '');
    fd.append('pageId', panel.dataset.pageId || '');
    fd.append('values', JSON.stringify(values));
    log('Guardando opciones', values);
    fetch((cfg||{}).ajaxUrl || '', { method:'POST', body: fd, credentials:'same-origin' })
      .then(function(r){ return r && r.json ? r.json() : null; })
      .then(function(res){
        log('Respuesta opciones', res);
        try {
          // Actualizar data-gbn-config del root activo (sin depender del selector)
          var rt = currentRoot || document.querySelector('.glory-split[data-gbn-id="'+ (panel.dataset.gbnId||'') +'"]');
          if (rt) {
            var confStr = rt.getAttribute('data-gbn-config') || '{}';
            var confObj = {};
            try { confObj = JSON.parse(confStr); } catch(e) { confObj = {}; }
            Object.keys(values).forEach(function(k){ confObj[k] = values[k]; });
            rt.setAttribute('data-gbn-config', JSON.stringify(confObj));
            // Actualizar data-post-type si cambia
            if (typeof values.post_type !== 'undefined') {
              rt.setAttribute('data-post-type', String(values.post_type||''));
            }
            // Mark clean tras guardar
            pendingValues = {};
            dirty = false;
            // Cerrar panel y deshabilitar drag, luego recargar bloque
            try {
              log('Cerrando panel tras guardar');
              var pnl = document.getElementById('gbn-panel');
              if (pnl) pnl.classList.remove('is-open');
              rt.classList.remove('gbn-open');
              updateDragState(rt, false);
            } catch(e) { log('Error cerrando panel/drag tras guardar', e); }
            try {
              log('Recargando bloque tras guardar');
              reloadBlockPreview();
            } catch(e) { log('Error recargando bloque tras guardar', e); }
          }
        } catch(e) { log('Error actualizando data-gbn-config', e); }
      })
      .catch(function(e){ log('Error guardando opciones', e); });
  }

  function debounce(fn, t){ var id; return function(){ var ctx=this, args=arguments; clearTimeout(id); id=setTimeout(function(){ fn.apply(ctx,args); }, t||300); } }
 
  // Debounce global para previsualizaciones intensivas (sliders y typing)
  var debouncedPreview = debounce(reloadBlockPreview, 3000);

  // Edit buttons y sortable
  var roots = qsAll('.glory-split');
  log('Contenedores .glory-split encontrados:', roots.length);
  qsAll('.glory-split').forEach(function(root, idx){
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'gbn-floating-edit';
    btn.innerHTML = '<svg data-testid="geist-icon" height="16" stroke-linejoin="round" style="color:currentColor" viewBox="0 0 16 16" width="16"><path d="M12.798 1.24199L13.3283 1.77232L14.0567 1.04389L13.1398 0.574402L12.798 1.24199ZM9.95705 4.0829L9.42672 3.55257L9.95705 4.0829ZM6.5844 6.95555L7.11473 7.48588L7.46767 7.13295L7.27837 6.67111L6.5844 6.95555ZM1.49995 12.04L2.03027 12.5703L2.03028 12.5703L1.49995 12.04ZM1.49994 14.54L0.969615 15.0703H0.969615L1.49994 14.54ZM3.99995 14.54L4.53028 15.0703L3.99995 14.54ZM9.10147 9.43848L9.37633 8.74066L8.91883 8.56046L8.57114 8.90815L9.10147 9.43848ZM14.7848 3.25519L15.4568 2.92229L14.9931 1.98617L14.2544 2.72486L14.7848 3.25519ZM11.9571 6.0829L11.4267 5.55257L11.9571 6.0829ZM10.5428 6.0829L11.0732 5.55257L11.0732 5.55257L10.5428 6.0829ZM9.95705 5.49711L9.42672 6.02744L9.42672 6.02745L9.95705 5.49711ZM12.2676 0.711655L9.42672 3.55257L10.4874 4.61323L13.3283 1.77232L12.2676 0.711655ZM10.7499 1.5C11.3659 1.5 11.9452 1.64794 12.4562 1.90957L13.1398 0.574402C12.4221 0.206958 11.6091 0 10.7499 0V1.5ZM6.99994 5.25C6.99994 3.17893 8.67888 1.5 10.7499 1.5V0C7.85045 0 5.49994 2.3505 5.49994 5.25H6.99994ZM7.27837 6.67111C7.09913 6.23381 6.99994 5.75443 6.99994 5.25H5.49994C5.49994 5.95288 5.63848 6.62528 5.89043 7.23999L7.27837 6.67111ZM6.05407 6.42522L0.969615 11.5097L2.03028 12.5703L7.11473 7.48588L6.05407 6.42522ZM0.969616 11.5097C-0.0136344 12.4929 -0.013635 14.0871 0.969615 15.0703L2.03027 14.0097C1.63281 13.6122 1.63281 12.9678 2.03027 12.5703L0.969616 11.5097ZM0.969615 15.0703C1.95287 16.0536 3.54703 16.0536 4.53028 15.0703L3.46962 14.0097C3.07215 14.4071 2.42774 14.4071 2.03027 14.0097L0.969615 15.0703ZM4.53028 15.0703L9.6318 9.96881L8.57114 8.90815L3.46962 14.0097L4.53028 15.0703ZM10.7499 9C10.2637 9 9.80071 8.90782 9.37633 8.74066L8.82661 10.1363C9.4232 10.3713 10.0724 10.5 10.7499 10.5V9ZM14.4999 5.25C14.4999 7.32107 12.821 9 10.7499 9V10.5C13.6494 10.5 15.9999 8.1495 15.9999 5.25H14.4999ZM14.1127 3.5881C14.3604 4.08811 14.4999 4.65168 14.4999 5.25H15.9999C15.9999 4.4156 15.8047 3.62465 15.4568 2.92229L14.1127 3.5881ZM12.4874 6.61323L15.3151 3.78552L14.2544 2.72486L11.4267 5.55257L12.4874 6.61323ZM10.0125 6.61323C10.6959 7.29665 11.804 7.29665 12.4874 6.61323L11.4267 5.55257C11.3291 5.6502 11.1708 5.6502 11.0732 5.55257L10.0125 6.61323ZM9.42672 6.02745L10.0125 6.61323L11.0732 5.55257L10.4874 4.96678L9.42672 6.02745ZM9.42672 3.55257C8.7433 4.23599 8.7433 5.34403 9.42672 6.02744L10.4874 4.96678C10.3898 4.86915 10.3897 4.71086 10.4874 4.61323L9.42672 3.55257Z" fill="currentColor"></path></svg>';
    btn.addEventListener('click', function(){ openPanelFor(root); });
    root.appendChild(btn);
    log('Botón Editar insertado en root #'+idx, { gbnId: root.getAttribute('data-gbn-id'), pageId: root.getAttribute('data-gbn-page-id') });

    // drag & drop: simple con HTML5 (solo activo cuando el panel está abierto)
    var list = root.querySelector('.glory-content-list');
    if (!list) return;
    list.querySelectorAll('.glory-content-item').forEach(function(item){
      item.setAttribute('draggable','false');
      item.addEventListener('dragstart', function(e){
        if (!root.classList.contains('gbn-open')) { e.preventDefault(); return false; }
        e.dataTransfer.setData('text/plain', String(Array.prototype.indexOf.call(list.children, item)));
        item.classList.add('is-drag');
      });
      item.addEventListener('dragend', function(){ item.classList.remove('is-drag'); });
    });
    list.addEventListener('dragover', function(e){ e.preventDefault(); var dragging=list.querySelector('.is-drag'); var target=e.target.closest('.glory-content-item'); if(!dragging||!target||dragging===target) return; var rect=target.getBoundingClientRect(); var before = (e.clientY - rect.top) < (rect.height/2); list.insertBefore(dragging, before ? target : target.nextSibling); });
    list.addEventListener('drop', function(){ saveOrder(root, list); });
  });

  function saveOrder(root, list){
    var ids = [];
    list.querySelectorAll('.glory-content-item').forEach(function(item){ var id = item.getAttribute('data-post-id'); if(id) ids.push(id); });
    var fd = new FormData();
    fd.append('action','gbn_save_order');
    fd.append('nonce', (cfg||{}).nonce || '' );
    fd.append('gbnId', root.getAttribute('data-gbn-id')||'');
    fd.append('pageId', root.getAttribute('data-gbn-page-id')||'');
    fd.append('postIds', JSON.stringify(ids));
    log('Guardando orden', ids);
    fetch((cfg||{}).ajaxUrl || '', { method:'POST', body: fd, credentials:'same-origin' }).then(function(r){ return r && r.json ? r.json() : null; }).then(function(res){ log('Respuesta orden', res); }).catch(function(e){ log('Error guardando orden', e); });
  }

  function updateDragState(root, enabled){
    try {
      var list = root.querySelector('.glory-content-list');
      if (!list) return;
      list.querySelectorAll('.glory-content-item').forEach(function(item){ item.setAttribute('draggable', enabled ? 'true' : 'false'); });
    } catch(e){}
  }

  function getConfig(root){
    try { return JSON.parse(root.getAttribute('data-gbn-config') || '{}'); } catch(e){ return {}; }
  }

  function reloadBlockPreview(){
    var rt = currentRoot; if (!rt) return;
    try {
      var conf = getConfig(rt) || {};
      var fd = new FormData();
      fd.append('action','gbn_preview_block');
      fd.append('nonce', (cfg||{}).nonce || '' );
      fd.append('gbnId', rt.getAttribute('data-gbn-id')||'');
      fd.append('pageId', rt.getAttribute('data-gbn-page-id')||'');
      fd.append('values', JSON.stringify(conf));
      log('Solicitando preview con conf', conf);
      fetch((cfg||{}).ajaxUrl || '', { method:'POST', body: fd, credentials:'same-origin' })
        .then(function(r){ return r && r.json ? r.json() : null; })
        .then(function(res){
          log('Respuesta preview', res);
          var html = null;
          if (res && res.success) {
            if (res.data && typeof res.data.html === 'string') html = res.data.html;
            else if (typeof res.html === 'string') html = res.html;
            else if (typeof res.data === 'string') html = res.data;
          }
          if (!html) { log('Preview sin html, abortando'); return; }
          var parser = new DOMParser();
          var doc = parser.parseFromString(html, 'text/html');
          var newRoot = doc.querySelector('.glory-split');
          if (!newRoot) { log('Preview sin .glory-split'); return; }
          log('Nuevo root generado para preview', newRoot);
          // Reemplazar/actualizar el <style id="<instance>-css"> con CSS por instancia
          try {
            var instClass = (Array.prototype.find.call(newRoot.classList, function(c){ return /^glory-split-[a-z0-9]+$/i.test(c); })) || null;
            if (instClass){
              var styleId = instClass + '-css';
              var newStyle = doc.getElementById(styleId);
              if (newStyle){
                var existingStyle = document.getElementById(styleId);
                if (existingStyle){
                  existingStyle.textContent = newStyle.textContent;
                  log('CSS por instancia actualizado en style#'+styleId);
                } else {
                  // Inyectar el CSS a <head> si no existe
                  try { document.head.appendChild(newStyle.cloneNode(true)); log('CSS por instancia insertado en <head> style#'+styleId); } catch(e){}
                }
              } else {
                log('No se encontró style por instancia en la respuesta para', styleId);
              }
            } else {
              log('No se pudo determinar clase de instancia (glory-split-*)');
            }
          } catch(e) { log('Error actualizando style por instancia', e); }
          // Reemplazar el root actual
          rt.outerHTML = newRoot.outerHTML;
          // Reasignar referencia
          var selector = '.glory-split[data-gbn-id="' + (rt.getAttribute('data-gbn-id')||'') + '"]';
          var updated = document.querySelector(selector) || document.querySelector('.glory-split');
          if (!updated) { log('No se pudo encontrar el root actualizado'); return; }
          currentRoot = updated;
          // Inyectar botón Edit si falta
          if (!updated.querySelector('.gbn-floating-edit')){
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gbn-floating-edit';
            btn.innerHTML = '<svg data-testid="geist-icon" height="16" stroke-linejoin="round" style="color:currentColor" viewBox="0 0 16 16" width="16"><path d="M12.798 1.24199L13.3283 1.77232L14.0567 1.04389L13.1398 0.574402L12.798 1.24199ZM9.95705 4.0829L9.42672 3.55257L9.95705 4.0829ZM6.5844 6.95555L7.11473 7.48588L7.46767 7.13295L7.27837 6.67111L6.5844 6.95555ZM1.49995 12.04L2.03027 12.5703L2.03028 12.5703L1.49995 12.04ZM1.49994 14.54L0.969615 15.0703H0.969615L1.49994 14.54ZM3.99995 14.54L4.53028 15.0703L3.99995 14.54ZM9.10147 9.43848L9.37633 8.74066L8.91883 8.56046L8.57114 8.90815L9.10147 9.43848ZM14.7848 3.25519L15.4568 2.92229L14.9931 1.98617L14.2544 2.72486L14.7848 3.25519ZM11.9571 6.0829L11.4267 5.55257L11.9571 6.0829ZM10.5428 6.0829L11.0732 5.55257L11.0732 5.55257L10.5428 6.0829ZM9.95705 5.49711L9.42672 6.02744L9.42672 6.02745L9.95705 5.49711ZM12.2676 0.711655L9.42672 3.55257L10.4874 4.61323L13.3283 1.77232L12.2676 0.711655ZM10.7499 1.5C11.3659 1.5 11.9452 1.64794 12.4562 1.90957L13.1398 0.574402C12.4221 0.206958 11.6091 0 10.7499 0V1.5ZM6.99994 5.25C6.99994 3.17893 8.67888 1.5 10.7499 1.5V0C7.85045 0 5.49994 2.3505 5.49994 5.25H6.99994ZM7.27837 6.67111C7.09913 6.23381 6.99994 5.75443 6.99994 5.25H5.49994C5.49994 5.95288 5.63848 6.62528 5.89043 7.23999L7.27837 6.67111ZM6.05407 6.42522L0.969615 11.5097L2.03028 12.5703L7.11473 7.48588L6.05407 6.42522ZM0.969616 11.5097C-0.0136344 12.4929 -0.013635 14.0871 0.969615 15.0703L2.03027 14.0097C1.63281 13.6122 1.63281 12.9678 2.03027 12.5703L0.969616 11.5097ZM0.969615 15.0703C1.95287 16.0536 3.54703 16.0536 4.53028 15.0703L3.46962 14.0097C3.07215 14.4071 2.42774 14.4071 2.03027 14.0097L0.969615 15.0703ZM4.53028 15.0703L9.6318 9.96881L8.57114 8.90815L3.46962 14.0097L4.53028 15.0703ZM10.7499 9C10.2637 9 9.80071 8.90782 9.37633 8.74066L8.82661 10.1363C9.4232 10.3713 10.0724 10.5 10.7499 10.5V9ZM14.4999 5.25C14.4999 7.32107 12.821 9 10.7499 9V10.5C13.6494 10.5 15.9999 8.1495 15.9999 5.25H14.4999ZM14.1127 3.5881C14.3604 4.08811 14.4999 4.65168 14.4999 5.25H15.9999C15.9999 4.4156 15.8047 3.62465 15.4568 2.92229L14.1127 3.5881ZM12.4874 6.61323L15.3151 3.78552L14.2544 2.72486L11.4267 5.55257L12.4874 6.61323ZM10.0125 6.61323C10.6959 7.29665 11.804 7.29665 12.4874 6.61323L11.4267 5.55257C11.3291 5.6502 11.1708 5.6502 11.0732 5.55257L10.0125 6.61323ZM9.42672 6.02745L10.0125 6.61323L11.0732 5.55257L10.4874 4.96678L9.42672 6.02745ZM9.42672 3.55257C8.7433 4.23599 8.7433 5.34403 9.42672 6.02744L10.4874 4.96678C10.3898 4.86915 10.3897 4.71086 10.4874 4.61323L9.42672 3.55257Z" fill="currentColor"></path></svg>';
            btn.addEventListener('click', function(){ openPanelFor(updated); });
            updated.appendChild(btn);
          }
          // Habilitar drag
          try { updated.classList.add('gbn-open'); updateDragState(updated, true); } catch(e){}
          log('Preview actualizado en el DOM', updated);
        });
    } catch(e){ log('Error general recargando preview', e); }
  }

  function refreshActiveContent(root, conf){
    try {
      var active = root.querySelector('.glory-split__item.is-active .glory-split__title');
      if (active) { active.click(); return; }
      if ((conf||{}).auto_open_first_item === 'yes') {
        var first = root.querySelector('.glory-split__item .glory-split__title');
        // Si el contenedor de contenido ya tiene algo, no hacer click (contenido precargado por PHP)
        var content = root.querySelector('.glory-split__content');
        if (first && content && !content.hasChildNodes()) { first.click(); }
      }
    } catch(e){}
  }

  // Auto Open First Item instantáneo: asegurar apertura inmediata si está habilitado
  (function instantAutoOpen(){
    try {
      qsAll('.glory-split').forEach(function(root){
        var conf = getConfig(root);
        if ((conf||{}).auto_open_first_item === 'yes'){
          var tryOpen = function(){
            var first = root.querySelector('.glory-split__item .glory-split__title');
            var content = root.querySelector('.glory-split__content');
            if (first && content && !content.hasChildNodes()) { first.click(); return true; }
            return false;
          };
          if (!tryOpen()){
            var obs = new MutationObserver(function(){ if (tryOpen()){ try { obs.disconnect(); } catch(e){} } });
            obs.observe(root, { childList: true, subtree: true });
          }
        }
      });
    } catch(e){}
  })();
})();


