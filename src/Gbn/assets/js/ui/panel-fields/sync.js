;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };

    /**
     * Agrega indicador visual de estado de sincronización para campos del Panel de Tema
     * Muestra si el campo está sincronizado con CSS o modificado manualmente
     */
    function addSyncIndicator(wrapper, block, fieldId) {
        if (!block || block.role !== 'theme') return;
        if (!fieldId || !fieldId.startsWith('components.')) return;
        
        var pathParts = fieldId.split('.');
        if (pathParts.length < 3) return;
        
        var role = pathParts[1];
        var prop = pathParts[2];
        
        // Obtener estado de sincronización
        var syncState = 'css';
        if (block.config && block.config.components && block.config.components[role]) {
            var comp = block.config.components[role];
            if (comp.__sync && comp.__sync[prop]) {
                syncState = comp.__sync[prop];
            }
        }
        
        // Crear indicador
        var indicator = document.createElement('span');
        indicator.className = 'gbn-sync-indicator gbn-sync-' + syncState;
        indicator.style.marginLeft = '8px';
        indicator.style.padding = '2px 8px';
        indicator.style.borderRadius = '4px';
        indicator.style.fontSize = '11px';
        indicator.style.fontWeight = '600';
        indicator.style.textTransform = 'uppercase';
        
        if (syncState === 'css') {
            indicator.innerHTML = '🔗 CSS';
            indicator.style.backgroundColor = '#e3f2fd';
            indicator.style.color = '#1976d2';
            indicator.title = 'Sincronizado con código CSS';
        } else {
            indicator.innerHTML = '✏️ Manual';
            indicator.style.backgroundColor = '#fff3e0';
            indicator.style.color = '#f57c00';
            indicator.title = 'Modificado manualmente - No sincroniza con CSS';
        }
        
        var target = wrapper.querySelector('legend') || wrapper.querySelector('.gbn-field-label');
        if (target) {
            target.appendChild(indicator);
            wrapper.classList.add('gbn-has-sync-indicator');
        }
    }

    /**
     * Actualiza los placeholders de todos los campos visibles basados en los nuevos defaults del tema
     */
    function updatePlaceholdersFromTheme(role, property, newValue) {
        var u = utils();
        var inputs = document.querySelectorAll('#gbn-panel input[data-role="' + role + '"]');
        
        inputs.forEach(function(input) {
            var prop = input.dataset.prop;
            if (!prop) return;
            
            if (prop === property || prop.startsWith(property + '.')) {
                var newVal = u.getThemeDefault(role, prop);
                
                // Caso spacing
                if (input.closest('.gbn-spacing-input')) {
                    var wrapper = input.closest('.gbn-field-spacing');
                    var unit = wrapper ? wrapper.dataset.unit : 'px';
                    
                    if (newVal !== undefined && newVal !== null) {
                        var parsed = u.parseSpacingValue(newVal, unit);
                        input.placeholder = parsed.valor;
                    } else {
                        input.placeholder = '-';
                    }
                }
                // Caso slider
                else if (input.type === 'range') {
                    var wrapper = input.closest('.gbn-field-range');
                    var badge = wrapper.querySelector('.gbn-field-value');
                    
                    if (input.value === '' || wrapper.classList.contains('gbn-field-inherited')) {
                        if (newVal !== undefined && newVal !== null) {
                            input.value = newVal;
                            if (badge) badge.textContent = newVal + ' (auto)';
                        } else {
                            if (badge) badge.textContent = 'auto';
                        }
                    }
                }
                // Caso texto/color
                else {
                    if (newVal !== undefined && newVal !== null) {
                        input.placeholder = newVal;
                        if (input.type === 'color' && input.closest('.gbn-field-inherited')) {
                            input.value = newVal;
                        }
                    } else {
                        input.placeholder = '';
                    }
                }
            }
        });
    }

    /**
     * Aplica indicador visual de herencia a un campo
     * Muestra de dónde proviene el valor cuando el campo está heredando (CSS o Tema)
     * @param {Element} fieldElement - Elemento del campo (wrapper .gbn-field-*)
     * @param {*} currentValue - Valor actual del campo (puede ser null/undefined si está heredando)
     * @param {*} defaultValue - Valor por defecto (del CSS o Tema)
     * @param {string} source - Origen del default ('css' | 'theme')
     */
    function aplicarIndicadorHerencia(fieldElement, currentValue, defaultValue, source) {
        if (!fieldElement) return;
        
        // Determinar si el campo está heredando
        var isInherited = !currentValue && defaultValue !== undefined && defaultValue !== null;
        var existingIndicator = fieldElement.querySelector('.gbn-inheritance-indicator');
        
        if (isInherited) {
            // Crear o actualizar indicador
            var indicator = existingIndicator;
            if (!indicator) {
                indicator = document.createElement('span');
                indicator.className = 'gbn-inheritance-indicator';
                
                // Buscar donde insertar el indicador (legend o label)
                var label = fieldElement.querySelector('legend') || fieldElement.querySelector('.gbn-field-label');
                if (label) {
                    label.appendChild(indicator);
                }
            }
            
            // Actualizar contenido y estilos
            var sourceText = source === 'css' ? 'CSS' : 'Tema';
            indicator.textContent = '↓ ' + sourceText;
            indicator.title = 'Heredado de ' + sourceText + ' defaults';
            indicator.style.fontSize = '11px';
            indicator.style.color = '#999';
            indicator.style.fontStyle = 'italic';
            indicator.style.marginLeft = '6px';
            indicator.style.fontWeight = 'normal';
            
        } else if (existingIndicator) {
            // Remover indicador si el campo tiene valor propio
            existingIndicator.remove();
        }
    }

    // Escuchar evento global de cambio de defaults
    if (typeof window !== 'undefined') {
        window.addEventListener('gbn:themeDefaultsChanged', function(e) {
            if (e.detail && e.detail.role) {
                updatePlaceholdersFromTheme(e.detail.role, e.detail.property, e.detail.value);
            }
        });
    }

    // Exportar
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.fieldSync = {
        addSyncIndicator: addSyncIndicator,
        updatePlaceholdersFromTheme: updatePlaceholdersFromTheme,
        aplicarIndicadorHerencia: aplicarIndicadorHerencia
    };

})(window);


