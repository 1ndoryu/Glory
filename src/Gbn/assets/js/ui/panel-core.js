;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;

    var panelRoot = null;
    var panelBody = null;
    var panelTitle = null;
    var panelFooter = null;
    var activeBlock = null;
    var panelForm = null;
    var panelMode = 'idle';
    var panelNotice = null;
    var panelStatusTimer = null;
    var listenersBound = false;

    function renderPlaceholder(message) {
        if (!panelBody) { return; }
        panelMode = 'idle';
        panelForm = null;
        var text = message || 'Selecciona un bloque para configurar.';
        panelBody.innerHTML = '<div class="gbn-panel-empty">' + text + '</div>';
        if (panelFooter) {
            // Re-query to ensure we have the current attached element
            if (panelRoot) {
                var currentFooter = panelRoot.querySelector('.gbn-footer-primary');
                if (currentFooter) {
                    panelFooter = currentFooter;
                }
            }
            
            panelFooter.disabled = true;
            panelFooter.textContent = 'Guardar (próximamente)';
            // Remove listeners? Cloning is safer.
            // Remove listeners? Cloning is safer.
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
            // Default behavior for save button (page config)
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
                // Solo refrescar si el panel está abierto
                if (!panel.isOpen()) return;
                
                var detail = event.detail || {};
                utils.debug('Breakpoint cambiado a: ' + detail.current + ', refrescando panel');
                
                // Si hay un bloque activo, re-renderizar sus controles
                if (panelMode === 'block' && activeBlock) {
                    if (Gbn.ui.panelRender && Gbn.ui.panelRender.renderBlockControls) {
                        Gbn.ui.panelRender.renderBlockControls(activeBlock, panelBody);
                    }
                }
                
                // Si está en modo tema, re-renderizar settings
                if (panelMode === 'theme') {
                    panel.openTheme(); // Forzar re-render completo
                }
                
                // Si está en modo página, re-renderizar settings
                if (panelMode === 'page') {
                    panel.openPage(); // Forzar re-render completo
                }
            });
        }
        return panelRoot;
    }

    function setActiveBlock(nextBlock) {
        if (activeBlock && activeBlock.element) {
            activeBlock.element.classList.remove('gbn-block-active');
            // Clean up simulation classes (Fase 10)
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

    var panel = {
        init: function () { ensurePanelMounted(); },
        isOpen: function () { return !!(panelRoot && panelRoot.classList.contains('is-open')); },
        
        open: function (block) {
            ensurePanelMounted(); 
            setActiveBlock(block || null); 
            panelMode = block ? 'block' : 'idle';
            
            if (panelRoot) { 
                panelRoot.classList.add('is-open'); 
                panelRoot.setAttribute('aria-hidden', 'false'); 
                document.body.classList.add('gbn-panel-open'); // Visual Docking 
                
                // Reset classes (Defensive cleanup)
                panelRoot.classList.remove('gbn-panel-primary', 'gbn-panel-secondary', 'gbn-panel-component', 'gbn-panel-theme', 'gbn-panel-page', 'gbn-panel-restore');
                
                if (block) {
                    if (block.role === 'principal') {
                        panelRoot.classList.add('gbn-panel-primary');
                    } else if (block.role === 'secundario') {
                        panelRoot.classList.add('gbn-panel-secondary');
                    } else {
                        panelRoot.classList.add('gbn-panel-component');
                    }
                }
            }
            
            var title = 'GBN Panel';
            if (block) { 
                if (block.meta && block.meta.label) { title = block.meta.label; } 
                else if (block.role) { title = 'Configuración: ' + block.role; } 
            }
            if (panelTitle) { panelTitle.textContent = title; }
            
            if (!panelBody) { return; }
            if (!block) { renderPlaceholder(); return; }
            
            // Delegate to panelRender
            if (Gbn.ui.panelRender && Gbn.ui.panelRender.renderBlockControls) {
                Gbn.ui.panelRender.renderBlockControls(block, panelBody);
            } else {
                panelBody.innerHTML = 'Error: panelRender no disponible';
            }
            
            utils.debug('Panel abierto', block ? block.id : null);
        },
        
        openTheme: function () {
            ensurePanelMounted(); setActiveBlock(null); panelMode = 'theme';
            if (panelRoot) { 
                panelRoot.classList.add('is-open'); 
                panelRoot.setAttribute('aria-hidden', 'false'); 
                document.body.classList.add('gbn-panel-open'); // Visual Docking
                panelRoot.classList.remove('gbn-panel-primary', 'gbn-panel-secondary', 'gbn-panel-component', 'gbn-panel-page', 'gbn-panel-restore');
                panelRoot.classList.add('gbn-panel-theme');
            }
            if (panelTitle) { panelTitle.textContent = 'Configuración del Tema'; }
            
            if (panelBody) {
                // Primero verificar si hay estado local (cambios no guardados)
                var localSettings = Gbn.config && Gbn.config.themeSettings;
                
                if (localSettings && Object.keys(localSettings).length > 0) {
                    // Usar estado local si existe
                    if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.renderThemeSettingsForm) {
                        var currentFooter = panelRoot.querySelector('.gbn-footer-primary');
                        if (currentFooter) panelFooter = currentFooter;
                        Gbn.ui.panelTheme.renderThemeSettingsForm(localSettings, panelBody, panelFooter);
                    }
                    setPanelStatus('Listo (desde cache local)');
                    return;
                }
                
                // Si no hay estado local, cargar del servidor
                panelBody.innerHTML = '<div class="gbn-panel-loading">Cargando configuración...</div>';
                panelForm = null;
                setPanelStatus('Cargando...');

                if (Gbn.persistence && typeof Gbn.persistence.getThemeSettings === 'function') {
                    Gbn.persistence.getThemeSettings().then(function(res) {
                        if (res && res.success) {
                            var settings = res.data || {};
                            // Guardar en estado local para uso futuro
                            if (!Gbn.config) Gbn.config = {};
                            Gbn.config.themeSettings = settings;
                            
                            // Delegate to panelTheme
                            if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.renderThemeSettingsForm) {
                                // Ensure footer is fresh
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
        },
        
        openPage: function () {
            ensurePanelMounted(); setActiveBlock(null); panelMode = 'page';
            if (panelRoot) { 
                panelRoot.classList.add('is-open'); 
                panelRoot.setAttribute('aria-hidden', 'false'); 
                document.body.classList.add('gbn-panel-open'); // Visual Docking
                panelRoot.classList.remove('gbn-panel-primary', 'gbn-panel-secondary', 'gbn-panel-component', 'gbn-panel-theme', 'gbn-panel-restore');
                panelRoot.classList.add('gbn-panel-page');
            }
            if (panelTitle) { panelTitle.textContent = 'Configuración de Página'; }
            
            if (panelBody) {
                // Primero verificar si hay estado local (cambios no guardados)
                var localSettings = Gbn.config && Gbn.config.pageSettings;
                
                if (localSettings && Object.keys(localSettings).length > 0) {
                    // Usar estado local si existe
                    if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.renderPageSettingsForm) {
                        var currentFooter = panelRoot.querySelector('.gbn-footer-primary');
                        if (currentFooter) panelFooter = currentFooter;
                        Gbn.ui.panelTheme.renderPageSettingsForm(localSettings, panelBody, panelFooter);
                    }
                    setPanelStatus('Listo (desde cache local)');
                    return;
                }
                
                // Si no hay estado local, cargar del servidor
                panelBody.innerHTML = '<div class="gbn-panel-loading">Cargando configuración...</div>';
                panelForm = null;
                setPanelStatus('Cargando...');

                if (Gbn.persistence && typeof Gbn.persistence.getPageSettings === 'function') {
                    Gbn.persistence.getPageSettings().then(function(res) {
                        if (res && res.success) {
                            var settings = res.data || {};
                            // Guardar en estado local para uso futuro
                            if (!Gbn.config) Gbn.config = {};
                            Gbn.config.pageSettings = settings;
                            
                            // Delegate to panelTheme
                            if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.renderPageSettingsForm) {
                                // Ensure footer is fresh
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
        },
        
        openRestore: function () {
            // ... (Restore logic from original panel.js)
            ensurePanelMounted(); setActiveBlock(null); panelMode = 'restore';
            if (panelRoot) { 
                panelRoot.classList.add('is-open'); 
                panelRoot.setAttribute('aria-hidden', 'false');
                document.body.classList.add('gbn-panel-open'); // Visual Docking
                panelRoot.classList.remove('gbn-panel-primary', 'gbn-panel-secondary', 'gbn-panel-component', 'gbn-panel-theme', 'gbn-panel-page');
                panelRoot.classList.add('gbn-panel-restore');
            }
            if (panelTitle) { panelTitle.textContent = 'Restaurar valores'; }
            
            if (panelBody) {
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
                    // No confirm dialog
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
                    // No confirm dialog
                    themeBtn.disabled = true;
                    themeBtn.textContent = 'Restableciendo...';
                    setPanelStatus('Restableciendo Tema...');

                    // Reset theme settings by saving an empty object
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
                    // No confirm dialog
                    allBtn.disabled = true;
                    allBtn.textContent = 'Restaurando Todo...';
                    setPanelStatus('Restaurando Todo...');
                    
                    var p1 = Gbn.persistence && typeof Gbn.persistence.restorePage === 'function' ? Gbn.persistence.restorePage() : Promise.resolve({success:false});
                    var p2 = Gbn.persistence && typeof Gbn.persistence.saveThemeSettings === 'function' ? Gbn.persistence.saveThemeSettings({}) : Promise.resolve({success:false});
                    
                    Promise.all([p1, p2]).then(function(results) {
                        // Check if at least one succeeded or both
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
            }
            
            panelForm = null;
            setPanelStatus('Esperando confirmación');
        },
        
        close: function () {
            // [FIX] Guardar el modo actual antes de resetearlo para hacer cleanup específico
            var previousMode = panelMode;
            
            if (panelRoot) { 
                panelRoot.classList.remove('is-open'); 
                panelRoot.setAttribute('aria-hidden', 'true');
                // [FIX] Limpiar todas las clases de modo para evitar residuos que afecten el layout
                panelRoot.classList.remove('gbn-panel-primary', 'gbn-panel-secondary', 'gbn-panel-component', 'gbn-panel-theme', 'gbn-panel-page', 'gbn-panel-restore');
            }
            
            // [FIX] Asegurar que la clase de docking siempre se remueva
            document.body.classList.remove('gbn-panel-open'); // Visual Docking
            
            // [FIX] Resetear estado específico de Theme Settings/Page Settings
            if (previousMode === 'theme' && Gbn.ui.theme && Gbn.ui.theme.render && Gbn.ui.theme.render.resetState) {
                Gbn.ui.theme.render.resetState();
            }
            
            setActiveBlock(null); 
            renderPlaceholder(); 
            panelMode = 'idle'; // [FIX] Reset mode
            utils.debug('Panel cerrado');
        },
        
        // Public API for other modules
        setStatus: setPanelStatus,
        flashStatus: flashPanelStatus,
        updateActiveBlock: setActiveBlock,
        getActiveBlock: function () { return activeBlock; },
        refreshControls: function(block) {
             // Re-render controls for the current block
             if (activeBlock && activeBlock.id === block.id && panelBody) {
                 if (Gbn.ui.panelRender && Gbn.ui.panelRender.renderBlockControls) {
                     Gbn.ui.panelRender.renderBlockControls(block, panelBody);
                 }
             }
        },
        getMode: function() { return panelMode; }
    };

    // Expose API for panel-fields.js
    // panel-fields.js uses Gbn.ui.panelApi.updateConfigValue
    // We delegate to panelRender.updateConfigValue
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
