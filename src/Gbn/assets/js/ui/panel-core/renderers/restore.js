(function (global) {
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

    var Gbn = (global.Gbn = global.Gbn || {});
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

        btn.addEventListener('click', function () {
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

        action
            .then(function (res) {
                if (res && res.success) {
                    statusModule.set(successMessage + ' Recargando...');
                    setTimeout(function () {
                        window.location.reload();
                    }, 500);
                } else {
                    statusModule.set(errorMessage);
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            })
            .catch(function () {
                statusModule.set('Error de conexión');
                btn.disabled = false;
                btn.textContent = originalText;
            });
    }

    /**
     * Crea el indicador visual del modo de contenido actual.
     * Muestra claramente si la pagina carga desde el codigo o desde la DB.
     *
     * @returns {HTMLElement} El elemento indicador
     */
    function createContentModeIndicator() {
        var cfg = window.gloryGbnCfg || {};
        var contentMode = cfg.contentMode || 'code';
        var isFromCode = contentMode === 'code';

        // Detectar si hay datos guardados en la DB (presets)
        var presets = cfg.presets || {};
        var hasConfig = presets.config && Object.keys(presets.config).length > 0;
        var hasStyles = presets.styles && Object.keys(presets.styles).length > 0;
        var hasDbData = hasConfig || hasStyles;
        var configCount = hasConfig ? Object.keys(presets.config).length : 0;

        // Detectar inconsistencia: modo 'code' pero hay datos en DB
        var hasInconsistency = isFromCode && hasDbData;

        var indicator = document.createElement('div');
        indicator.className = 'gbn-content-mode-indicator';
        indicator.style.cssText = ['padding: 12px 16px', 'margin-bottom: 20px', 'border-radius: 8px', 'display: flex', 'align-items: center', 'gap: 10px', 'font-size: 13px', 'font-weight: 500'].join(';');

        // Estilos condicionales segun el modo
        if (hasInconsistency) {
            // Amarillo - Inconsistencia detectada
            indicator.style.backgroundColor = 'rgba(234, 179, 8, 0.15)';
            indicator.style.border = '1px solid rgba(234, 179, 8, 0.4)';
            indicator.style.color = '#eab308';
        } else if (isFromCode) {
            // Verde - Carga desde codigo (default, sin modificaciones)
            indicator.style.backgroundColor = 'rgba(34, 197, 94, 0.15)';
            indicator.style.border = '1px solid rgba(34, 197, 94, 0.4)';
            indicator.style.color = '#22c55e';
        } else {
            // Azul - Carga desde DB (tiene modificaciones guardadas)
            indicator.style.backgroundColor = 'rgba(59, 130, 246, 0.15)';
            indicator.style.border = '1px solid rgba(59, 130, 246, 0.4)';
            indicator.style.color = '#3b82f6';
        }

        // Icono SVG
        var iconSvg = hasInconsistency ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>' : isFromCode ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>' : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>';

        var icon = document.createElement('span');
        icon.innerHTML = iconSvg;
        icon.style.display = 'flex';
        icon.style.alignItems = 'center';

        var textContainer = document.createElement('div');
        textContainer.style.flex = '1';

        var title = document.createElement('div');
        title.style.fontWeight = '600';
        title.style.marginBottom = '2px';

        if (hasInconsistency) {
            title.textContent = 'Inconsistencia detectada';
        } else {
            title.textContent = isFromCode ? 'Carga desde: Codigo' : 'Carga desde: Base de datos';
        }

        var desc = document.createElement('div');
        desc.style.fontSize = '11px';
        desc.style.opacity = '0.8';
        desc.style.fontWeight = '400';

        if (hasInconsistency) {
            desc.textContent = 'Modo "code" pero hay ' + configCount + ' bloques en DB. La pagina deberia mostrar el codigo, no la DB.';
        } else if (isFromCode) {
            desc.textContent = 'Esta pagina muestra el contenido original del codigo PHP.';
        } else {
            desc.textContent = 'Esta pagina tiene ' + configCount + ' bloques guardados en la DB.';
        }

        textContainer.appendChild(title);
        textContainer.appendChild(desc);

        // Badge del modo
        var badge = document.createElement('span');
        badge.textContent = contentMode.toUpperCase();
        badge.style.cssText = ['padding: 2px 8px', 'border-radius: 4px', 'font-size: 10px', 'font-weight: 700', 'letter-spacing: 0.5px', 'background: currentColor', 'color: ' + (hasInconsistency ? 'rgba(234, 179, 8, 0.15)' : isFromCode ? 'rgba(34, 197, 94, 0.15)' : 'rgba(59, 130, 246, 0.15)')].join(';');

        indicator.appendChild(icon);
        indicator.appendChild(textContainer);
        indicator.appendChild(badge);

        return indicator;
    }

    /**
     * Renderiza el panel de restauración de valores.
     * Incluye opciones para restaurar página, tema, o ambos.
     */
    function renderRestorePanel() {
        modeManager.setup('restore', 'gbn-panel-restore', 'Restaurar valores');

        var state = stateModule.get();
        if (!state.body) {
            return;
        }

        state.body.innerHTML = '';

        var container = document.createElement('div');
        container.className = 'gbn-panel-restore';
        container.style.padding = '20px';

        // Indicador de modo de contenido actual (diagnostico visual)
        var modeIndicator = createContentModeIndicator();
        container.appendChild(modeIndicator);

        // --- Restore Page Section ---
        var pageSection = createRestoreSection({
            title: 'Restaurar Página Actual',
            description: 'Elimina configuraciones personalizadas de esta página y restaura el contenido original.',
            buttonText: 'Restaurar Página',
            buttonClass: 'gbn-btn-danger',
            onClick: function (btn) {
                if (Gbn.persistence && typeof Gbn.persistence.restorePage === 'function') {
                    executeRestoreAction(btn, 'Restaurando...', 'Restaurar Página', Gbn.persistence.restorePage(), 'Página restaurada.', 'Error al restaurar página');
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
            onClick: function (btn) {
                if (Gbn.persistence && typeof Gbn.persistence.saveThemeSettings === 'function') {
                    executeRestoreAction(btn, 'Restableciendo...', 'Restaurar Tema Global', Gbn.persistence.saveThemeSettings({}), 'Tema restablecido.', 'Error al restablecer tema');
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
            onClick: function (btn) {
                btn.disabled = true;
                btn.textContent = 'Restaurando Todo...';
                statusModule.set('Restaurando Todo...');

                var p1 = Gbn.persistence && typeof Gbn.persistence.restorePage === 'function' ? Gbn.persistence.restorePage() : Promise.resolve({success: false});
                var p2 = Gbn.persistence && typeof Gbn.persistence.saveThemeSettings === 'function' ? Gbn.persistence.saveThemeSettings({}) : Promise.resolve({success: false});

                Promise.all([p1, p2])
                    .then(function () {
                        statusModule.set('Restauración completa. Recargando...');
                        setTimeout(function () {
                            window.location.reload();
                        }, 500);
                    })
                    .catch(function () {
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
