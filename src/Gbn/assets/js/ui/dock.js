;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;

    var dockRoot = null;
    var saveBtn = null;

    var ICONS = {
        power: '<svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>',
        palette: '<svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path></svg>',
        settings: '<svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
        restore: '<svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>',
        save: '<svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>'
    };

    function createButton(icon, title, action, className) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'gbn-dock-btn ' + (className || '');
        btn.innerHTML = icon;
        btn.title = title;
        btn.dataset.gbnAction = action;
        
        // Tooltip simple
        var tooltip = document.createElement('span');
        tooltip.className = 'gbn-dock-tooltip';
        tooltip.textContent = title;
        btn.appendChild(tooltip);
        
        return btn;
    }

    function init() {
        if (dockRoot) return;

        dockRoot = document.createElement('div');
        dockRoot.id = 'gbn-dock';
        dockRoot.className = 'gbn-dock';

        var toggleBtn = createButton(ICONS.power, 'Activar/Desactivar GBN', 'toggle', 'gbn-dock-main');
        var themeBtn = createButton(ICONS.palette, 'Configurar Tema', 'theme');
        var pageBtn = createButton(ICONS.settings, 'Configurar Página', 'page');
        var restoreBtn = createButton(ICONS.restore, 'Restaurar', 'restore');
        saveBtn = createButton(ICONS.save, 'Guardar', 'save', 'gbn-dock-save');
        saveBtn.disabled = true; // Inicialmente deshabilitado

        // Selector de Breakpoint
        var breakpointSelector = document.createElement('div');
        breakpointSelector.className = 'gbn-breakpoint-selector';
        breakpointSelector.innerHTML = 
            '<button class="gbn-breakpoint-btn active" data-bp="desktop" title="Vista Desktop">' +
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                    '<rect x="2" y="3" width="20" height="14" rx="2"></rect>' +
                    '<line x1="8" y1="21" x2="16" y2="21"></line>' +
                    '<line x1="12" y1="17" x2="12" y2="21"></line>' +
                '</svg>' +
            '</button>' +
            '<button class="gbn-breakpoint-btn" data-bp="tablet" title="Vista Tablet">' +
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                    '<rect x="5" y="2" width="14" height="20" rx="2"></rect>' +
                    '<line x1="12" y1="18" x2="12" y2="18"></line>' +
                '</svg>' +
            '</button>' +
            '<button class="gbn-breakpoint-btn" data-bp="mobile" title="Vista Móvil">' +
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                    '<rect x="7" y="2" width="10" height="20" rx="2"></rect>' +
                    '<line x1="12" y1="18" x2="12" y2="18"></line>' +
                '</svg>' +
            '</button>';

        // Event listeners para breakpoints
        var bpBtns = breakpointSelector.querySelectorAll('.gbn-breakpoint-btn');
        for (var i = 0; i < bpBtns.length; i++) {
            bpBtns[i].addEventListener('click', function() {
                var bp = this.getAttribute('data-bp');
                if (Gbn.responsive && Gbn.responsive.setBreakpoint) {
                    Gbn.responsive.setBreakpoint(bp);
                    
                    // Update active state
                    for (var j = 0; j < bpBtns.length; j++) {
                        bpBtns[j].classList.remove('active');
                    }
                    this.classList.add('active');
                }
            });
        }

        // Event Listeners
        toggleBtn.addEventListener('click', function() {
            if (Gbn.ui && Gbn.ui.inspector && typeof Gbn.ui.inspector.setActive === 'function') {
                // Toggle logic is handled by inspector, but we need to know current state.
                // Better: inspector calls dock to update state, or dock calls inspector.
                // Let's assume inspector exposes a toggle function or we access internal state via a getter if available.
                // For now, we'll dispatch a custom event or rely on inspector being global.
                // Actually, inspector.js exposes setActive. We need to know current state.
                // Let's toggle class on body to check or use Gbn.utils.isGbnActive()
                var isActive = document.documentElement.classList.contains('gbn-active');
                Gbn.ui.inspector.setActive(!isActive);
            }
        });

        themeBtn.addEventListener('click', function() { 
            if (Gbn.ui.panel) {
                if (Gbn.ui.panel.isOpen() && Gbn.ui.panel.getMode && Gbn.ui.panel.getMode() === 'theme') {
                    Gbn.ui.panel.close();
                } else {
                    Gbn.ui.panel.openTheme();
                }
            } 
        });
        
        pageBtn.addEventListener('click', function() { 
            if (Gbn.ui.panel) {
                if (Gbn.ui.panel.isOpen() && Gbn.ui.panel.getMode && Gbn.ui.panel.getMode() === 'page') {
                    Gbn.ui.panel.close();
                } else {
                    Gbn.ui.panel.openPage();
                }
            } 
        });
        
        restoreBtn.addEventListener('click', function() { 
             if (Gbn.ui.panel) {
                if (Gbn.ui.panel.isOpen() && Gbn.ui.panel.getMode && Gbn.ui.panel.getMode() === 'restore') {
                    Gbn.ui.panel.close();
                } else {
                    Gbn.ui.panel.openRestore();
                }
             }
        });
        
        saveBtn.addEventListener('click', function() {
            if (saveBtn.disabled) return;
            if (Gbn.persistence) {
                saveBtn.classList.add('is-loading');
                
                var promises = [];
                
                // 1. Save Block Config
                if (typeof Gbn.persistence.savePageConfig === 'function') {
                    promises.push(Gbn.persistence.savePageConfig());
                }
                
                // 2. Save Page Settings (if modified/present)
                if (Gbn.config && Gbn.config.pageSettings && typeof Gbn.persistence.savePageSettings === 'function') {
                    promises.push(Gbn.persistence.savePageSettings(Gbn.config.pageSettings));
                }
                
                // 3. Save Theme Settings (if modified/present)
                if (Gbn.config && Gbn.config.themeSettings && typeof Gbn.persistence.saveThemeSettings === 'function') {
                    promises.push(Gbn.persistence.saveThemeSettings(Gbn.config.themeSettings));
                }
                
                Promise.all(promises).then(function(results) {
                    saveBtn.classList.remove('is-loading');
                    saveBtn.classList.remove('has-changes');
                    saveBtn.disabled = true; // Deshabilitar tras guardar
                    
                    // Check if any failed
                    var anyError = results.some(function(r) { return !r || !r.success; });
                    if (anyError) {
                        // Optional: show error feedback
                        if (Gbn.ui.panel && Gbn.ui.panel.flashStatus) Gbn.ui.panel.flashStatus('Error al guardar algunos datos');
                    } else {
                        if (Gbn.ui.panel && Gbn.ui.panel.flashStatus) Gbn.ui.panel.flashStatus('Guardado correctamente');
                    }
                }).catch(function() {
                    saveBtn.classList.remove('is-loading');
                    if (Gbn.ui.panel && Gbn.ui.panel.flashStatus) Gbn.ui.panel.flashStatus('Error de conexión');
                });
            }
        });

        dockRoot.appendChild(toggleBtn);
        dockRoot.appendChild(themeBtn);
        dockRoot.appendChild(pageBtn);
        dockRoot.appendChild(restoreBtn);
        dockRoot.appendChild(breakpointSelector); // Selector antes del guardar
        dockRoot.appendChild(saveBtn);

        document.body.appendChild(dockRoot);

        // Listen for changes
        global.addEventListener('gbn:layoutChanged', enableSave);
        global.addEventListener('gbn:contentHydrated', enableSave);
        global.addEventListener('gbn:configChanged', enableSave);
    }

    function enableSave() {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.classList.add('has-changes');
        }
    }

    function updateState(active) {
        if (dockRoot) {
            if (active) {
                dockRoot.classList.add('is-active');
            } else {
                dockRoot.classList.remove('is-active');
            }
        }
    }

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.dock = {
        init: init,
        updateState: updateState,
        enableSave: enableSave
    };

})(window);
