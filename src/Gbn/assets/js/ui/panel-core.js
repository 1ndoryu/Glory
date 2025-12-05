;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;

    // === ESTADO DEL PANEL (Privado) ===
    var panelRoot = null;
    var panelBody = null;
    var panelTitle = null;
    var panelFooter = null;
    var activeBlock = null;
    var panelForm = null;
    var panelMode = 'idle'; // Modos: 'idle', 'block', 'theme', 'page', 'restore'
    var panelNotice = null;
    var panelStatusTimer = null;
    var listenersBound = false;

    // Lista completa de clases de modo para limpieza
    var MODE_CLASSES = ['gbn-panel-primary', 'gbn-panel-secondary', 'gbn-panel-component', 'gbn-panel-theme', 'gbn-panel-page', 'gbn-panel-restore'];

    // === FUNCIONES AUXILIARES DE UI ===

    function renderPlaceholder(message) {
        if (!panelBody) { return; }
        panelForm = null;
        var text = message || 'Selecciona un bloque para configurar.';
        panelBody.innerHTML = '<div class="gbn-panel-empty">' + text + '</div>';
        if (panelFooter) {
            if (panelRoot) {
                var currentFooter = panelRoot.querySelector('.gbn-footer-primary');
                if (currentFooter) {
                    panelFooter = currentFooter;
                }
            }
            
            panelFooter.disabled = true;
            panelFooter.textContent = 'Guardar (próximamente)';
            if (panelFooter && panelFooter.parentNode) {
                var newFooterBtn = panelFooter.cloneNode(true);
                panelFooter.parentNode.replaceChild(newFooterBtn, panelFooter);
                panelFooter = newFooterBtn;
            }
        }
        setPanelStatus('Cambios en vivo');
    }

    function ensurePanelMounted() {
        if (panelRoot) { return panelRoot; }
        panelRoot = document.getElementById('gbn-panel');
        if (!panelRoot) {
            panelRoot = document.createElement('aside');
            panelRoot.id = 'gbn-panel';
            panelRoot.setAttribute('aria-hidden', 'true');
            panelRoot.innerHTML = ''
                + '<header class="gbn-header">'
                + '  <span class="gbn-header-title">GBN Panel</span>'
                + '  <button type="button" class="gbn-header-close" data-gbn-action="close-panel" aria-label="Close panel">×</button>'
                + '</header>'
                + '<div class="gbn-body"></div>'
                + '<footer class="gbn-footer">'
                + '  <span class="gbn-footer-status">Cambios en vivo</span>'
                + '  <button type="button" class="gbn-footer-primary" disabled>Guardar (próximamente)</button>'
                + '</footer>';
            document.body.appendChild(panelRoot);
        }
        panelBody = panelRoot.querySelector('.gbn-body');
        panelTitle = panelRoot.querySelector('.gbn-header-title');
        panelFooter = panelRoot.querySelector('.gbn-footer-primary');
        panelNotice = panelRoot.querySelector('.gbn-footer-status');
        
        if (panelFooter && !panelFooter.__gbnBound) {
            panelFooter.__gbnBound = true;
            panelFooter.disabled = false;
            panelFooter.textContent = 'Guardar';
            panelFooter.addEventListener('click', function (event) {
                event.preventDefault();
                if (!Gbn.persistence || typeof Gbn.persistence.savePageConfig !== 'function') { return; }
                setPanelStatus('Guardando...');
                Gbn.persistence.savePageConfig().then(function (res) {
                    flashPanelStatus(res && res.success ? 'Guardado' : 'Error al guardar');
                }).catch(function () { flashPanelStatus('Error al guardar'); });
            });
        }
        
        renderPlaceholder();
        
        if (!listenersBound) {
            listenersBound = true;
            var closeBtn = panelRoot.querySelector('[data-gbn-action="close-panel"]');
            if (closeBtn) {
                closeBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    panel.close();
                });
            }
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && panel.isOpen()) {
                    panel.close();
                }
            });
            
            // Listener para refrescar panel cuando cambia el breakpoint
            window.addEventListener('gbn:breakpointChanged', function(event) {
                if (!panel.isOpen()) return;
                
                var detail = event.detail || {};
                utils.debug('Breakpoint cambiado a: ' + detail.current + ', refrescando panel');
                
                if (panelMode === 'block' && activeBlock) {
                    if (Gbn.ui.panelRender && Gbn.ui.panelRender.renderBlockControls) {
                        Gbn.ui.panelRender.renderBlockControls(activeBlock, panelBody);
                    }
                }
                
                if (panelMode === 'theme') {
                    panel.openTheme();
                }
                
                if (panelMode === 'page') {
                    panel.openPage();
                }
            });
        }
        return panelRoot;
    }

    function setActiveBlock(nextBlock) {
        if (activeBlock && activeBlock.element) {
            activeBlock.element.classList.remove('gbn-block-active');
            activeBlock.element.classList.remove('gbn-simulated-hover', 'gbn-simulated-focus');
            if (activeBlock.element.__gbnBtn) {
                activeBlock.element.__gbnBtn.classList.remove('is-active');
            }
        }
        activeBlock = nextBlock || null;
        if (activeBlock && activeBlock.element) {
            activeBlock.element.classList.add('gbn-block-active');
            if (activeBlock.element.__gbnBtn) {
                activeBlock.element.__gbnBtn.classList.add('is-active');
            }
        }
    }

    function setPanelStatus(text) {
        if (panelNotice) {
            panelNotice.textContent = text;
        }
    }

    function flashPanelStatus(text) {
        if (!panelNotice) { return; }
        panelNotice.textContent = text;
        if (panelStatusTimer) { clearTimeout(panelStatusTimer); }
        panelStatusTimer = setTimeout(function () {
            panelNotice.textContent = 'Cambios en vivo';
        }, 1600);
    }

    // === LÓGICA CENTRALIZADA DE TRANSICIÓN DE MODOS ===
    // [REFACTOR] Esta es la función central que maneja TODAS las transiciones de panel.
    // Resuelve el bug de docking persistente al garantizar limpieza consistente.

    /**
     * Limpia completamente el estado del modo anterior antes de cambiar.
     * Esta función es CRÍTICA para evitar el bug de docking persistente.
     */
    function cleanupCurrentMode() {
        var currentMode = panelMode;
        utils.debug('[Panel] Limpiando modo actual: ' + currentMode);
        
        // 1. Limpiar estado específico de Theme Settings
        if (currentMode === 'theme') {
            if (Gbn.ui.theme && Gbn.ui.theme.render && Gbn.ui.theme.render.resetState) {
                Gbn.ui.theme.render.resetState();
                utils.debug('[Panel] Estado de Theme Settings reseteado');
            }
        }
        
        // 2. Limpiar clases de simulación del bloque activo
        if (activeBlock && activeBlock.element) {
            activeBlock.element.classList.remove('gbn-simulated-hover', 'gbn-simulated-focus');
        }
        
        // 3. Limpiar bloque activo
        setActiveBlock(null);
    }

    /**
     * Configura la UI del panel para el nuevo modo.
     * Centraliza toda la lógica común de apertura.
     * @param {string} newMode - El nuevo modo del panel
     * @param {string} panelClass - La clase CSS a agregar al panel
     * @param {string} title - El título a mostrar en el header
     */
    function setupPanelForMode(newMode, panelClass, title) {
        ensurePanelMounted();
        
        // [CRÍTICO] Primero limpiar el modo anterior
        cleanupCurrentMode();
        
        // Actualizar el modo
        panelMode = newMode;
        
        if (panelRoot) {
            // Abrir el panel
            panelRoot.classList.add('is-open');
            panelRoot.setAttribute('aria-hidden', 'false');
            
            // [CRÍTICO] Agregar clase de docking al body
            document.body.classList.add('gbn-panel-open');
            
            // Limpiar TODAS las clases de modo anteriores
            for (var i = 0; i < MODE_CLASSES.length; i++) {
                panelRoot.classList.remove(MODE_CLASSES[i]);
            }
            
            // Agregar la clase del nuevo modo
            if (panelClass) {
                panelRoot.classList.add(panelClass);
            }
        }
        
        // Actualizar título
        if (panelTitle) {
            panelTitle.textContent = title || 'GBN Panel';
        }
        
        utils.debug('[Panel] Modo cambiado a: ' + newMode);
    }

    // === FUNCIONES DE RENDERIZADO POR MODO ===

    function renderBlockPanel(block) {
        var modeClass = 'gbn-panel-component';
        if (block && block.role === 'principal') {
            modeClass = 'gbn-panel-primary';
        } else if (block && block.role === 'secundario') {
            modeClass = 'gbn-panel-secondary';
        }
        
        var title = 'GBN Panel';
        if (block) {
            if (block.meta && block.meta.label) { 
                title = block.meta.label; 
            } else if (block.role) { 
                title = 'Configuración: ' + block.role; 
            }
        }
        
        setupPanelForMode('block', modeClass, title);
        setActiveBlock(block);
        
        if (!panelBody) { return; }
        if (!block) { 
            renderPlaceholder(); 
            return; 
        }
        
        if (Gbn.ui.panelRender && Gbn.ui.panelRender.renderBlockControls) {
            Gbn.ui.panelRender.renderBlockControls(block, panelBody);
        } else {
            panelBody.innerHTML = 'Error: panelRender no disponible';
        }
        
        utils.debug('[Panel] Bloque abierto: ' + (block ? block.id : null));
    }

    function renderThemePanel() {
        setupPanelForMode('theme', 'gbn-panel-theme', 'Configuración del Tema');
        
        if (!panelBody) { return; }
        
        // Verificar si hay estado local (cambios no guardados)
        var localSettings = Gbn.config && Gbn.config.themeSettings;
        
        if (localSettings && Object.keys(localSettings).length > 0) {
            if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.renderThemeSettingsForm) {
                var currentFooter = panelRoot.querySelector('.gbn-footer-primary');
                if (currentFooter) panelFooter = currentFooter;
                Gbn.ui.panelTheme.renderThemeSettingsForm(localSettings, panelBody, panelFooter);
            }
            setPanelStatus('Listo (desde cache local)');
            return;
        }
        
        // Cargar del servidor
        panelBody.innerHTML = '<div class="gbn-panel-loading">Cargando configuración...</div>';
        panelForm = null;
        setPanelStatus('Cargando...');

        if (Gbn.persistence && typeof Gbn.persistence.getThemeSettings === 'function') {
            Gbn.persistence.getThemeSettings().then(function(res) {
                // [FIX] Si el panel ya no está en modo theme (usuario lo cerró), abortar.
                if (panelMode !== 'theme') {
                    utils.debug('[Panel] Carga de tema abortada: panel cerrado o cambio de modo');
                    return;
                }

                if (res && res.success) {
                    var settings = res.data || {};
                    if (!Gbn.config) Gbn.config = {};
                    Gbn.config.themeSettings = settings;
                    
                    if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.renderThemeSettingsForm) {
                        var currentFooter = panelRoot.querySelector('.gbn-footer-primary');
                        if (currentFooter) panelFooter = currentFooter;
                        Gbn.ui.panelTheme.renderThemeSettingsForm(settings, panelBody, panelFooter);
                    } else {
                        panelBody.innerHTML = 'Error: panelTheme no disponible';
                    }
                    setPanelStatus('Listo');
                } else {
                    panelBody.innerHTML = '<div class="gbn-panel-error">Error al cargar configuración.</div>';
                    setPanelStatus('Error');
                }
            }).catch(function() {
                panelBody.innerHTML = '<div class="gbn-panel-error">Error de conexión.</div>';
                setPanelStatus('Error');
            });
        } else {
            panelBody.innerHTML = '<div class="gbn-panel-error">Persistencia no disponible.</div>';
        }
    }

    function renderPagePanel() {
        setupPanelForMode('page', 'gbn-panel-page', 'Configuración de Página');
        
        if (!panelBody) { return; }
        
        var localSettings = Gbn.config && Gbn.config.pageSettings;
        
        if (localSettings && Object.keys(localSettings).length > 0) {
            if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.renderPageSettingsForm) {
                var currentFooter = panelRoot.querySelector('.gbn-footer-primary');
                if (currentFooter) panelFooter = currentFooter;
                Gbn.ui.panelTheme.renderPageSettingsForm(localSettings, panelBody, panelFooter);
            }
            setPanelStatus('Listo (desde cache local)');
            return;
        }
        
        panelBody.innerHTML = '<div class="gbn-panel-loading">Cargando configuración...</div>';
        panelForm = null;
        setPanelStatus('Cargando...');

        if (Gbn.persistence && typeof Gbn.persistence.getPageSettings === 'function') {
            Gbn.persistence.getPageSettings().then(function(res) {
                // [FIX] Si el panel ya no está en modo page (usuario lo cerró), abortar.
                if (panelMode !== 'page') {
                    utils.debug('[Panel] Carga de página abortada: panel cerrado o cambio de modo');
                    return;
                }

                if (res && res.success) {
                    var settings = res.data || {};
                    if (!Gbn.config) Gbn.config = {};
                    Gbn.config.pageSettings = settings;
                    
                    if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.renderPageSettingsForm) {
                        var currentFooter = panelRoot.querySelector('.gbn-footer-primary');
                        if (currentFooter) panelFooter = currentFooter;
                        Gbn.ui.panelTheme.renderPageSettingsForm(settings, panelBody, panelFooter);
                    } else {
                        panelBody.innerHTML = 'Error: panelTheme no disponible';
                    }
                    setPanelStatus('Listo');
                } else {
                    panelBody.innerHTML = '<div class="gbn-panel-error">Error al cargar configuración.</div>';
                    setPanelStatus('Error');
                }
            }).catch(function() {
                panelBody.innerHTML = '<div class="gbn-panel-error">Error de conexión.</div>';
                setPanelStatus('Error');
            });
        } else {
            panelBody.innerHTML = '<div class="gbn-panel-error">Persistencia no disponible.</div>';
        }
    }

    function renderRestorePanel() {
        setupPanelForMode('restore', 'gbn-panel-restore', 'Restaurar valores');
        
        if (!panelBody) { return; }
        
        panelBody.innerHTML = '';
        
        var container = document.createElement('div');
        container.className = 'gbn-panel-restore';
        container.style.padding = '20px';
        
        // --- Restore Page Section ---
        var pageSection = document.createElement('div');
        pageSection.className = 'gbn-restore-section';
        
        var pageTitle = document.createElement('h4');
        pageTitle.textContent = 'Restaurar Página Actual';
        
        var desc = document.createElement('p');
        desc.textContent = 'Elimina configuraciones personalizadas de esta página y restaura el contenido original.';
        
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'gbn-btn-primary gbn-btn-danger';
        btn.textContent = 'Restaurar Página';
        
        btn.addEventListener('click', function() {
            btn.disabled = true;
            btn.textContent = 'Restaurando...';
            setPanelStatus('Restaurando Página...');
            
            if (Gbn.persistence && typeof Gbn.persistence.restorePage === 'function') {
                Gbn.persistence.restorePage().then(function(res) {
                    if (res && res.success) {
                        setPanelStatus('Página restaurada. Recargando...');
                        setTimeout(function() { window.location.reload(); }, 500);
                    } else {
                        setPanelStatus('Error al restaurar página');
                        btn.disabled = false;
                        btn.textContent = 'Restaurar Página';
                    }
                }).catch(function() {
                    setPanelStatus('Error de conexión');
                    btn.disabled = false;
                    btn.textContent = 'Restaurar Página';
                });
            }
        });

        pageSection.appendChild(pageTitle);
        pageSection.appendChild(desc);
        pageSection.appendChild(btn);
        container.appendChild(pageSection);

        // --- Restore Theme Section ---
        var themeSection = document.createElement('div');
        themeSection.className = 'gbn-restore-section';
        
        var themeTitle = document.createElement('h4');
        themeTitle.textContent = 'Restaurar Tema Global';

        var themeDesc = document.createElement('p');
        themeDesc.textContent = 'Restablece todos los ajustes globales del tema (colores, tipografía, defaults de componentes) a sus valores originales.';

        var themeBtn = document.createElement('button');
        themeBtn.type = 'button';
        themeBtn.className = 'gbn-btn-primary gbn-btn-warning';
        themeBtn.textContent = 'Restaurar Tema Global';

        themeBtn.addEventListener('click', function() {
            themeBtn.disabled = true;
            themeBtn.textContent = 'Restableciendo...';
            setPanelStatus('Restableciendo Tema...');

            if (Gbn.persistence && typeof Gbn.persistence.saveThemeSettings === 'function') {
                Gbn.persistence.saveThemeSettings({}).then(function(res) {
                    if (res && res.success) {
                        setPanelStatus('Tema restablecido. Recargando...');
                        setTimeout(function() { window.location.reload(); }, 500);
                    } else {
                        setPanelStatus('Error al restablecer tema');
                        themeBtn.disabled = false;
                        themeBtn.textContent = 'Restaurar Tema Global';
                    }
                }).catch(function() {
                    setPanelStatus('Error de conexión');
                    themeBtn.disabled = false;
                    themeBtn.textContent = 'Restaurar Tema Global';
                });
            } else {
                 setPanelStatus('Función no disponible');
                 themeBtn.disabled = false;
            }
        });

        themeSection.appendChild(themeTitle);
        themeSection.appendChild(themeDesc);
        themeSection.appendChild(themeBtn);
        container.appendChild(themeSection);
        
        // --- Restore ALL Section ---
        var allSection = document.createElement('div');
        allSection.className = 'gbn-restore-section gbn-restore-all';
        
        var allTitle = document.createElement('h4');
        allTitle.textContent = 'Restaurar Todo (Página + Tema)';

        var allDesc = document.createElement('p');
        allDesc.textContent = 'Restablece TANTO la página actual como el tema global a sus estados originales. ¡Acción destructiva!';

        var allBtn = document.createElement('button');
        allBtn.type = 'button';
        allBtn.className = 'gbn-btn-primary gbn-btn-danger-dark';
        allBtn.textContent = 'Restaurar TODO';

        allBtn.addEventListener('click', function() {
            allBtn.disabled = true;
            allBtn.textContent = 'Restaurando Todo...';
            setPanelStatus('Restaurando Todo...');
            
            var p1 = Gbn.persistence && typeof Gbn.persistence.restorePage === 'function' ? Gbn.persistence.restorePage() : Promise.resolve({success:false});
            var p2 = Gbn.persistence && typeof Gbn.persistence.saveThemeSettings === 'function' ? Gbn.persistence.saveThemeSettings({}) : Promise.resolve({success:false});
            
            Promise.all([p1, p2]).then(function(results) {
                setPanelStatus('Restauración completa. Recargando...');
                setTimeout(function() { window.location.reload(); }, 500);
            }).catch(function() {
                setPanelStatus('Error durante la restauración');
                allBtn.disabled = false;
                allBtn.textContent = 'Restaurar TODO';
            });
        });

        allSection.appendChild(allTitle);
        allSection.appendChild(allDesc);
        allSection.appendChild(allBtn);
        container.appendChild(allSection);

        panelBody.appendChild(container);
        
        panelForm = null;
        setPanelStatus('Esperando confirmación');
    }

    // === API PÚBLICA DEL PANEL ===

    var panel = {
        init: function () { 
            ensurePanelMounted(); 
        },
        
        isOpen: function () { 
            return !!(panelRoot && panelRoot.classList.contains('is-open')); 
        },
        
        // [REFACTOR] Todas las funciones open* ahora usan la lógica centralizada
        open: function (block) {
            renderBlockPanel(block);
        },
        
        openTheme: function () {
            renderThemePanel();
        },
        
        openPage: function () {
            renderPagePanel();
        },
        
        openRestore: function () {
            renderRestorePanel();
        },
        
        /**
         * Cierra el panel y limpia completamente el estado.
         * [REFACTOR] Usa cleanupCurrentMode() para garantizar limpieza consistente.
         */
        close: function () {
            utils.debug('[Panel] Cerrando, modo actual: ' + panelMode);
            
            // [CRÍTICO] Limpiar el estado del modo actual
            // Envolvemos en try-catch para asegurar que un error en limpieza no impida cerrar el panel
            try {
                cleanupCurrentMode();
            } catch (e) {
                console.error('[Panel] Error durante limpieza de modo:', e);
            }
            
            if (panelRoot) { 
                panelRoot.classList.remove('is-open'); 
                panelRoot.setAttribute('aria-hidden', 'true');
                
                // Limpiar TODAS las clases de modo
                for (var i = 0; i < MODE_CLASSES.length; i++) {
                    panelRoot.classList.remove(MODE_CLASSES[i]);
                }
            }
            
            // [CRÍTICO] Remover la clase de docking del body
            // Esta línea previene el bug de página contraída
            document.body.classList.remove('gbn-panel-open');
            
            renderPlaceholder(); 
            panelMode = 'idle';
            
            utils.debug('[Panel] Cerrado exitosamente, modo: idle');
        },
        
        // Public API for other modules
        setStatus: setPanelStatus,
        flashStatus: flashPanelStatus,
        updateActiveBlock: setActiveBlock,
        getActiveBlock: function () { return activeBlock; },
        refreshControls: function(block) {
             if (activeBlock && activeBlock.id === block.id && panelBody) {
                 if (Gbn.ui.panelRender && Gbn.ui.panelRender.renderBlockControls) {
                     Gbn.ui.panelRender.renderBlockControls(block, panelBody);
                 }
             }
        },
        getMode: function() { return panelMode; }
    };

    // Expose API for panel-fields.js
    var panelApi = {
        getActiveBlock: function () { return activeBlock; },
        updateConfigValue: function (block, path, value) { 
            if (Gbn.ui.panelRender && Gbn.ui.panelRender.updateConfigValue) {
                return Gbn.ui.panelRender.updateConfigValue(block, path, value);
            }
        },
        flashStatus: function (text) { return flashPanelStatus(text); },
        applyBlockStyles: function (block) { 
             if (Gbn.ui.panelRender && Gbn.ui.panelRender.applyBlockStyles) {
                 return Gbn.ui.panelRender.applyBlockStyles(block);
             }
        }
    };

    Gbn.ui.panelApi = panelApi;
    Gbn.ui.panel = panel;

})(window);
