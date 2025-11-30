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
            panelFooter.disabled = true;
            panelFooter.textContent = 'Guardar (próximamente)';
            // Remove listeners? Cloning is safer.
            var newFooterBtn = panelFooter.cloneNode(true);
            panelFooter.parentNode.replaceChild(newFooterBtn, panelFooter);
            panelFooter = newFooterBtn;
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
        }
        return panelRoot;
    }

    function setActiveBlock(nextBlock) {
        if (activeBlock && activeBlock.element) {
            activeBlock.element.classList.remove('gbn-block-active');
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
                
                // Reset classes
                panelRoot.classList.remove('gbn-panel-primary', 'gbn-panel-secondary', 'gbn-panel-component', 'gbn-panel-theme', 'gbn-panel-page');
                
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
                panelRoot.classList.remove('gbn-panel-primary', 'gbn-panel-secondary', 'gbn-panel-component', 'gbn-panel-page');
                panelRoot.classList.add('gbn-panel-theme');
            }
            if (panelTitle) { panelTitle.textContent = 'Configuración del Tema'; }
            
            if (panelBody) {
                panelBody.innerHTML = '<div class="gbn-panel-loading">Cargando configuración...</div>';
                panelForm = null;
                setPanelStatus('Cargando...');

                if (Gbn.persistence && typeof Gbn.persistence.getThemeSettings === 'function') {
                    Gbn.persistence.getThemeSettings().then(function(res) {
                        if (res && res.success) {
                            var settings = res.data || {};
                            // Delegate to panelTheme
                            if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.renderThemeSettingsForm) {
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
                panelRoot.classList.remove('gbn-panel-primary', 'gbn-panel-secondary', 'gbn-panel-component', 'gbn-panel-theme');
                panelRoot.classList.add('gbn-panel-page');
            }
            if (panelTitle) { panelTitle.textContent = 'Configuración de Página'; }
            
            if (panelBody) {
                panelBody.innerHTML = '<div class="gbn-panel-loading">Cargando configuración...</div>';
                panelForm = null;
                setPanelStatus('Cargando...');

                if (Gbn.persistence && typeof Gbn.persistence.getPageSettings === 'function') {
                    Gbn.persistence.getPageSettings().then(function(res) {
                        if (res && res.success) {
                            var settings = res.data || {};
                            // Delegate to panelTheme
                            if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.renderPageSettingsForm) {
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
            if (panelRoot) { panelRoot.classList.add('is-open'); panelRoot.setAttribute('aria-hidden', 'false'); }
            if (panelTitle) { panelTitle.textContent = 'Restaurar valores'; }
            
            if (panelBody) {
                panelBody.innerHTML = '';
                
                var container = document.createElement('div');
                container.className = 'gbn-panel-restore';
                container.style.padding = '20px';
                
                var desc = document.createElement('p');
                desc.textContent = 'Esta acción eliminará todas las configuraciones personalizadas de GBN para esta página y restaurará el contenido original definido en el código. Esta acción no se puede deshacer.';
                desc.style.marginBottom = '20px';
                desc.style.fontSize = '13px';
                desc.style.lineHeight = '1.5';
                desc.style.color = '#666';
                
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'gbn-btn-primary gbn-btn-danger';
                btn.textContent = 'Restaurar Defaults';
                btn.style.width = '100%';
                btn.style.padding = '10px';
                btn.style.backgroundColor = '#d9534f';
                btn.style.color = '#fff';
                btn.style.border = 'none';
                btn.style.borderRadius = '4px';
                btn.style.cursor = 'pointer';
                
                btn.addEventListener('click', function() {
                    btn.disabled = true;
                    btn.textContent = 'Restaurando...';
                    setPanelStatus('Restaurando...');
                    
                    if (Gbn.persistence && typeof Gbn.persistence.restorePage === 'function') {
                        Gbn.persistence.restorePage().then(function(res) {
                            if (res && res.success) {
                                setPanelStatus('Restaurado. Recargando...');
                                setTimeout(function() {
                                    window.location.reload();
                                }, 500);
                            } else {
                                setPanelStatus('Error al restaurar');
                                btn.disabled = false;
                                btn.textContent = 'Restaurar Defaults';
                            }
                        }).catch(function() {
                            setPanelStatus('Error de conexión');
                            btn.disabled = false;
                            btn.textContent = 'Restaurar Defaults';
                        });
                    }
                });
                
                container.appendChild(desc);
                container.appendChild(btn);
                panelBody.appendChild(container);
            }
            
            panelForm = null;
            setPanelStatus('Esperando confirmación');
        },
        
        close: function () {
            if (panelRoot) { panelRoot.classList.remove('is-open'); panelRoot.setAttribute('aria-hidden', 'true'); }
            setActiveBlock(null); renderPlaceholder(); utils.debug('Panel cerrado');
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
        }
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
