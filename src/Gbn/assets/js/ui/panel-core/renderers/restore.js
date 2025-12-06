;(function (global) {
    'use strict';

    /**
     * panel-core/renderers/restore.js - Renderer para panel de restauración
     * 
     * Maneja la UI del panel de restauración de valores.
     * Permite restaurar página, tema global, o ambos.
     * 
     * Parte del REFACTOR-003: Refactorización de panel-core.js
     * 
     * @module panel-core/renderers/restore
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelCore = Gbn.ui.panelCore || {};
    Gbn.ui.panelCore.renderers = Gbn.ui.panelCore.renderers || {};

    var stateModule = Gbn.ui.panelCore.state;
    var modeManager = Gbn.ui.panelCore.modeManager;
    var statusModule = Gbn.ui.panelCore.status;

    /**
     * Crea una sección de restauración con título, descripción y botón.
     * 
     * @param {Object} options - Configuración de la sección
     * @param {string} options.title - Título de la sección
     * @param {string} options.description - Descripción de la acción
     * @param {string} options.buttonText - Texto del botón
     * @param {string} options.buttonClass - Clase CSS del botón
     * @param {string} [options.extraClass] - Clase CSS adicional para la sección
     * @param {Function} options.onClick - Handler del click del botón
     * @returns {HTMLElement} Elemento de sección
     */
    function createRestoreSection(options) {
        var section = document.createElement('div');
        section.className = 'gbn-restore-section' + (options.extraClass ? ' ' + options.extraClass : '');
        
        var title = document.createElement('h4');
        title.textContent = options.title;
        
        var desc = document.createElement('p');
        desc.textContent = options.description;
        
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'gbn-btn-primary ' + options.buttonClass;
        btn.textContent = options.buttonText;
        
        btn.addEventListener('click', function() {
            options.onClick(btn);
        });
        
        section.appendChild(title);
        section.appendChild(desc);
        section.appendChild(btn);
        
        return section;
    }

    /**
     * Ejecuta una acción de restauración con manejo de estado del botón.
     * 
     * @param {HTMLElement} btn - Botón que disparó la acción
     * @param {string} loadingText - Texto mientras carga
     * @param {string} originalText - Texto original del botón
     * @param {Function} action - Promise de la acción
     * @param {string} successMessage - Mensaje al éxito
     * @param {string} errorMessage - Mensaje al error
     */
    function executeRestoreAction(btn, loadingText, originalText, action, successMessage, errorMessage) {
        btn.disabled = true;
        btn.textContent = loadingText;
        statusModule.set(loadingText);
        
        action.then(function(res) {
            if (res && res.success) {
                statusModule.set(successMessage + ' Recargando...');
                setTimeout(function() { window.location.reload(); }, 500);
            } else {
                statusModule.set(errorMessage);
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }).catch(function() {
            statusModule.set('Error de conexión');
            btn.disabled = false;
            btn.textContent = originalText;
        });
    }

    /**
     * Renderiza el panel de restauración de valores.
     * Incluye opciones para restaurar página, tema, o ambos.
     */
    function renderRestorePanel() {
        modeManager.setup('restore', 'gbn-panel-restore', 'Restaurar valores');
        
        var state = stateModule.get();
        if (!state.body) { return; }
        
        state.body.innerHTML = '';
        
        var container = document.createElement('div');
        container.className = 'gbn-panel-restore';
        container.style.padding = '20px';
        
        // --- Restore Page Section ---
        var pageSection = createRestoreSection({
            title: 'Restaurar Página Actual',
            description: 'Elimina configuraciones personalizadas de esta página y restaura el contenido original.',
            buttonText: 'Restaurar Página',
            buttonClass: 'gbn-btn-danger',
            onClick: function(btn) {
                if (Gbn.persistence && typeof Gbn.persistence.restorePage === 'function') {
                    executeRestoreAction(
                        btn, 
                        'Restaurando...', 
                        'Restaurar Página',
                        Gbn.persistence.restorePage(),
                        'Página restaurada.',
                        'Error al restaurar página'
                    );
                }
            }
        });
        container.appendChild(pageSection);

        // --- Restore Theme Section ---
        var themeSection = createRestoreSection({
            title: 'Restaurar Tema Global',
            description: 'Restablece todos los ajustes globales del tema (colores, tipografía, defaults de componentes) a sus valores originales.',
            buttonText: 'Restaurar Tema Global',
            buttonClass: 'gbn-btn-warning',
            onClick: function(btn) {
                if (Gbn.persistence && typeof Gbn.persistence.saveThemeSettings === 'function') {
                    executeRestoreAction(
                        btn,
                        'Restableciendo...',
                        'Restaurar Tema Global',
                        Gbn.persistence.saveThemeSettings({}),
                        'Tema restablecido.',
                        'Error al restablecer tema'
                    );
                } else {
                    statusModule.set('Función no disponible');
                }
            }
        });
        container.appendChild(themeSection);
        
        // --- Restore ALL Section ---
        var allSection = createRestoreSection({
            title: 'Restaurar Todo (Página + Tema)',
            description: 'Restablece TANTO la página actual como el tema global a sus estados originales. ¡Acción destructiva!',
            buttonText: 'Restaurar TODO',
            buttonClass: 'gbn-btn-danger-dark',
            extraClass: 'gbn-restore-all',
            onClick: function(btn) {
                btn.disabled = true;
                btn.textContent = 'Restaurando Todo...';
                statusModule.set('Restaurando Todo...');
                
                var p1 = Gbn.persistence && typeof Gbn.persistence.restorePage === 'function' 
                    ? Gbn.persistence.restorePage() 
                    : Promise.resolve({success:false});
                var p2 = Gbn.persistence && typeof Gbn.persistence.saveThemeSettings === 'function' 
                    ? Gbn.persistence.saveThemeSettings({}) 
                    : Promise.resolve({success:false});
                
                Promise.all([p1, p2]).then(function() {
                    statusModule.set('Restauración completa. Recargando...');
                    setTimeout(function() { window.location.reload(); }, 500);
                }).catch(function() {
                    statusModule.set('Error durante la restauración');
                    btn.disabled = false;
                    btn.textContent = 'Restaurar TODO';
                });
            }
        });
        container.appendChild(allSection);

        state.body.appendChild(container);
        
        stateModule.set('form', null);
        statusModule.set('Esperando confirmación');
    }

    // === EXPONER API ===
    Gbn.ui.panelCore.renderers.restore = renderRestorePanel;

})(window);
